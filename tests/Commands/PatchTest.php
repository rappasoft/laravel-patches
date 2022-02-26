<?php

namespace Rappasoft\LaravelPatches\Tests\Commands;

use Rappasoft\LaravelPatches\Tests\TestCase;

class PatchTest extends TestCase
{
    /** @test */
    public function it_runs_pending_patches()
    {
        file_put_contents(
            database_path('patches/2021_01_01_000000_my_first_patch.php'),
            file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
        );

        $this->assertDatabaseCount(config('laravel-patches.table_name'), 0);

        $this->artisan('patch')->run();

        $this->assertDatabaseCount(config('laravel-patches.table_name'), 1);

        $this->assertDatabaseHas(config('laravel-patches.table_name'), [
            'patch' => '2021_01_01_000000_my_first_patch',
            'batch' => 1,
            'log' => json_encode(['Hello First!']),
        ]);
    }

    /** @test */
    public function it_increments_the_batch_number_normally()
    {
        file_put_contents(
            database_path('patches/2021_01_01_000000_my_first_patch.php'),
            file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
        );

        $this->artisan('patch')->run();

        $this->assertDatabaseHas(config('laravel-patches.table_name'), [
            'patch' => '2021_01_01_000000_my_first_patch',
            'batch' => 1,
        ]);

        file_put_contents(
            database_path('patches/2021_01_02_000000_my_second_patch.php'),
            file_get_contents(__DIR__.'/patches/2021_01_02_000000_my_second_patch.php')
        );

        $this->artisan('patch')->run();

        $this->assertDatabaseHas(config('laravel-patches.table_name'), [
            'patch' => '2021_01_02_000000_my_second_patch',
            'batch' => 2,
        ]);
    }

    /** @test */
    public function multiple_patches_have_the_same_batch_if_run_at_the_same_time()
    {
        file_put_contents(
            database_path('patches/2021_01_01_000000_my_first_patch.php'),
            file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
        );

        file_put_contents(
            database_path('patches/2021_01_02_000000_my_second_patch.php'),
            file_get_contents(__DIR__.'/patches/2021_01_02_000000_my_second_patch.php')
        );

        $this->artisan('patch')->run();

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
    }

    /** @test */
    public function it_increments_the_batch_number_by_one_if_step_is_enabled()
    {
        file_put_contents(
            database_path('patches/2021_01_01_000000_my_first_patch.php'),
            file_get_contents(__DIR__.'/patches/2021_01_01_000000_my_first_patch.php')
        );

        file_put_contents(
            database_path('patches/2021_01_02_000000_my_second_patch.php'),
            file_get_contents(__DIR__.'/patches/2021_01_02_000000_my_second_patch.php')
        );

        $this->artisan('patch', ['--step' => true])->run();

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
    }
}
