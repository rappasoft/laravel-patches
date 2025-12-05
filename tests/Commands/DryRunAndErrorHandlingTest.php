<?php

use Rappasoft\LaravelPatches\Models\Patch;
use Illuminate\Support\Facades\Event;
use Rappasoft\LaravelPatches\Events\PatchExecuted;


test('dry run mode preview patches without executing', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_dry_run_test.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class DryRunTest extends Patch {
            public function up() {
                $this->log("This should not execute");
            }
        }'
    );

    $this->artisan('patch --dry-run')
        ->expectsOutputToContain('Dry run mode')
        ->expectsOutputToContain('Would run: 2024_01_01_000000_dry_run_test')
        ->assertSuccessful();

    // Verify it didn't actually run
    expect(Patch::where('patch', '2024_01_01_000000_dry_run_test')->exists())->toBeFalse();
});

test('dry run shows total patches count', function () {
    file_put_contents(database_path('patches/2024_01_01_000000_first.php'), '<?php class First {}');
    file_put_contents(database_path('patches/2024_01_02_000000_second.php'), '<?php class Second {}');

    $this->artisan('patch --dry-run')
        ->expectsOutputToContain('Total patches: 2')
        ->assertSuccessful();
});

test('dry run shows message when no pending patches', function () {
    $this->artisan('patch --dry-run')
        ->expectsOutput('No patches to run.')
        ->assertSuccessful();
});

test('dry run lists patches in order', function () {
    file_put_contents(database_path('patches/2024_01_03_000000_third.php'), '<?php class Third {}');
    file_put_contents(database_path('patches/2024_01_01_000000_first.php'), '<?php class First {}');
    file_put_contents(database_path('patches/2024_01_02_000000_second.php'), '<?php class Second {}');

    \Illuminate\Support\Facades\Artisan::call('patch', ['--dry-run' => true]);
    $output = \Illuminate\Support\Facades\Artisan::output();

    $firstPos = strpos($output, '2024_01_01_000000_first');
    $secondPos = strpos($output, '2024_01_02_000000_second');
    $thirdPos = strpos($output, '2024_01_03_000000_third');

    expect($firstPos)->toBeLessThan($secondPos)
        ->and($secondPos)->toBeLessThan($thirdPos);
});

test('patch command logs failed patches with error details', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_failing_patch.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class FailingPatch extends Patch {
            public function up() {
                throw new \Exception("Test failure");
            }
        }'
    );

    try {
        $this->artisan('patch');
    } catch (\Exception $e) {
        // Expected to fail
    }

    $patch = Patch::where('patch', '2024_01_01_000000_failing_patch')->first();
    
    expect($patch)->not()->toBeNull()
        ->and($patch->status)->toBe('failed')
        ->and($patch->error_message)->toBe('Test failure')
        ->and($patch->error_trace)->not()->toBeNull();
});

test('patch command shows error message on failure', function () {
    config(['laravel-patches.stop_on_error' => true]);
    
    file_put_contents(
        database_path('patches/2024_01_01_000000_error_patch.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class ErrorPatch extends Patch {
            public function up() {
                throw new \RuntimeException("Custom error");
            }
        }'
    );

    expect(fn() => $this->artisan('patch'))->toThrow('Custom error');
});

test('patch command continues on error when configured', function () {
    config(['laravel-patches.stop_on_error' => false]);
    
    file_put_contents(
        database_path('patches/2024_01_01_000000_fails.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class Fails extends Patch {
            public function up() {
                throw new \Exception("Error");
            }
        }'
    );

    file_put_contents(
        database_path('patches/2024_01_02_000000_succeeds.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class Succeeds extends Patch {
            public function up() {
                $this->log("Success");
            }
        }'
    );

    $this->artisan('patch')->assertSuccessful();

    expect(Patch::where('patch', '2024_01_01_000000_fails')->where('status', 'failed')->exists())->toBeTrue()
        ->and(Patch::where('patch', '2024_01_02_000000_succeeds')->where('status', 'success')->exists())->toBeTrue();
});

test('patch command stops on error by default', function () {
    config(['laravel-patches.stop_on_error' => true]);
    
    file_put_contents(
        database_path('patches/2024_01_01_000000_stops_on_error.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class StopsOnError extends Patch {
            public function up() {
                throw new \Exception("Stop here");
            }
        }'
    );

    file_put_contents(
        database_path('patches/2024_01_02_000000_should_not_run.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class ShouldNotRun extends Patch {
            public function up() {
                $this->log("Should not execute");
            }
        }'
    );

    expect(fn() => $this->artisan('patch'))->toThrow('Stop here');

    expect(Patch::where('patch', '2024_01_02_000000_should_not_run')->exists())->toBeFalse();
});

test('patch command tracks execution time', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_timed.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class Timed extends Patch {
            public function up() {
                usleep(10000); // 10ms
            }
        }'
    );

    $this->artisan('patch')->assertSuccessful();

    $patch = Patch::first();
    expect($patch->execution_time_ms)->toBeGreaterThan(0);
});

test('patch command tracks memory usage', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_memory.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class Memory extends Patch {
            public function up() {
                $data = array_fill(0, 1000, "test");
            }
        }'
    );

    $this->artisan('patch')->assertSuccessful();

    $patch = Patch::first();
    expect($patch->memory_used_mb)->toBeGreaterThanOrEqual(0.0);
});

test('patch command dispatches events', function () {
    Event::fake();
    
    file_put_contents(
        database_path('patches/2024_01_01_000000_events.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class Events extends Patch {
            public function up() {}
        }'
    );

    $this->artisan('patch')->assertSuccessful();

    Event::assertDispatched(PatchExecuted::class);
});

test('patch command respects transaction config', function () {
    config(['laravel-patches.use_transactions' => true]);
    
    file_put_contents(
        database_path('patches/2024_01_01_000000_transaction.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        use Illuminate\Support\Facades\DB;
        class Transaction extends Patch {
            public function up() {
                DB::table("patches")->insert(["patch" => "test", "batch" => 99, "ran_on" => now()]);
                throw new \Exception("Rollback");
            }
        }'
    );

    expect(fn() => $this->artisan('patch'))->toThrow('Rollback');

    expect(\Illuminate\Support\Facades\DB::table('patches')->where('batch', 99)->exists())->toBeFalse();
});
