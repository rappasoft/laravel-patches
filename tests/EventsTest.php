<?php

use Illuminate\Support\Facades\Event;
use Rappasoft\LaravelPatches\Events\{PatchExecuting, PatchExecuted, PatchFailed, PatchRollingBack, PatchRolledBack};
use Rappasoft\LaravelPatches\Patch;


beforeEach(function () {
    Event::fake();
});

test('PatchExecuting event is dispatched with correct data', function () {
    $patch = new class extends Patch {
        public function up() {}
    };

    event(new PatchExecuting('test_patch', 1, $patch));

    Event::assertDispatched(PatchExecuting::class, function ($event) use ($patch) {
        return $event->patch === 'test_patch'
            && $event->batch === 1
            && $event->instance === $patch;
    });
});

test('PatchExecuted event is dispatched with metrics', function () {
    event(new PatchExecuted('test_patch', 1, ['log line 1'], 1500, 12.5));

    Event::assertDispatched(PatchExecuted::class, function ($event) {
        return $event->patch === 'test_patch'
            && $event->batch === 1
            && $event->log === ['log line 1']
            && $event->executionTime === 1500
            && $event->memoryUsed === 12.5;
    });
});

test('PatchExecuted event handles null log', function () {
    event(new PatchExecuted('test_patch', 1, null, 1000, 5.0));

    Event::assertDispatched(PatchExecuted::class, function ($event) {
        return $event->log === null;
    });
});

test('PatchFailed event is dispatched with exception', function () {
    $exception = new \Exception('Test error');

    event(new PatchFailed('test_patch', 1, $exception, 500));

    Event::assertDispatched(PatchFailed::class, function ($event) use ($exception) {
        return $event->patch === 'test_patch'
            && $event->batch === 1
            && $event->exception === $exception
            && $event->executionTime === 500;
    });
});

test('PatchRollingBack event is dispatched', function () {
    $patch = new class extends Patch {
        public function down() {}
    };

    event(new PatchRollingBack('test_patch', $patch));

    Event::assertDispatched(PatchRollingBack::class, function ($event) use ($patch) {
        return $event->patch === 'test_patch'
            && $event->instance === $patch;
    });
});

test('PatchRolledBack event is dispatched with timing', function () {
    event(new PatchRolledBack('test_patch', 750));

    Event::assertDispatched(PatchRolledBack::class, function ($event) {
        return $event->patch === 'test_patch'
            && $event->executionTime === 750;
    });
});



test('event listeners can be registered', function () {
    Event::fake();
    
    Event::listen(PatchExecuted::class, function ($event) {
        // Listener logic
    });

    event(new PatchExecuted('test', 1, [], 1000, 5.0));

    Event::assertListening(PatchExecuted::class, \Closure::class);
});

test('multiple events can be dispatched in sequence', function () {
    $patch = new class extends Patch {
        public function up() {}
    };

    event(new PatchExecuting('patch1', 1, $patch));
    event(new PatchExecuted('patch1', 1, [], 100, 1.0));
    event(new PatchExecuting('patch2', 1, $patch));
    event(new PatchExecuted('patch2', 1, [], 200, 2.0));

    Event::assertDispatched(PatchExecuting::class, 2);
    Event::assertDispatched(PatchExecuted::class, 2);
});

test('PatchFailed exception can be accessed', function () {
    $originalException = new \RuntimeException('Database error', 500);
    
    event(new PatchFailed('test_patch', 1, $originalException, 1000));

    Event::assertDispatched(PatchFailed::class, function ($event) use ($originalException) {
        return $event->exception->getMessage() === 'Database error'
            && $event->exception->getCode() === 500
            && $event->exception === $originalException;
    });
});
