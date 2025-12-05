<?php

namespace Rappasoft\LaravelPatches;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Rappasoft\LaravelPatches\Events\PatchExecuted;
use Rappasoft\LaravelPatches\Events\PatchExecuting;
use Rappasoft\LaravelPatches\Events\PatchFailed;
use Rappasoft\LaravelPatches\Events\PatchRolledBack;
use Rappasoft\LaravelPatches\Events\PatchRollingBack;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

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
     * @param  string|null  $name
     * @param  int|null  $batch
     *
     * @return array{log: array|null, executionTime: int, memoryUsed: float, exception: Throwable|null}
     */
    public function runPatch(object $patch, string $method, ?string $name = null, ?int $batch = null): array
    {
        if (! method_exists($patch, $method)) {
            return [
                'log' => null,
                'executionTime' => 0,
                'memoryUsed' => 0.0,
                'exception' => null,
            ];
        }

        $startTime = microtime(true);
        $startMemory = memory_get_peak_usage(true);
        $exception = null;
        $log = null;

        // Dispatch executing event
        if ($name && $batch && $method === 'up') {
            event(new PatchExecuting($name, $batch, $patch));
        } elseif ($name && $method === 'down') {
            event(new PatchRollingBack($name, $patch));
        }

        try {
            // Determine if we should use transactions
            $useTransaction = $this->shouldUseTransaction($patch);

            if ($useTransaction) {
                DB::transaction(function () use ($patch, $method) {
                    $patch->{$method}();
                });
            } else {
                $patch->{$method}();
            }

            $log = $patch->log;
        } catch (Throwable $e) {
            $exception = $e;
            $log = $patch->log ?? [];
        }

        $executionTime = (int) ((microtime(true) - $startTime) * 1000);
        $memoryUsed = (memory_get_peak_usage(true) - $startMemory) / 1024 / 1024;

        // Dispatch completion events
        if ($name && $batch) {
            if ($exception) {
                event(new PatchFailed($name, $batch, $exception, $executionTime));
            } elseif ($method === 'up') {
                event(new PatchExecuted($name, $batch, $log, $executionTime, $memoryUsed));
            } elseif ($method === 'down') {
                event(new PatchRolledBack($name, $executionTime));
            }
        }

        return [
            'log' => $log,
            'executionTime' => $executionTime,
            'memoryUsed' => round($memoryUsed, 2),
            'exception' => $exception,
        ];
    }

    /**
     * Determine if the patch should use a transaction.
     *
     * @param  object  $patch
     *
     * @return bool
     */
    protected function shouldUseTransaction(object $patch): bool
    {
        // Check if patch has useTransaction property
        if (property_exists($patch, 'useTransaction')) {
            $rp = new \ReflectionProperty($patch, 'useTransaction');
            $rp->setAccessible(true);

            if ($rp->getValue($patch) !== null) {
                return $rp->getValue($patch);
            }
        }

        // Fall back to config
        return (bool) config('laravel-patches.use_transactions', false);
    }
}
