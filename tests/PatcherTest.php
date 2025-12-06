<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Rappasoft\LaravelPatches\Events\PatchExecuted;
use Rappasoft\LaravelPatches\Events\PatchExecuting;
use Rappasoft\LaravelPatches\Events\PatchFailed;
use Rappasoft\LaravelPatches\Events\PatchRolledBack;
use Rappasoft\LaravelPatches\Events\PatchRollingBack;
use Rappasoft\LaravelPatches\Patch;
use Rappasoft\LaravelPatches\Patcher;

beforeEach(function () {
    Event::fake();
});

test('runPatch returns result array with required keys', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
            $this->log('Test log');
        }
    };

    $result = $patcher->runPatch($patch, 'up', 'test_patch', 1);

    expect($result)->toHaveKeys(['log', 'executionTime', 'memoryUsed', 'exception'])
        ->and($result['log'])->toBe(['Test log'])
        ->and($result['executionTime'])->toBeGreaterThanOrEqual(0)
        ->and($result['memoryUsed'])->toBeGreaterThanOrEqual(0.0)
        ->and($result['exception'])->toBeNull();
});

test('runPatch returns null log when method does not exist', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
        }
    };

    $result = $patcher->runPatch($patch, 'nonexistent');

    expect($result['log'])->toBeNull()
        ->and($result['executionTime'])->toBe(0)
        ->and($result['memoryUsed'])->toBe(0.0)
        ->and($result['exception'])->toBeNull();
});

test('runPatch dispatches PatchExecuting event for up method', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
        }
    };

    $patcher->runPatch($patch, 'up', 'test_patch', 1);

    Event::assertDispatched(PatchExecuting::class, function ($event) use ($patch) {
        return $event->patch === 'test_patch'
            && $event->batch === 1
            && $event->instance === $patch;
    });
});

test('runPatch dispatches PatchExecuted event on success', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
            $this->log('Success');
        }
    };

    $patcher->runPatch($patch, 'up', 'test_patch', 1);

    Event::assertDispatched(PatchExecuted::class, function ($event) {
        return $event->patch === 'test_patch'
            && $event->batch === 1
            && $event->log === ['Success']
            && $event->executionTime >= 0
            && $event->memoryUsed >= 0;
    });
});

test('runPatch dispatches PatchFailed event on exception', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
            throw new \Exception('Test error');
        }
    };

    $result = $patcher->runPatch($patch, 'up', 'test_patch', 1);

    Event::assertDispatched(PatchFailed::class, function ($event) {
        return $event->patch === 'test_patch'
            && $event->batch === 1
            && $event->exception->getMessage() === 'Test error';
    });

    expect($result['exception'])->toBeInstanceOf(\Exception::class);
});

test('runPatch captures exception without re-throwing', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
            throw new \RuntimeException('Error');
        }
    };

    $result = $patcher->runPatch($patch, 'up', 'test_patch', 1);

    expect($result['exception'])->toBeInstanceOf(\RuntimeException::class)
        ->and($result['exception']->getMessage())->toBe('Error')
        ->and($result['log'])->toBe([]);
});

test('runPatch dispatches PatchRollingBack for down method', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function down()
        {
        }
    };

    $patcher->runPatch($patch, 'down', 'test_patch');

    Event::assertDispatched(PatchRollingBack::class, function ($event) use ($patch) {
        return $event->patch === 'test_patch'
            && $event->instance === $patch;
    });
});

test('runPatch dispatches PatchRolledBack after successful down', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function down()
        {
        }
    };

    $patcher->runPatch($patch, 'down', 'test_patch', 1);

    Event::assertDispatched(PatchRolledBack::class, function ($event) {
        return $event->patch === 'test_patch'
            && $event->executionTime >= 0;
    });
});

test('runPatch measures execution time accurately', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
            usleep(10000); // 10ms
        }
    };

    $result = $patcher->runPatch($patch, 'up');

    expect($result['executionTime'])->toBeGreaterThanOrEqual(5) // Allow for timing variance
        ->and($result['executionTime'])->toBeLessThan(1000); // Less than 1 second
});

test('runPatch tracks memory usage', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
            // Allocate some memory
            $data = array_fill(0, 1000, 'test');
        }
    };

    $result = $patcher->runPatch($patch, 'up');

    expect($result['memoryUsed'])->toBeGreaterThanOrEqual(0.0);
});

test('runPatch uses transaction when patch has useTransaction true', function () {
    config(['laravel-patches.use_transactions' => false]);
    
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        protected bool $useTransaction = true;
        
        public function up()
        {
            DB::table('patches')->insert(['patch' => 'test', 'batch' => 99, 'ran_on' => now()]);

            throw new \Exception('Rollback test');
        }
    };

    $result = $patcher->runPatch($patch, 'up');

    // Transaction should have rolled back
    expect(DB::table('patches')->where('batch', 99)->exists())->toBeFalse();
});

test('runPatch does not use transaction when useTransaction is false', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        protected bool $useTransaction = false;
        
        public function up()
        {
            DB::table('patches')->insert(['patch' => 'test', 'batch' => 88, 'ran_on' => now()]);
        }
    };

    $result = $patcher->runPatch($patch, 'up');

    // Should persist without transaction
    expect(DB::table('patches')->where('batch', 88)->exists())->toBeTrue();
});

test('runPatch uses global config for transactions', function () {
    config(['laravel-patches.use_transactions' => true]);
    
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
            DB::table('patches')->insert(['patch' => 'test', 'batch' => 77, 'ran_on' => now()]);

            throw new \Exception('Test');
        }
    };

    $result = $patcher->runPatch($patch, 'up');

    // Config says use transactions, should rollback
    expect(DB::table('patches')->where('batch', 77)->exists())->toBeFalse();
});

test('runPatch patch-level transaction overrides config', function () {
    config(['laravel-patches.use_transactions' => true]);
    
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        protected bool $useTransaction = false; // Override
        
        public function up()
        {
            DB::table('patches')->insert(['patch' => 'test', 'batch' => 66, 'ran_on' => now()]);
            // No exception, should persist
        }
    };

    $result = $patcher->runPatch($patch, 'up');

    expect(DB::table('patches')->where('batch', 66)->exists())->toBeTrue();
});

test('shouldUseTransaction checks patch property first', function () {
    $patcher = new Patcher(new Filesystem());
    $reflection = new \ReflectionClass($patcher);
    $method = $reflection->getMethod('shouldUseTransaction');
    $method->setAccessible(true);

    $patch = new class extends Patch {
        protected bool $useTransaction = true;
    };

    config(['laravel-patches.use_transactions' => false]);

    expect($method->invoke($patcher, $patch))->toBeTrue();
});

test('shouldUseTransaction falls back to config', function () {
    $patcher = new Patcher(new Filesystem());
    $reflection = new \ReflectionClass($patcher);
    $method = $reflection->getMethod('shouldUseTransaction');
    $method->setAccessible(true);

    $patch = new class extends Patch {};

    config(['laravel-patches.use_transactions' => true]);

    expect($method->invoke($patcher, $patch))->toBeTrue();
});

test('shouldUseTransaction defaults to false', function () {
    $patcher = new Patcher(new Filesystem());
    $reflection = new \ReflectionClass($patcher);
    $method = $reflection->getMethod('shouldUseTransaction');
    $method->setAccessible(true);

    $patch = new class extends Patch {};

    config(['laravel-patches.use_transactions' => null]);

    expect($method->invoke($patcher, $patch))->toBeFalse();
});

test('runPatch handles patch with logs before exception', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
            $this->log('Step 1 complete');
            $this->log('Step 2 complete');

            throw new \Exception('Step 3 failed');
        }
    };

    $result = $patcher->runPatch($patch, 'up');

    expect($result['log'])->toBe(['Step 1 complete', 'Step 2 complete'])
        ->and($result['exception'])->toBeInstanceOf(\Exception::class);
});

test('runPatch does not dispatch events when name or batch not provided', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
        }
    };

    // Call without name and batch
    $patcher->runPatch($patch, 'up');

    Event::assertNotDispatched(PatchExecuting::class);
    Event::assertNotDispatched(PatchExecuted::class);
});

test('runPatch rounds memory to 2 decimal places', function () {
    $patcher = new Patcher(new Filesystem());
    $patch = new class extends Patch {
        public function up()
        {
        }
    };

    $result = $patcher->runPatch($patch, 'up');

    expect($result)->toBeArray();
    
    // Check memory is rounded
    $memoryString = (string) $result['memoryUsed'];
    if (strpos($memoryString, '.') !== false) {
        $decimals = strlen(substr($memoryString, strpos($memoryString, '.') + 1));
        expect($decimals)->toBeLessThanOrEqual(2);
    }
});
