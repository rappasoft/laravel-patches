---
title: Metadata & Advanced Features  
weight: 11
---

Laravel Patches automatically tracks comprehensive metadata about patch execution, providing valuable insights for monitoring and debugging.

## Tracked Metadata

Every patch execution automatically captures:

- **execution_time_ms** - Duration in milliseconds
- **memory_used_mb** - Peak memory usage
- **executed_by** - User or process that ran the patch
- **environment** - Application environment (production, staging, local)
- **status** - Execution status (success, failed, rolled_back)
- **error_message** - Exception message if failed
- **error_trace** - Full stack trace if failed
- **log** - Custom log messages from `$this->log()`
- **ran_on** - Timestamp of execution

## Configuration

Control metadata tracking in `config/laravel-patches.php`:

```php
return [
    'track_metadata' => env('PATCHES_TRACK_METADATA', true),
    'track_memory' => env('PATCHES_TRACK_MEMORY', true),
    'track_user' => env('PATCHES_TRACK_USER', true),
];
```

## Viewing Metadata

### Via Commands

```bash
# See all metadata in table view
php artisan patch:status

# Get JSON output for processing
php artisan patch:list --json
```

### Via Database

```php
use Rappasoft\LaravelPatches\Models\Patch;

$patches = Patch::where('status', 'success')
    ->orderBy('execution_time_ms', 'desc')
    ->get();

foreach ($patches as $patch) {
    echo "{$patch->patch}: {$patch->execution_time_ms}ms\n";
}
```

### Via Events

```php
use Rappasoft\LaravelPatches\Events\PatchExecuted;

Event::listen(PatchExecuted::class, function ($event) {
    Log::info('Patch Metrics', [
        'patch' => $event->patch,
        'time_ms' => $event->executionTime,
        'memory_mb' => $event->memoryUsed,
    ]);
});
```

## Patch Descriptions

Add descriptions to your patches for better documentation:

```php
use Rappasoft\LaravelPatches\Patch;

class UpdateUserEmails extends Patch
{
    public function description(): ?string
    {
        return 'Updates all user emails from old domain to new domain';
    }

    public function up()
    {
        // Patch logic
    }

    public function down()
    {
        // Rollback logic
    }
}
```

Descriptions appear in:
- `patch:status` command output
- Event payloads
- Error messages

## Performance Analysis

### Find Slow Patches

```php
$slowPatches = Patch::where('execution_time_ms', '>', 5000)
    ->orderByDesc('execution_time_ms')
    ->get();
```

### Memory-Heavy Patches

```php
$memoryHeavy = Patch::where('memory_used_mb', '>', 100)
    ->get();
```

### Failure Rate

```php
$totalPatches = Patch::count();
$failedPatches = Patch::where('status', 'failed')->count();
$failureRate = ($failedPatches / $totalPatches) * 100;
```

##  Monitoring Integration

### Send Metrics to External Service

```php
Event::listen(PatchExecuted::class, function ($event) {
    Metrics::timing('patch.execution', $event->executionTime, [
        'patch' => $event->patch,
        'environment' => app()->environment(),
    ]);

    Metrics::gauge('patch.memory', $event->memoryUsed);
});
```

### Alert on Anomalies

```php
Event::listen(PatchExecuted::class, function ($event) {
    $avgTime = Patch::where('patch', $event->patch)
        ->avg('execution_time_ms');

    if ($event->executionTime > ($avgTime * 2)) {
        Slack::send("⚠️ Slow patch detected: {$event->patch}");
    }
});
```

## Best Practices

1. **Review metadata regularly** to identify performance issues
2. **Add descriptions** to all production patches
3. **Monitor execution times** for degradation
4. **Set up alerts** for failed patches
5. **Keep metadata enabled** in production for troubleshooting
