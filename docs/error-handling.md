---
title: Error Handling
weight: 9
---

Laravel Patches provides comprehensive error handling to ensure patches fail gracefully and provide detailed information for troubleshooting.

## Error Behavior

### Default Behavior

By default, if a patch throws an exception:

1. **Execution stops immediately**
2. **Error is logged** to the patches table
3. **Exception is re-thrown**
4. **No subsequent patches run**

### Continue on Error

You can configure patches to continue running even when one fails:

```php
// config/laravel-patches.php
return [
    'stop_on_error' => false, // Continue running patches after failures
];
```

Or use environment variable:

```bash
PATCHES_STOP_ON_ERROR=false
```

---

## Error Logging

### Automatic Error Capture

When a patch fails, the following information is automatically captured:

- Error message
- Stack trace
- Execution time before failure
- Memory usage
- User who ran the patch
- Environment (production, staging, etc.)

### Database Storage

Failed patches are stored in the patches table with:

```php
[
    'status' => 'failed',
    'error_message' => 'Exception message',
    'error_trace' => 'Full stack trace',
    'execution_time_ms' => 1234,
]
```

### Viewing Failed Patches

```bash
# See all patches including failed ones
php artisan patch:status

# Filter only failed patches
php artisan patch:list --status=failed
```

---

## Configuration

### config/laravel-patches.php

```php
return [
    /**
     * Stop execution on first error
     */
    'stop_on_error' => env('PATCHES_STOP_ON_ERROR', true),

    /**
     * Log errors to patches table
     */
    'log_errors' => env('PATCHES_LOG_ERRORS', true),
];
```

---

## Handling Errors in Patches

### Try-Catch Blocks

Handle specific errors within your patch:

```php
use Rappasoft\LaravelPatches\Patch;

class MyPatch extends Patch
{
    public function up()
    {
        try {
            // Risky operation
            User::where('email', 'LIKE', '%old-domain.com')
                ->update(['email' => DB::raw("REPLACE(email, 'old-domain.com', 'new-domain.com')")]);
                
            $this->log('Updated user emails');
        } catch (\Exception $e) {
            $this->log('Error updating emails: ' . $e->getMessage());
            
            // Optionally rethrow
            throw $e;
        }
    }

    public function down()
    {
        // Rollback logic
    }
}
```

### Validation Before Execution

Validate data before making changes:

```php
public function up()
{
    // Check if operation is safe
    if (User::whereNull('email')->exists()) {
        throw new \Exception('Cannot proceed: Users with null emails exist');
    }

    // Proceed with patch
    // ...
}
```

### Progressive Error Handling

```php
public function up()
{
    $errors = [];
    $processed = 0;

    User::chunk(100, function ($users) use (&$errors, &$processed) {
        foreach ($users as $user) {
            try {
                $user->update(['status' => 'active']);
                $processed++;
            } catch (\Exception $e) {
                $errors[] = "User {$user->id}: {$e->getMessage()}";
            }
        }
    });

    $this->log("Processed: {$processed} users");

    if (count($errors)) {
        $this->log("Errors: " . implode(', ', $errors));
        throw new \Exception(count($errors) . ' users failed to update');
    }
}
```

---

## Event-Based Error Handling

Subscribe to the `PatchFailed` event for custom error handling:

```php
use Rappasoft\LaravelPatches\Events\PatchFailed;

Event::listen(PatchFailed::class, function (PatchFailed $event) {
    // Send alert
    Notification::send(
        User::admins()->get(),
        new PatchFailedNotification($event->patch, $event->exception)
    );

    // Log to external service
    Bugsnag::notifyException($event->exception, [
        'patch' => $event->patch,
        'batch' => $event->batch,
    ]);

    // Create rollback plan
    DB::table('patch_failures')->insert([
        'patch' => $event->patch,
        'error' => $event->exception->getMessage(),
        'needs_manual_intervention' => true,
        'created_at' => now(),
    ]);
});
```

---

## Best Practices

### 1. Use Descriptive Error Messages

```php
throw new \Exception("Failed to update user #{$user->id}: email validation failed");
```

### 2. Log Progress

```php
$this->log("Processing 1000 records...");
// Process records
$this->log("Completed successfully");
```

### 3. Validate Preconditions

```php
public function up()
{
    if (! Schema::hasColumn('users', 'old_field')) {
        throw new \Exception('old_field column does not exist');
    }

    // Proceed with migration
}
```

### 4. Use Transactions (see Transactions documentation)

```php
protected bool $useTransaction = true;
```

### 5. Test Error Scenarios

```php
// In your tests
test('handles duplicate email error', function () {
    User::factory()->create(['email' => 'test@example.com']);

    $this->expectException(\Exception::class);

    Artisan::call('patch');
});
```

---

## Debugging Failed Patches

### 1. Check Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log
```

### 2. Query Failed Patches

```php
use Rappasoft\LaravelPatches\Models\Patch;

$failed = Patch::where('status', 'failed')->get();

foreach ($failed as $patch) {
    dump($patch->patch);
    dump($patch->error_message);
    dump($patch->error_trace);
}
```

### 3. Re-run Individual Patch

Create a temporary command to re-run a specific patch:

```php
// app/Console/Commands/RerunPatch.php
public function handle()
{
    $patchName = $this->argument('patch');

    // Delete old failed attempt
    Patch::where('patch', $patchName)->delete();

    // Re-run the patch
    Artisan::call('patch');
}
```

---

## Recovery Strategies

### After a Failed Patch

1. **Review the error** in `patch:status` or database
2. **Understand the cause** from error message and trace
3. **Fix the data or code** that caused the failure
4. **Rollback if needed** with `patch:rollback`
5. **Fix the patch file** if it contains bugs
6. **Re-run patches** with `php artisan patch`

### Manual Intervention

If a patch partially completed before failing:

```php
public function up()
{
    // Check what was already done
    if (DB::table('temp_migration_state')->exists()) {
        $this->log('Resuming from previous failed attempt');
        // Continue from checkpoint
    } else {
        // Start fresh
    }

    // Your patch logic with checkpoints
}
```

---

## Production Safeguards

### 1. Always Test First

```bash
# Test in staging
php artisan patch --dry-run
php artisan patch
```

### 2. Backup Before Running

```bash
php artisan snapshot:create
php artisan patch
```

### 3. Monitor Execution

```bash
# Run with verbose output
php artisan patch -v
```

### 4. Have Rollback Plan

Ensure your `down()` methods can properly rollback:

```php
public function down()
{
    // Reverse the changes from up()
}
```
