<?php

use Rappasoft\LaravelPatches\Models\Patch;

test('list command shows error when patches table does not exist', function () {
    \Illuminate\Support\Facades\Schema::drop('patches');
    
    $this->artisan('patch:list')
        ->expectsOutput('The patches table does not exist, did you forget to migrate?')
        ->assertFailed();
});

test('list command shows message when no patches exist', function () {
    $this->artisan('patch:list')
        ->expectsOutput('No patches found matching the criteria.')
        ->assertSuccessful();
});

test('list command displays all patches', function () {
    file_put_contents(database_path('patches/2024_01_01_000000_first.php'), '<?php class First {}');
    Patch::create([
        'patch' => '2024_01_01_000000_first',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
    ]);

    file_put_contents(
        database_path('patches/2024_01_02_000000_second.php'),
        '<?php class Second {}'
    );

    $this->artisan('patch:list')
        ->expectsOutputToContain('first')
        ->expectsOutputToContain('second')
        ->assertSuccessful();
});

test('list command filters by status pending', function () {
    file_put_contents(database_path('patches/2024_01_01_000000_ran.php'), '<?php class Ran {}');
    Patch::create([
        'patch' => '2024_01_01_000000_ran',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
    ]);

    file_put_contents(
        database_path('patches/2024_01_02_000000_pending.php'),
        '<?php class Pending {}'
    );

    $this->artisan('patch:list --status=pending')
        ->expectsOutputToContain('pending')
        ->doesntExpectOutputToContain('ran')
        ->assertSuccessful();
});

test('list command filters by status ran', function () {
    file_put_contents(database_path('patches/2024_01_01_000000_success.php'), '<?php class Success {}');
    Patch::create([
        'patch' => '2024_01_01_000000_success',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
    ]);

    file_put_contents(
        database_path('patches/2024_01_02_000000_pending.php'),
        '<?php class Pending {}'
    );

    $this->artisan('patch:list --status=ran')
        ->doesntExpectOutput('Total patches: 0');
});

test('list command filters by status failed', function () {
    file_put_contents(database_path('patches/2024_01_01_000000_failed.php'), '<?php class Failed {}');
    Patch::create([
        'patch' => '2024_01_01_000000_failed',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'failed',
    ]);

    file_put_contents(database_path('patches/2024_01_02_000000_success.php'), '<?php class Success2 {}');
    Patch::create([
        'patch' => '2024_01_02_000000_success',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
    ]);

    $this->artisan('patch:list --status=failed')
        ->expectsOutputToContain('failed')
        ->doesntExpectOutputToContain('success')
        ->assertSuccessful();
});

test('list command filters by batch number', function () {
    file_put_contents(database_path('patches/2024_01_01_000000_batch_1.php'), '<?php class Batch1 {}');
    Patch::create([
        'patch' => '2024_01_01_000000_batch_1',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
    ]);

    file_put_contents(database_path('patches/2024_01_02_000000_batch_2.php'), '<?php class Batch2 {}');
    Patch::create([
        'patch' => '2024_01_02_000000_batch_2',
        'batch' => 2,
        'ran_on' => now(),
        'status' => 'success',
    ]);

    $this->artisan('patch:list --batch=1')
        ->expectsOutputToContain('batch_1')
        ->doesntExpectOutputToContain('batch_2')
        ->assertSuccessful();
});

test('list command outputs JSON format', function () {
    file_put_contents(database_path('patches/2024_01_01_000000_test.php'), '<?php class Test {}');
    Patch::create([
        'patch' => '2024_01_01_000000_test',
        'batch' => 1,
        'ran_on' => $ranOn = now(),
        'status' => 'success',
        'execution_time_ms' => 150,
    ]);

    \Illuminate\Support\Facades\Artisan::call('patch:list', ['--json' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();

    // Verify it's valid JSON
    $json = json_decode($output, true);
    expect($json)->toBeArray()
        ->and($json[0])->toHaveKey('name')
        ->and($json[0])->toHaveKey('status')
        ->and($json[0])->toHaveKey('batch');
});

test('list command JSON output for pending patch', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_pending.php'),
        '<?php class Pending {}'
    );

    \Illuminate\Support\Facades\Artisan::call('patch:list', ['--json' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();

    $json = json_decode($output, true);
    expect($json[0]['status'])->toBe('pending')
        ->and($json[0]['batch'])->toBeNull()
        ->and($json[0]['ran_on'])->toBeNull();
});

test('list command shows empty JSON array when no patches', function () {
    \Illuminate\Support\Facades\Artisan::call('patch:list', ['--json' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();

    expect($output)->toContain('[]');
});

test('list command table format shows headers', function () {
    file_put_contents(database_path('patches/2024_01_01_000000_test.php'), '<?php class Test {}');
    Patch::create([
        'patch' => 'test',
        'batch' => 1,
        'ran_on' => now(),
        'status' => 'success',
    ]);

    \Illuminate\Support\Facades\Artisan::call('patch:list');
    $output = \Illuminate\Support\Facades\Artisan::output();

    expect($output)->toContain('Patch')
        ->toContain('Status')
        ->toContain('Batch')
        ->toContain('Ran On')
        ->toContain('Time (ms)');
});

test('list command shows total count', function () {
    file_put_contents(database_path('patches/patch_1.php'), '<?php class Patch1 {}');
    Patch::create(['patch' => 'patch_1', 'batch' => 1, 'ran_on' => now(), 'status' => 'success']);
    file_put_contents(database_path('patches/patch_2.php'), '<?php class Patch2 {}');
    Patch::create(['patch' => 'patch_2', 'batch' => 1, 'ran_on' => now(), 'status' => 'success']);

    $this->artisan('patch:list')
        ->expectsOutputToContain('Total patches: 2')
        ->assertSuccessful();
});

test('list command handles mixed status patches', function () {
    file_put_contents(database_path('patches/success.php'), '<?php class Success {}');
    Patch::create(['patch' => 'success', 'batch' => 1, 'ran_on' => now(), 'status' => 'success']);
    file_put_contents(database_path('patches/failed.php'), '<?php class Failed {}');
    Patch::create(['patch' => 'failed', 'batch' => 1, 'ran_on' => now(), 'status' => 'failed']);
    file_put_contents(database_path('patches/2024_01_03_000000_pending.php'), '<?php class Pending {}');

    $output = $this->artisan('patch:list')->run();
    
    expect($output)->toBe(0);
});

test('list command JSON includes all metadata fields', function () {
    file_put_contents(database_path('patches/full_metadata.php'), '<?php class FullMetadata {}');
    Patch::create([
        'patch' => 'full_metadata',
        'batch' => 5,
        'ran_on' => $ranOn = now(),
        'status' => 'success',
        'execution_time_ms' => 2500,
    ]);

    \Illuminate\Support\Facades\Artisan::call('patch:list', ['--json' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();

    $json = json_decode($output, true);
    expect($json[0])->toHaveKeys(['name', 'status', 'batch', 'ran_on', 'execution_time_ms']);
});

test('list command combines filters correctly', function () {
    file_put_contents(database_path('patches/batch1_success.php'), '<?php class Batch1Success {}');
    Patch::create(['patch' => 'batch1_success', 'batch' => 1, 'ran_on' => now(), 'status' => 'success']);
    file_put_contents(database_path('patches/batch1_failed.php'), '<?php class Batch1Failed {}');
    Patch::create(['patch' => 'batch1_failed', 'batch' => 1, 'ran_on' => now(), 'status' => 'failed']);
    file_put_contents(database_path('patches/batch2_success.php'), '<?php class Batch2Success {}');
    Patch::create(['patch' => 'batch2_success', 'batch' => 2, 'ran_on' => now(), 'status' => 'success']);

    $this->artisan('patch:list --batch=1 --status=success')
        ->expectsOutputToContain('batch1_success')
        ->doesntExpectOutputToContain('batch1_failed')
        ->doesntExpectOutputToContain('batch2_success')
        ->assertSuccessful();
});

test('list command shows dash for null values in table', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_pending.php'),
        '<?php class Pending {}'
    );

    $output = $this->artisan('patch:list')->run();
    
    expect($output)->toBe(0);
});
