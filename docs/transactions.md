---
title: Transactions
weight: 10
---

Laravel Patches supports wrapping patch execution in database transactions, providing automatic rollback on failures and ensuring data consistency.

## Overview

When transactions are enabled, if a patch throws an exception, all database changes made by that patch are automatically rolled back.

---

## Enabling Transactions

### Per-Patch Basis

Enable transactions for a specific patch:

```php
use Rappasoft\LaravelPatches\Patch;

class MyPatch extends Patch
{
    protected bool $useTransaction = true;

    public function up()
    {
        // All database operations here will be wrapped in a transaction
        User::where('type', 'old')->update(['type' => 'new']);
        Setting::create(['key' => 'version', 'value' => '2.0']);

        // If anything fails, everything rolls back
    }

    public function down()
    {
        User::where('type', 'new')->update(['type' => 'old']);
        Setting::where('key', 'version')->delete();
    }
}
```

### Globally

Enable transactions for all patches by default:

```php
// config/laravel-patches.php
return [
    'use_transactions' => env('PATCHES_USE_TRANSACTIONS', false),
];
```

Or in `.env`:

```bash
PATCHES_USE_TRANSACTIONS=true
```

---

## How It Works

When `$useTransaction = true`:

```php
DB::transaction(function () {
   $patch->up();
});
```

If an exception is thrown:
1. All database changes are rolled back
2. The exception is re-thrown
3. No changes persist to the database

---

## When to Use Transactions

### ✅ Use Transactions When:

- **Updating multiple related records**
  ```php
  public function up()
  {
      User::all()->each(function ($user) {
          $user->profile->update(['verified' => true]);
          $user->update(['status' => 'active']);
      });
  }
  ```

- **Data must be consistent**
  ```php
  public function up()
  {
      // Transfer data between tables
      OldTable::chunk(100, function ($records) {
          foreach ($records as $record) {
              NewTable::create($record->toArray());
              $record->delete();
          }
      });
  }
  ```

- **Testing patches** (easy rollback)
  ```php
  protected bool $useTransaction = true; // For development
  ```

### ❌ Avoid Transactions When:

- **Running long operations**
  ```php
  // This will lock tables for extended period
  User::chunk(1000, function ($users) { /* ... */ });
  ```

- **Making external API calls**
  ```php
  public function up()
  {
      // Transactions won't rollback API calls
      Http::post('api.example.com/update', $data);
  }
  ```

- **Using DDL statements** (some databases)
  ```php
  public function up()
  {
      // Schema changes may auto-commit
      Schema::create('new_table', function ($table) {
          // ...
      });
  }
  ```

- **Processing huge datasets**
  ```php
  // Memory and lock issues
  Model::all()->each(function ($record) { /* ... */ });
  ```

---

## Configuration Priority

The priority order for determining transaction usage:

1. **Patch-level** `$useTransaction` property (highest priority)
2. **Config** `laravel-patches.use_transactions`
3. **Default** `false` (lowest priority)

Example:

```php
// Config says TRUE
'use_transactions' => true,

// But patch says FALSE - patch wins
class MyPatch extends Patch
{
    protected bool $useTransaction = false; // This takes precedence
}
```

---

## Manual Transaction Control

For complex scenarios, you can manually control transactions:

```php
use Rappasoft\LaravelPatches\Patch;
use Illuminate\Support\Facades\DB;

class ComplexPatch extends Patch
{
    protected bool $useTransaction = false; // Disable automatic

    public function up()
    {
        // Part 1 - in transaction
        DB::transaction(function () {
            User::where('type', 'A')->update(['status' => 'active']);
        });

        // Part 2 - NOT in transaction (external API)
        Http::post('api.example.com/notify');

        // Part 3 - in new transaction
        DB::transaction(function () {
            Log::create(['action' => 'patch_completed']);
        });
    }
}
```

---

## Nested Transactions

Laravel uses savepoints for nested transactions:

```php
public function up()
{
    DB::transaction(function () {
        User::create(['name' => 'John']);

        DB::transaction(function () {
            // Nested - uses savepoint
            Profile::create(['user_id' => 1]);
        });
    });
}
```

---

## Handling Transaction Failures

### Catching and Logging

```php
use Rappasoft\LaravelPatches\Patch;

class SafePatch extends Patch
{
    protected bool $useTransaction = true;

    public function up()
    {
        try {
            User::where('old_status', 'pending')
                ->update(['status' => 'active']);
                
            $this->log('Updated user statuses');
        } catch (\Exception $e) {
            $this->log('Failed to update: ' . $e->getMessage());
            throw $e; // Re-throw to trigger rollback
        }
    }
}
```

### Partial Rollback

```php
public function up()
{
    // This succeeds and commits
    DB::transaction(function () {
        User::create(['name' => 'Alice']);
    });

    // This fails and rolls back (only its changes)
    try {
        DB::transaction(function () {
            User::create(['email' => 'invalid']); // Fails validation
        });
    } catch (\Exception $e) {
        $this->log('Second transaction failed, but first succeeded');
    }
}
```

---

## Performance Considerations

### Large Datasets

For large datasets, use chunking WITHOUT wrapping everything in one transaction:

```php
class LargeDataPatch extends Patch
{
    protected bool $useTransaction = false;

    public function up()
    {
        User::chunk(500, function ($users) {
            // Each chunk in its own transaction
            DB::transaction(function () use ($users) {
                foreach ($users as $user) {
                    $user->update(['migrated' => true]);
                }
            });
        });
    }
}
```

### Deadlock Prevention

```php
public function up()
{
    // Process in smaller batches to reduce lock contention
    $retries = 3;

    User::chunk(100, function ($users) use (&$retries) {
        try {
            DB::transaction(function () use ($users) {
                foreach ($users as $user) {
                    $user->processUpdate();
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '40001' && $retries > 0) { // Deadlock
                $retries--;
                sleep(1);
                // Retry logic
            } else {
                throw $e;
            }
        }
    });
}
```

---

## Testing with Transactions

Transactions are especially useful in testing:

```php
test('patch updates users correctly', function () {
    $patch = new MyPatch();
    $patch->useTransaction = true;

    try {
        $patch->up();
        $this->fail('Expected exception was not thrown');
    } catch (\Exception $e) {
        // Transaction rolled back automatically
        $this->assertEquals(0, User::where('migrated', true)->count());
    }
});
```

---

## Database-Specific Behavior

### MySQL/MariaDB

- DDL statements (CREATE, ALTER, DROP) cause implicit commits
- Transactions work well for DML (INSERT, UPDATE, DELETE)

### PostgreSQL

- Full support for transactional DDL
- Can rollback schema changes

### SQLite

- Transactions work for all operations
- Good for testing

---

## Best Practices

1. **Enable for critical patches**
   ```php
   protected bool $useTransaction = true; // For data integrity
   ```

2. **Disable for long-running patches**
   ```php
   protected bool $useTransaction = false; // Prevent long locks
   ```

3. **Use manual control when needed**
   ```php
   // Mix transactional and non-transactional code
   ```

4. **Test both success and failure scenarios**
   ```php
   test('rolls back on failure', function () { /* ... */ });
   ```

5. **Monitor transaction duration**
   ```php
   Event::listen(PatchExecuted::class, function ($event) {
       if ($event->executionTime > 10000) {
           Log::warning("Long transaction: {$event->patch}");
       }
   });
   ```

6. **Document transaction usage**
   ```php
   /**
    * Updates user roles across multiple tables.
    * Uses transactions to ensure consistency.
    */
   protected bool $useTransaction = true;
   ```

---

## Troubleshooting

### Transaction Timeout

If patches take too long:

```php
DB::statement('SET SESSION max_execution_time = 300000'); // 5 minutes
```

### Lock Wait Timeout

```php
DB::statement('SET SESSION innodb_lock_wait_timeout = 120');
```

### Checking Transaction Status

```php
public function up()
{
    if (DB::transactionLevel() > 0) {
        $this->log('Currently in a transaction');
    }
}
```
