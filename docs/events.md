---
title: Events
weight: 8
---

Laravel Patches dispatches events throughout the patch lifecycle, allowing you to hook into the execution process for logging, notifications, monitoring, or custom behavior.

## Available Events

### PatchExecuting

Dispatched before a patch's `up()` method runs.

**Properties:**
- `string $patch` - The patch name
- `int $batch` - The batch number  
- `object $instance` - The patch instance

**Example:**

```php
use Rappasoft\LaravelPatches\Events\PatchExecuting;

Event::listen(PatchExecuting::class, function (PatchExecuting $event) {
    Log::info("About to run patch: {$event->patch} in batch {$event->batch}");
    
    // Send notification
    Notification::send($admins, new PatchStarting($event->patch));
});
```

---

### PatchExecuted

Dispatched after a patch successfully executes.

**Properties:**
- `string $patch` - The patch name
- `int $batch` - The batch number
- `?array $log` - The patch logs
- `int $executionTime` - Execution time in milliseconds
- `float $memoryUsed` - Memory used in MB

**Example:**

```php
use Rappasoft\LaravelPatches\Events\PatchExecuted;

Event::listen(PatchExecuted::class, function (PatchExecuted $event) {
    Log::info("Patch {$event->patch} completed in {$event->executionTime}ms");
    
    // Alert if patch took too long
    if ($event->executionTime > 30000) { // 30 seconds
        Slack::send("Slow patch detected: {$event->patch} took {$event->executionTime}ms");
    }
    
    // Log metrics to monitoring service
    Metrics::timing('patch.execution_time', $event->executionTime, [
        'patch' => $event->patch,
        'batch' => $event->batch,
    ]);
});
```

---

### PatchFailed

Dispatched when a patch throws an exception.

**Properties:**
- `string $patch` - The patch name
- `int $batch` - The batch number
- `Throwable $exception` - The exception that was thrown
- `int $executionTime` - Time before failure in milliseconds

**Example:**

```php
use Rappasoft\LaravelPatches\Events\PatchFailed;

Event::listen(PatchFailed::class, function (PatchFailed $event) {
    Log::error("Patch {$event->patch} failed", [
        'exception' => $event->exception->getMessage(),
        'trace' => $event->exception->getTraceAsString(),
        'batch' => $event->batch,
    ]);
    
    // Send urgent notification
    Notification::send($admins, new PatchFailedNotification($event));
    
    // Create rollback plan
    IssueTracker::create([
        'title' => "Patch failed: {$event->patch}",
        'description' => $event->exception->getMessage(),
        'severity' => 'critical',
    ]);
});
```

---

### PatchRollingBack

Dispatched before a patch's `down()` method runs.

**Properties:**
- `string $patch` - The patch name
- `object $instance` - The patch instance

**Example:**

```php
use Rappasoft\LaravelPatches\Events\PatchRollingBack;

Event::listen(PatchRollingBack::class, function (PatchRollingBack $event) {
    Log::warning("Rolling back patch: {$event->patch}");
    
    // Backup data if needed
    if ($event->instance instanceof CriticalPatch) {
        BackupService::createSnapshot();
    }
});
```

---

### PatchRolledBack

Dispatched after a patch successfully rolls back.

**Properties:**
- `string $patch` - The patch name
- `int $executionTime` - Rollback time in milliseconds

**Example:**

```php
use Rappasoft\LaravelPatches\Events\PatchRolledBack;

Event::listen(PatchRolledBack::class, function (PatchRolledBack $event) {
    Log::info("Rolled back patch: {$event->patch} in {$event->executionTime}ms");
    
    Cache::tags('patches')->flush();
});
```

---

## Registering Event Listeners

### In EventServiceProvider

```php
use Rappasoft\LaravelPatches\Events\{
    PatchExecuting,
    PatchExecuted,
    PatchFailed,
    PatchRollingBack,
    PatchRolledBack
};

protected $listen = [
    PatchExecuting::class => [
        LogPatchExecution::class,
        NotifyAdministrators::class,
    ],
    PatchExecuted::class => [
        RecordMetrics::class,
        ClearCache::class,
    ],
    PatchFailed::class => [
        AlertTeam::class,
        LogFailure::class,
    ],
];
```

### Using Closures

```php
// In AppServiceProvider boot() method
Event::listen(PatchExecuted::class, function ($event) {
    // Handle event
});
```

### Using Event Subscribers

```php
class PatchEventSubscriber
{
    public function handlePatchExecuting(PatchExecuting $event): void
    {
        // ...
    }

    public function handlePatchExecuted(PatchExecuted $event): void
    {
        // ...
    }

    public function handlePatchFailed(PatchFailed $event): void
    {
        // ...
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            PatchExecuting::class => 'handlePatchExecuting',
            PatchExecuted::class => 'handlePatchExecuted',
            PatchFailed::class => 'handlePatchFailed',
        ];
    }
}
```

---

## Common Use Cases

### 1. Performance Monitoring

```php
Event::listen(PatchExecuted::class, function ($event) {
    Metrics::histogram('patch.duration', $event->executionTime);
    Metrics::histogram('patch.memory', $event->memoryUsed);
});
```

### 2. Slack Notifications

```php
Event::listen(PatchExecuted::class, function ($event) {
    Slack::send("✅ Patch completed: {$event->patch} ({$event->executionTime}ms)");
});

Event::listen(PatchFailed::class, function ($event) {
    Slack::send("❌ Patch failed: {$event->patch}\n{$event->exception->getMessage()}");
});
```

### 3. Audit Logging

```php
Event::listen([PatchExecuted::class, PatchFailed::class], function ($event) {
    AuditLog::create([
        'action' => 'patch_executed',
        'patch' => $event->patch,
        'batch' => $event->batch,
        'status' => $event instanceof PatchFailed ? 'failed' : 'success',
        'user' => auth()->user()?->email,
        'timestamp' => now(),
    ]);
});
```

### 4. Cache Invalidation

```php
Event::listen(PatchExecuted::class, function ($event) {
    // Clear specific cache after certain patches
    if (str_contains($event->patch, 'cache')) {
        Cache::flush();
    }
});
```

### 5. Database Backups

```php
Event::listen(PatchExecuting::class, function ($event) {
    // Backup before critical patches
    if ($event->instance->isCritical()) {
        Artisan::call('snapshot:create');
    }
});
```

---

## Event Data Access

All events are serializable and can be queued:

```php
Event::listen(PatchExecuted::class, function ($event) {
    ProcessPatchMetrics::dispatch($event)->onQueue('metrics');
});
```

---

## Disabling Events

If you need to run patches without triggering events:

```php
Event::fake([
    PatchExecuting::class,
    PatchExecuted::class,
]);

Artisan::call('patch');
```

Or in tests:

```php
Event::fake();
// Run patches
Event::assertDispatched(PatchExecuted::class);
```
