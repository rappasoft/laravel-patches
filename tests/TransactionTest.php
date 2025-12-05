<?php

use Illuminate\Support\Facades\DB;

test('patch with useTransaction true rolls back on exception', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_transaction_test.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        use Illuminate\Support\Facades\DB;
        
        class TransactionTest extends Patch {
            protected bool $useTransaction = true;
            
            public function up() {
                DB::table("patches")->insert(["patch" => "rollback_test", "batch" => 88, "ran_on" => now()]);
                throw new \Exception("Force rollback");
            }
        }'
    );

    try {
        $this->artisan('patch');
    } catch (\Exception $e) {
        // Expected
    }

    expect(DB::table('patches')->where('batch', 88)->exists())->toBeFalse();
});

test('patch with useTransaction true commits on success', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_transaction_success.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        use Illuminate\Support\Facades\DB;
        
        class TransactionSuccess extends Patch {
            protected bool $useTransaction = true;
            
            public function up() {
                DB::table("patches")->insert(["patch" => "commit_test", "batch" => 77, "ran_on" => now()]);
            }
        }'
    );

    $this->artisan('patch')->assertSuccessful();

    expect(DB::table('patches')->where('batch', 77)->exists())->toBeTrue();
});

test('patch with useTransaction false persists changes even on error', function () {
    config(['laravel-patches.stop_on_error' => false]);
    
    file_put_contents(
        database_path('patches/2024_01_01_000000_no_transaction.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        use Illuminate\Support\Facades\DB;
        
        class NoTransaction extends Patch {
            protected bool $useTransaction = false;
            
            public function up() {
                DB::table("patches")->insert(["patch" => "persist_test", "batch" => 66, "ran_on" => now()]);
                throw new \Exception("Error but data persists");
            }
        }'
    );

    $this->artisan('patch');

    expect(DB::table('patches')->where('batch', 66)->exists())->toBeTrue();
});

test('global transaction config applies when patch has no override', function () {
    config(['laravel-patches.use_transactions' => true]);
    
    file_put_contents(
        database_path('patches/2024_01_01_000000_global_config.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        use Illuminate\Support\Facades\DB;
        
        class GlobalConfig extends Patch {
            public function up() {
                DB::table("patches")->insert(["patch" => "global_test", "batch" => 55, "ran_on" => now()]);
                throw new \Exception("Use global config");
            }
        }'
    );

    try {
        $this->artisan('patch');
    } catch (\Exception $e) {
        // Expected
    }

    expect(DB::table('patches')->where('batch', 55)->exists())->toBeFalse();
});

test('patch-level transaction overrides global config', function () {
    config(['laravel-patches.use_transactions' => true]);
    
    file_put_contents(
        database_path('patches/2024_01_01_000000_transaction_override_patch.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        use Illuminate\Support\Facades\DB;
        
        class TransactionOverridePatch extends Patch {
            protected bool $useTransaction = false; // Override global
            
            public function up() {
                DB::table("patches")->insert(["patch" => "override_test", "batch" => 44, "ran_on" => now()]);
            }
        }'
    );

    $this->artisan('patch')->assertSuccessful();

    expect(DB::table('patches')->where('batch', 44)->exists())->toBeTrue();
});

test('transaction wraps entire patch execution', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_full_transaction.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        use Illuminate\Support\Facades\DB;
        
        class FullTransaction extends Patch {
            protected bool $useTransaction = true;
            
            public function up() {
                DB::table("patches")->insert(["patch" => "first", "batch" => 33, "ran_on" => now()]);
                DB::table("patches")->insert(["patch" => "second", "batch" => 33, "ran_on" => now()]);
                DB::table("patches")->insert(["patch" => "third", "batch" => 33, "ran_on" => now()]);
                throw new \Exception("Rollback all");
            }
        }'
    );

    try {
        $this->artisan('patch');
    } catch (\Exception $e) {
        // Expected
    }

    expect(DB::table('patches')->where('batch', 33)->count())->toBe(0);
});

test('nested operations in transaction', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_nested.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        use Illuminate\Support\Facades\DB;
        
        class Nested extends Patch {
            protected bool $useTransaction = true;
            
            public function up() {
                DB::table("patches")->insert(["patch" => "outer", "batch" => 22, "ran_on" => now()]);
                
                DB::transaction(function() {
                    DB::table("patches")->insert(["patch" => "inner", "batch" => 22, "ran_on" => now()]);
                });
                
                throw new \Exception("Rollback everything");
            }
        }'
    );

    try {
        $this->artisan('patch');
    } catch (\Exception $e) {
        // Expected
    }

    expect(DB::table('patches')->where('batch', 22)->count())->toBe(0);
});

test('transaction does not affect other patches', function () {
    file_put_contents(
        database_path('patches/2024_01_01_000000_first_succeeds.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        class FirstSucceeds extends Patch {
            public function up() {
                $this->log("First success");
            }
        }'
    );

    file_put_contents(
        database_path('patches/2024_01_02_000000_second_fails.php'),
        '<?php
        use Rappasoft\LaravelPatches\Patch;
        use Illuminate\Support\Facades\DB;
        
        class SecondFails extends Patch {
            protected bool $useTransaction = true;
            
            public function up() {
                DB::table("patches")->insert(["patch" => "isolated", "batch" => 11, "ran_on" => now()]);
                throw new \Exception("Only this fails");
            }
        }'
    );

    config(['laravel-patches.stop_on_error' => false]);
    $this->artisan('patch');

    // First should succeed
    expect(\Rappasoft\LaravelPatches\Models\Patch::where('patch', '2024_01_01_000000_first_succeeds')->exists())->toBeTrue();
    
    // Second's transaction should have rolled back
    expect(DB::table('patches')->where('batch', 11)->exists())->toBeFalse();
});
