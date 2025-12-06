<?php

use Illuminate\Support\Facades\Log;

it('rollsback a patch', function () {
    Log::shouldReceive('info')->with('Goodbye First');

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
    ]);

    $this->artisan('patch:rollback')->assertSuccessful();

    expect(\Rappasoft\LaravelPatches\Models\Patch::count())->toBe(0);

    $this->assertDatabaseMissing(config('laravel-patches.table_name'), [
        'patch' => '2021_01_01_000000_my_first_patch',
        'batch' => 1,
    ]);
});

it('rollsback all patches of the previous batch', function () {
    Log::shouldReceive('info')->with('Goodbye First');
    Log::shouldReceive('info')->with('Goodbye Second');

    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    file_put_contents(
        database_path('patches/2021_01_02_000000_my_second_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_02_000000_my_second_patch.php')
    );

    $this->artisan('patch')->assertSuccessful();

    expect(\Rappasoft\LaravelPatches\Models\Patch::count())->toBe(2);

    $this->artisan('patch:rollback')->assertSuccessful();

    expect(\Rappasoft\LaravelPatches\Models\Patch::count())->toBe(0);
});

it('rollsback the correct patches with step', function () {
    Log::shouldReceive('info')->once();

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
        'patch' => '2021_01_01_000000_my_first_patch',
        'batch' => 1,
    ]);

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'patch' => '2021_01_02_000000_my_second_patch',
        'batch' => 1,
    ]);

    $this->artisan('patch:rollback', ['--step' => 1])->assertSuccessful();

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'patch' => '2021_01_01_000000_my_first_patch',
        'batch' => 1,
    ]);

    $this->assertDatabaseMissing(config('laravel-patches.table_name'), [
        'patch' => '2021_01_02_000000_my_second_patch',
        'batch' => 1,
    ]);
});

it('shows message when nothing to rollback', function () {
    \Illuminate\Support\Facades\Artisan::call('patch:rollback');
    $output = \Illuminate\Support\Facades\Artisan::output();
    expect($output)->toContain('Nothing to rollback.');
});

it('displays rollback execution time', function () {
    Log::shouldReceive('info')->with('Goodbye First');

    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    $this->artisan('patch')->assertSuccessful();

    \Illuminate\Support\Facades\Artisan::call('patch:rollback');
    $output = \Illuminate\Support\Facades\Artisan::output();
    
    expect($output)->toContain('Rolled back:')
        ->toContain('2021_01_01_000000_my_first_patch')
        ->toContain('ms');
});

it('shows rolling back message before executing rollback', function () {
    Log::shouldReceive('info')->with('Goodbye First');

    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    $this->artisan('patch')->assertSuccessful();

    \Illuminate\Support\Facades\Artisan::call('patch:rollback');
    $output = \Illuminate\Support\Facades\Artisan::output();
    
    expect($output)->toContain('Rolling back:')
        ->toContain('2021_01_01_000000_my_first_patch');
});

it('handles missing patch file during rollback', function () {
    // Manually insert a patch record
    \Rappasoft\LaravelPatches\Models\Patch::create([
        'patch' => '2021_01_01_000000_missing_patch',
        'batch' => 1,
        'ran_on' => now(),
    ]);

    \Illuminate\Support\Facades\Artisan::call('patch:rollback');
    $output = \Illuminate\Support\Facades\Artisan::output();
    
    expect($output)->toContain('Patch not found:')
        ->toContain('2021_01_01_000000_missing_patch');

    // Should still remove the database record
    expect(\Rappasoft\LaravelPatches\Models\Patch::count())->toBe(1);
});

it('rollsback multiple steps correctly', function () {
    Log::shouldReceive('info')->times(2);

    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    file_put_contents(
        database_path('patches/2021_01_02_000000_my_second_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_02_000000_my_second_patch.php')
    );

    file_put_contents(
        database_path('patches/2021_01_03_000000_my_third_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_03_000000_my_third_patch.php')
    );

    $this->artisan('patch', ['--step' => true])->assertSuccessful();

    expect(\Rappasoft\LaravelPatches\Models\Patch::count())->toBe(3);

    // Rollback 2 steps
    $this->artisan('patch:rollback', ['--step' => 2])->assertSuccessful();

    expect(\Rappasoft\LaravelPatches\Models\Patch::count())->toBe(1);

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'patch' => '2021_01_01_000000_my_first_patch',
    ]);
});

it('rollsback patches in reverse order', function () {
    Log::shouldReceive('info')->with('Goodbye Second')->once()->ordered();
    Log::shouldReceive('info')->with('Goodbye First')->once()->ordered();

    file_put_contents(
        database_path('patches/2021_01_01_000000_my_first_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
    );

    file_put_contents(
        database_path('patches/2021_01_02_000000_my_second_patch.php'),
        file_get_contents(__DIR__.'/patches/2021_01_02_000000_my_second_patch.php')
    );

    $this->artisan('patch')->assertSuccessful();
    $this->artisan('patch:rollback')->assertSuccessful();
});
