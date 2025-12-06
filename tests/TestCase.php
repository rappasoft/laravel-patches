<?php

namespace Rappasoft\LaravelPatches\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Rappasoft\LaravelPatches\LaravelPatchesServiceProvider;

class TestCase extends Orchestra
{
    /**
     * The latest response returned by the application.
     *
     * @var \Illuminate\Testing\TestResponse|null
     */
    public static $latestResponse = null;

    protected function setUp(): void
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
    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        include_once __DIR__.'/../database/migrations/create_patches_table.php.stub';
        (new \CreatePatchesTable())->up();
        
        include_once __DIR__.'/../database/migrations/add_metadata_to_patches_table.php.stub';
        (new \AddMetadataToPatchesTable())->up();
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
