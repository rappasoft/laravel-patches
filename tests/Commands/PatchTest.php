<?php

it('runs pending patches', function () {
    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    expect(\Rappasoft\LaravelPatches\Models\Patch::count())->toBe(0);

    $this->artisan('patch')->assertSuccessful();

    expect(\Rappasoft\LaravelPatches\Models\Patch::count())->toBe(1);

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'patch' => '2021_01_01_000000_my_first_patch',
        'batch' => 1,
        'log' => json_encode(['Hello First!']),
    ]);
});

it('increments the batch number normally', function () {
    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    $this->artisan('patch')->assertSuccessful();

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'patch' => '2021_01_01_000000_my_first_patch',
        'batch' => 1,
    ]);

    file_put_contents(
        database_path('patches/2021_01_02_000000_my_second_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_02_000000_my_second_patch.php')
    );

    $this->artisan('patch')->assertSuccessful();

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'patch' => '2021_01_02_000000_my_second_patch',
        'batch' => 2,
    ]);
});

it('assigns same batch to multiple patches run at the same time', function () {
    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    file_put_contents(
        database_path('patches/2021_01_02_000000_my_second_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_02_000000_my_second_patch.php')
    );

    $this->artisan('patch')->assertSuccessful();

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'id' => 1,
        'patch' => '2021_01_01_000000_my_first_patch',
        'batch' => 1,
    ]);

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'id' => 2,
        'patch' => '2021_01_02_000000_my_second_patch',
        'batch' => 1,
    ]);
});

it('increments the batch number by one if step is enabled', function () {
    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    file_put_contents(
        database_path('patches/2021_01_02_000000_my_second_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_02_000000_my_second_patch.php')
    );

    $this->artisan('patch', ['--step' => true])->assertSuccessful();

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'id' => 1,
        'patch' => '2021_01_01_000000_my_first_patch',
        'batch' => 1,
    ]);

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'id' => 2,
        'patch' => '2021_01_02_000000_my_second_patch',
        'batch' => 2,
    ]);
});

it('does not run patches when table does not exist', function () {
    // Drop the patches table
    \Illuminate\Support\Facades\Schema::dropIfExists(config('laravel-patches.table_name'));

    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    \Illuminate\Support\Facades\Artisan::call('patch');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain(__('The patches table does not exist, did you forget to migrate?'));

    // Recreate the table for other tests
    include_once __DIR__.'/../../database/migrations/create_patches_table.php.stub';
    (new \CreatePatchesTable())->up();
});

it('shows message when no patches to run', function () {
    \Illuminate\Support\Facades\Artisan::call('patch');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain(__('No patches to run.'));
});

it('displays patch execution time', function () {
    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    \Illuminate\Support\Facades\Artisan::call('patch');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('Patched:')
        ->toContain('2021_01_01_000000_my_first_patch')
        ->toContain('ms');
});

it('shows running message before executing patch', function () {
    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    \Illuminate\Support\Facades\Artisan::call('patch');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('Running Patch:')
        ->toContain('2021_01_01_000000_my_first_patch');
});

it('runs only pending patches', function () {
    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    // Run first time
    $this->artisan('patch')->assertSuccessful();
    expect(\Rappasoft\LaravelPatches\Models\Patch::count())->toBe(1);

    // Add second patch
    file_put_contents(
        database_path('patches/2021_01_02_000000_my_second_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_02_000000_my_second_patch.php')
    );

    // Run second time - should only run the new patch
    \Illuminate\Support\Facades\Artisan::call('patch');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('2021_01_02_000000_my_second_patch');

    expect(\Rappasoft\LaravelPatches\Models\Patch::count())->toBe(2);
});
