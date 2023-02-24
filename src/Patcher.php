<?php

namespace Rappasoft\LaravelPatches;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Patcher
 *
 * @package Rappasoft\LaravelPatches
 */
class Patcher
{
    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * @var OutputInterface
     */
    protected OutputInterface $output;

    /**
     * Patcher constructor.
     *
     * @param  Filesystem  $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Set the output implementation that should be used by the console.
     *
     * @param  OutputInterface  $output
     * @return $this
     */
    public function setOutput(OutputInterface $output): Patcher
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Make sure the patches table exists
     *
     * @return bool
     */
    public function patchesTableExists(): bool
    {
        return Schema::hasTable(config('laravel-patches.table_name'));
    }

    /**
     * Return the array of paths to look through for patches
     *
     * @return array
     */
    public function getPatchPaths(): array
    {
        return [$this->getPatchPath()];
    }

    /**
     * Get the path to the patch directory.
     *
     * @return string
     */
    public function getPatchPath(): string
    {
        return database_path('patches');
    }

    /**
     * @param $paths
     *
     * @return array
     */
    public function getPatchFiles($paths): array
    {
        return collect($paths)
            ->flatMap(fn ($path) => Str::endsWith($path, '.php') ? [$path] : $this->files->glob($path.'/*_*.php'))
            ->filter()
            ->values()
            ->keyBy(fn ($file) => $this->getPatchName($file))
            ->sortBy(fn ($_file, $key) => $key)
            ->all();
    }

    /**
     * Get the ClassName
     *
     * @param $name
     *
     * @return string
     */
    public function getClassName($name): string
    {
        return Str::studly($name);
    }

    /**
     * Get the name of the patch.
     *
     * @param  string  $path
     *
     * @return string
     */
    public function getPatchName(string $path): string
    {
        return str_replace('.php', '', basename($path));
    }

    /**
     * Require in all the patch files in a given path.
     *
     * @param  array  $files
     *
     * @return void
     * @throws FileNotFoundException
     */
    public function requireFiles(array $files): void
    {
        foreach ($files as $file) {
            $this->files->requireOnce($file);
        }
    }

    /**
     * Resolve a patch instance from a file.
     *
     * @param  string  $file
     *
     * @return object
     */
    public function resolve(string $file): object
    {
        $class = Str::studly(implode('_', array_slice(explode('_', $file), 4)));

        return new $class;
    }

    /**
     * Run the specified method on the patch
     *
     * @param  object  $patch
     * @param  string  $method
     *
     * @return array
     */
    public function runPatch(object $patch, string $method): ?array
    {
        if (method_exists($patch, $method)) {
            $patch->{$method}();

            return $patch->log;
        }

        return null;
    }
}
