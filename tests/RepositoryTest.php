<?php

use Rappasoft\LaravelPatches\Models\Patch;
use Rappasoft\LaravelPatches\Repository;

test('log stores patch with basic data', function () {
    $repository = new Repository();
    $repository->log('test_patch', 1, ['Log entry']);

    expect(Patch::count())->toBe(1);
    
    $patch = Patch::first();
    expect($patch->patch)->toBe('test_patch')
        ->and($patch->batch)->toBe(1)
        ->and($patch->log)->toBe(['Log entry'])
        ->and($patch->status)->toBe('success')
        ->and($patch->ran_on)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('log stores execution time metadata', function () {
    $repository = new Repository();
    $repository->log('test_patch', 1, [], 1500);

    $patch = Patch::first();
    expect($patch->execution_time_ms)->toBe(1500);
});

test('log stores memory usage metadata', function () {
    $repository = new Repository();
    $repository->log('test_patch', 1, [], null, 12.5);

    $patch = Patch::first();
    expect($patch->memory_used_mb)->toBe(12.5);
});

test('log stores executed_by user', function () {
    $repository = new Repository();
    $repository->log('test_patch', 1, [], null, null, 'john@example.com');

    $patch = Patch::first();
    expect($patch->executed_by)->toBe('john@example.com');
});

test('log detects current user automatically', function () {
    $repository = new Repository();
    $repository->log('test_patch', 1, []);

    $patch = Patch::first();
    expect($patch->executed_by)->not()->toBeNull()
        ->and($patch->executed_by)->toContain('@'); // Should contain hostname
});

test('log stores environment', function () {
    $repository = new Repository();
    $repository->log('test_patch', 1, [], null, null, null, 'production');

    $patch = Patch::first();
    expect($patch->environment)->toBe('production');
});

test('log detects environment automatically', function () {
    $repository = new Repository();
    $repository->log('test_patch', 1, []);

    $patch = Patch::first();
    expect($patch->environment)->toBe('testing'); // In tests
});

test('log stores failed status with error message', function () {
    $repository = new Repository();
    $repository->log(
        'test_patch',
        1,
        [],
        1000,
        5.0,
        null,
        null,
        'failed',
        'Database connection error',
        'Stack trace here'
    );

    $patch = Patch::first();
    expect($patch->status)->toBe('failed')
        ->and($patch->error_message)->toBe('Database connection error')
        ->and($patch->error_trace)->toBe('Stack trace here');
});

test('log does not store error details for successful patches', function () {
    $repository = new Repository();
    $repository->log(
        'test_patch',
        1,
        [],
        1000,
        5.0,
        null,
        null,
        'success',
        'This should not be stored',
        'Neither should this'
    );

    $patch = Patch::first();
    expect($patch->status)->toBe('success')
        ->and($patch->error_message)->toBeNull()
        ->and($patch->error_trace)->toBeNull();
});

test('log respects track_metadata config', function () {
    config(['laravel-patches.track_metadata' => false]);
    
    $repository = new Repository();
    $repository->log('test_patch', 1, [], 1500, 12.5);

    $patch = Patch::first();
    expect($patch->execution_time_ms)->toBeNull()
        ->and($patch->environment)->toBeNull();
});

test('log respects track_memory config', function () {
    config(['laravel-patches.track_memory' => false]);
    
    $repository = new Repository();
    $repository->log('test_patch', 1, [], 1500, 12.5);

    $patch = Patch::first();
    expect($patch->memory_used_mb)->toBeNull();
});

test('log respects track_user config', function () {
    config(['laravel-patches.track_user' => false]);
    
    $repository = new Repository();
    $repository->log('test_patch', 1, []);

    $patch = Patch::first();
    expect($patch->executed_by)->toBeNull();
});

test('log respects log_errors config', function () {
    config(['laravel-patches.log_errors' => false]);
    
    $repository = new Repository();
    $repository->log(
        'test_patch',
        1,
        [],
        null,
        null,
        null,
        null,
        'failed',
        'Error message',
        'Stack trace'
    );

    $patch = Patch::first();
    expect($patch->error_message)->toBeNull()
        ->and($patch->error_trace)->toBeNull();
});

test('getCurrentUser returns console user with hostname', function () {
    $repository = new Repository();
    $reflection = new \ReflectionClass($repository);
    $method = $reflection->getMethod('getCurrentUser');
    $method->setAccessible(true);

    $user = $method->invoke($repository);

    expect($user)->toContain('@')
        ->and($user)->not()->toBe('guest');
});

test('log handles empty log array', function () {
    $repository = new Repository();
    $repository->log('test_patch', 1, []);

    $patch = Patch::first();
    expect($patch->log)->toBe([]);
});

test('log handles multiple log entries', function () {
    $repository = new Repository();
    $logs = ['Entry 1', 'Entry 2', 'Entry 3'];
    $repository->log('test_patch', 1, $logs);

    $patch = Patch::first();
    expect($patch->log)->toBe($logs)
        ->and($patch->log)->toHaveCount(3);
});

test('log with all metadata fields populated', function () {
    $repository = new Repository();
    $repository->log(
        'complete_patch',
        5,
        ['Log 1', 'Log 2'],
        2500,
        15.75,
        'admin@example.com',
        'production',
        'success'
    );

    $patch = Patch::first();
    expect($patch->patch)->toBe('complete_patch')
        ->and($patch->batch)->toBe(5)
        ->and($patch->log)->toBe(['Log 1', 'Log 2'])
        ->and($patch->execution_time_ms)->toBe(2500)
        ->and($patch->memory_used_mb)->toBe(15.75)
        ->and($patch->executed_by)->toBe('admin@example.com')
        ->and($patch->environment)->toBe('production')
        ->and($patch->status)->toBe('success')
        ->and($patch->error_message)->toBeNull()
        ->and($patch->error_trace)->toBeNull();
});

test('log creates multiple patches in same batch', function () {
    $repository = new Repository();
    $repository->log('patch_1', 1, []);
    $repository->log('patch_2', 1, []);
    $repository->log('patch_3', 1, []);

    expect(Patch::where('batch', 1)->count())->toBe(3);
});

test('log stores rolled_back status', function () {
    $repository = new Repository();
    $repository->log('test_patch', 1, [], null, null, null, null, 'rolled_back');

    $patch = Patch::first();
    expect($patch->status)->toBe('rolled_back');
});
