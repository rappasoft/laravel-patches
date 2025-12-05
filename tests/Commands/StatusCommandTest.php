<?php

use Rappasoft\LaravelPatches\Models\Patch;


test('status command shows error when patches table does not exist', function () {
    // Drop all tables to simulate fresh install
    \Illuminate\Support\Facades\Schema::drop('patches');
    
    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('The patches table does not exist, did you forget to migrate?');
});

test('status command shows message when no patches exist', function () {
    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('No patches found matching the criteria.');
});

test('status command displays ran patches', function () {
    Patch::create([
        'patch' => '2024_01_01_000000_ran_test',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
        'execution_time_ms' => 150,
    ]);

    file_put_contents(database_path('patches/2024_01_01_000000_ran_test.php'), '<?php class RanTest {}');





    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('2024_01_01_000000_ran_test')->toContain('Success');
});

test('status command displays pending patches', function () {
    // Create a patch file
    file_put_contents(
        database_path('patches/2024_01_01_000000_pending_patch.php'),
        '<?php class PendingPatch {}'
    );

    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('pending_patch')->toContain('Pending');
});

test('status command filters by --pending flag', function () {
    Patch::create([
        'patch' => '2024_01_01_000000_ran_patch',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
    ]);
    file_put_contents(database_path('patches/2024_01_01_000000_ran_patch.php'), '<?php class RanPatch {}');

    file_put_contents(
        database_path('patches/2024_01_02_000000_pending_patch.php'),
        '<?php class PendingPatch {}'
    );

    \Illuminate\Support\Facades\Artisan::call('patch:status', ['--pending' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('pending_patch')->not->toContain('ran_patch');
});

test('status command filters by --ran flag', function () {
    Patch::create([
        'patch' => '2024_01_01_000000_ran_patch',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
    ]);
    file_put_contents(database_path('patches/2024_01_01_000000_ran_patch.php'), '<?php class RanPatch {}');

    file_put_contents(
        database_path('patches/2024_01_02_000000_pending_patch.php'),
        '<?php class PendingPatch {}'
    );

    \Illuminate\Support\Facades\Artisan::call('patch:status', ['--ran' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('ran_patch')->not->toContain('pending_patch');
});

test('status command filters by batch number', function () {
    Patch::create([
        'patch' => '2024_01_01_000000_batch_1',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
    ]);
    file_put_contents(database_path('patches/2024_01_01_000000_batch_1.php'), '<?php class Batch1 {}');

    Patch::create([
        'patch' => '2024_01_02_000000_batch_2',
        'batch' => 2,
        'ran_on' => now(),
        'status' => 'success',
    ]);
    file_put_contents(database_path('patches/2024_01_02_000000_batch_2.php'), '<?php class Batch2 {}');

    \Illuminate\Support\Facades\Artisan::call('patch:status', ['--batch' => 1]);
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('batch_1')->not->toContain('batch_2');
});

test('status command shows execution time', function () {
    Patch::create([
        'patch' => '2024_01_01_000000_test',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
        'execution_time_ms' => 1500,
    ]);
    file_put_contents(database_path('patches/2024_01_01_000000_test.php'), '<?php class Test {}');

    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('1500');
});

test('status command shows failed patches in red', function () {
    Patch::create([
        'patch' => '2024_01_01_failed',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'failed',
        'error_message' => 'Test error',
    ]);
    file_put_contents(database_path('patches/2024_01_01_failed.php'), '<?php class Failed {}');

    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();
    
    expect($output)->toContain('failed');
});

test('status command shows summary statistics', function () {
    Patch::create(['patch' => '2024_01_01_000000_patch_1', 'batch' => 1, 'ran_on' => now(), 'status' => 'success']);
    Patch::create(['patch' => '2024_01_01_000000_patch_2', 'batch' => 1, 'ran_on' => now(), 'status' => 'success']);

    file_put_contents(database_path('patches/2024_01_01_000000_patch_1.php'), '<?php class Patch1 {}');
    file_put_contents(database_path('patches/2024_01_01_000000_patch_2.php'), '<?php class Patch2 {}');
    
    file_put_contents(database_path('patches/2024_01_03_000000_pending.php'), '<?php class Pending {}');

    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('Ran: 2')->toContain('Pending: 1')->toContain('Total: 3');
});

test('status command handles empty state gracefully', function () {
    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('No patches found matching the criteria.');
});

test('status command shows table headers', function () {
    Patch::create([
        'patch' => '2024_01_01_000000_test',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
    ]);
    file_put_contents(database_path('patches/2024_01_01_000000_test.php'), '<?php class Test {}');

    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();

    expect($output)->toContain('Status')
        ->toContain('Patch')
        ->toContain('Batch')
        ->toContain('Ran On');
});

test('status command formats timestamps correctly', function () {
    $now = now();
    Patch::create([
        'patch' => '2024_01_01_000000_test',
        'batch' => 1,
        'ran_on' => $now,
        'status' => 'success',
    ]);
    file_put_contents(database_path('patches/2024_01_01_000000_test.php'), '<?php class Test {}');

    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();

    expect($output)->toContain($now->format('Y-m-d'));
});

test('status command shows dashes for missing data', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_pending.php'),
        '<?php class Pending {}'
    );

    \Illuminate\Support\Facades\Artisan::call('patch:status');
    $output = \Illuminate\Support\Facades\Artisan::output();
    
    expect($output)->toContain('-');
});
