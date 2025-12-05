<?php

namespace Rappasoft\LaravelPatches;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Class LaravelPatchesServiceProvider
 *
 * @package Rappasoft\LaravelPatches
 */
class LaravelPatchesServiceProvider extends PackageServiceProvider
{
    /**
     * @param  Package  $package
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-patches')
            ->hasConfigFile('laravel-patches')
            ->hasMigrations(['create_patches_table', 'add_metadata_to_patches_table'])
            ->hasCommands([
                Commands\PatchMakeCommand::class,
                Commands\PatchCommand::class,
                Commands\RollbackCommand::class,
                Commands\StatusCommand::class,
                Commands\ListCommand::class,
            ]);
    }
}
