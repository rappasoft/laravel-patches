<?php

namespace Rappasoft\LaravelPatches\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Orchestra\Testbench\TestCase as Orchestra;
use Rappasoft\LaravelPatches\LaravelPatchesServiceProvider;

class TestCase extends Orchestra
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();

        $this->clearPatches();
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return string[]
     */
    protected function getPackageProviders($app)
    {
        return [
            LaravelPatchesServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        include_once __DIR__.'/../database/migrations/create_patches_table.php.stub';
        (new \CreatePatchesTable())->up();
    }

    /**
     * Clear the database/patches folder in Orchestra
     */
    public function clearPatches(): void
    {
        foreach (glob(database_path('patches').'/*') as $file) {
            unlink($file);
        }
    }
}
