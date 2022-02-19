<?php

namespace Rappasoft\LaravelPatches;

use Rappasoft\LaravelPatches\Commands\PatchCommand;
use Rappasoft\LaravelPatches\Commands\PatchMakeCommand;
use Rappasoft\LaravelPatches\Commands\RollbackCommand;
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
            ->hasMigration('create_patches_table')
            ->hasCommands([PatchMakeCommand::class, PatchCommand::class, RollbackCommand::class]);
    }
}
