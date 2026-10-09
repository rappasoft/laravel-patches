<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Rappasoft\LaravelPatches\Events\PatchFailed;
use Rappasoft\LaravelPatches\Events\PatchRolledBack;
use Rappasoft\LaravelPatches\Events\PatchRollingBack;
use Rappasoft\LaravelPatches\Models\Patch;

beforeEach(fn () => Log::swap(\Mockery::mock(Log::getFacadeRoot())->makePartial()));

it('preserves the patch record and stops when rollback fails', function () {
    Event::fake([PatchFailed::class, PatchRolledBack::class, PatchRollingBack::class]);
    config(['laravel-patches.use_transactions' => true, 'laravel-patches.stop_on_error' => false]);

    file_put_contents(
        database_path('patches/2026_10_09_000000_rollback_failure.php'),
        '<?php
        class RollbackFailure extends \Rappasoft\LaravelPatches\Patch {
            public function up() {}
            public function down() {
                \Illuminate\Support\Facades\DB::table("patches")->update(["log" => "[\"partial rollback\"]"]);
                throw new \RuntimeException("Rollback failed");
            }
        }'
    );

    $this->artisan('patch')->assertSuccessful();

    expect(fn () => \Illuminate\Support\Facades\Artisan::call('patch:rollback'))->toThrow('Rollback failed');
    expect(Patch::count())->toBe(1)
        ->and(Patch::first()->log)->toBe([])
        ->and(Patch::first()->status)->toBe('success');
    Event::assertDispatched(PatchFailed::class, fn ($event) => $event->patch === '2026_10_09_000000_rollback_failure' && $event->batch === 1);
    Event::assertNotDispatched(PatchRolledBack::class);
});

it('dispatches completion events after a successful rollback', function () {
    Event::fake([PatchRolledBack::class, PatchRollingBack::class]);
    file_put_contents(
        database_path('patches/2026_10_09_000000_rollback_events.php'),
        '<?php
        class RollbackEvents extends \Rappasoft\LaravelPatches\Patch {
            public function up() {}
            public function down() {}
        }'
    );

    $this->artisan('patch')->assertSuccessful();
    $this->artisan('patch:rollback')->assertSuccessful();

    expect(Patch::count())->toBe(0);
    Event::assertDispatched(PatchRollingBack::class);
    Event::assertDispatched(PatchRolledBack::class, fn ($event) => $event->patch === '2026_10_09_000000_rollback_events');
});

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
