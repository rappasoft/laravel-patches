<?php

use Rappasoft\LaravelPatches\Models\Patch;

it('has correct table name from config', function () {
    $patch = new Patch();

    expect($patch->getTable())->toBe(config('laravel-patches.table_name'))
        ->and($patch->getTable())->toBe('patches');
});

it('has timestamps disabled', function () {
    $patch = new Patch();

    expect($patch->timestamps)->toBeFalse();
});

it('has correct fillable attributes', function () {
    $patch = new Patch();

    expect($patch->getGuarded())->toBeEmpty();
});

it('casts log to array', function () {
    $patch = Patch::create([
        'patch' => 'test_patch',
        'batch' => 1,
        'log' => ['message 1', 'message 2'],
        'ran_on' => now(),
    ]);

    $retrieved = Patch::first();

    expect($retrieved->log)->toBeArray()
        ->and($retrieved->log)->toHaveCount(2)
        ->and($retrieved->log[0])->toBe('message 1');
});

it('casts ran_on to datetime', function () {
    $now = now();
    
    $patch = Patch::create([
        'patch' => 'test_patch',
        'batch' => 1,
        'ran_on' => $now,
    ]);

    $retrieved = Patch::first();

    expect($retrieved->ran_on)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($retrieved->ran_on->format('Y-m-d H:i:s'))->toBe($now->format('Y-m-d H:i:s'));
});

it('can store null log', function () {
    $patch = Patch::create([
        'patch' => 'test_patch',
        'batch' => 1,
        'log' => null,
        'ran_on' => now(),
    ]);

    $retrieved = Patch::first();

    expect($retrieved->log)->toBeNull();
});

it('creates patch with all attributes', function () {
    $now = now();
    
    Patch::create([
        'patch' => '2021_01_01_000000_my_patch',
        'batch' => 5,
        'log' => ['Step 1', 'Step 2'],
        'ran_on' => $now,
    ]);

    $this->assertDatabaseHas(config('laravel-patches.table_name'), [
        'patch' => '2021_01_01_000000_my_patch',
        'batch' => 5,
        'log' => json_encode(['Step 1', 'Step 2']),
    ]);
});

it('can be queried by batch number', function () {
    Patch::create(['patch' => 'patch_1', 'batch' => 1, 'ran_on' => now()]);
    Patch::create(['patch' => 'patch_2', 'batch' => 2, 'ran_on' => now()]);
    Patch::create(['patch' => 'patch_3', 'batch' => 2, 'ran_on' => now()]);

    $batchTwo = Patch::where('batch', 2)->get();

    expect($batchTwo)->toHaveCount(2);
});

it('can be ordered by patch name', function () {
    Patch::create(['patch' => 'c_patch', 'batch' => 1, 'ran_on' => now()]);
    Patch::create(['patch' => 'a_patch', 'batch' => 1, 'ran_on' => now()]);
    Patch::create(['patch' => 'b_patch', 'batch' => 1, 'ran_on' => now()]);

    $ordered = Patch::orderBy('patch')->pluck('patch')->toArray();

    expect($ordered)->toMatchArray(['a_patch', 'b_patch', 'c_patch']);
});
