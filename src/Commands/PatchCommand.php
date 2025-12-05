<?php

namespace Rappasoft\LaravelPatches\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Rappasoft\LaravelPatches\Patcher;
use Rappasoft\LaravelPatches\Repository;

/**
 * Class PatchCommand
 *
 * @package Rappasoft\LaravelPatches\Commands
 */
class PatchCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patch
                {--force : Force the operation to run when in production}
                {--step : Force the patches to be run so they can be rolled back individually}
                {--dry-run : Preview patches without executing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run any necessary patches';

    /**
     * The patcher instance.
     *
     * @var Patcher
     */
    protected Patcher $patcher;

    /**
     * The repository instance.
     *
     * @var Repository
     */
    protected Repository $repository;

    /**
     * PatchCommand constructor.
     *
     * @param  Patcher  $patcher
     * @param  Repository  $repository
     */
    public function __construct(Patcher $patcher, Repository $repository)
    {
        parent::__construct();

        $this->patcher = $patcher;
        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws FileNotFoundException
     */
    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return 1;
        }

        if (! $this->patcher->patchesTableExists()) {
            $this->error(__('The patches table does not exist, did you forget to migrate?'));

            return 1;
        }

        $files = $this->patcher->getPatchFiles($this->patcher->getPatchPaths());

        $this->patcher->requireFiles($patches = $this->pendingPatches($files, $this->repository->getRan()));

        // Handle dry run mode
        if ($this->option('dry-run')) {
            $this->handleDryRun($patches);
            return 0;
        }

        $this->runPending($patches);

        return 0;
    }

    /**
     * Handle dry run mode
     *
     * @param  array  $patches
     */
    protected function handleDryRun(array $patches): void
    {
        if (! count($patches)) {
            $this->info(__('No patches to run.'));
            return;
        }

        $this->line("<fg=cyan>Dry run mode - No patches will be executed</>");
        $this->newLine();

        foreach ($patches as $file) {
            $name = $this->patcher->getPatchName($file);
            $this->line("<comment>Would run:</comment> {$name}");
        }

        $this->newLine();
        $this->info("Total patches: " . count($patches));
    }

    /**
     * Get the patch files that have not yet run.
     *
     * @param  array  $files
     * @param  array  $ran
     *
     * @return array
     */
    protected function pendingPatches(array $files, array $ran): array
    {
        return collect($files)
            ->reject(fn ($file) => in_array($this->patcher->getPatchName($file), $ran, true))
            ->values()->all();
    }

    /**
     * Run pending patches
     *
     * @param  array  $patches
     */
    protected function runPending(array $patches): void
    {
        if (! count($patches)) {
            $this->info(__('No patches to run.'));

            return;
        }

        $batch = $this->repository->getNextBatchNumber();

        foreach ($patches as $file) {
            $this->runUp($file, $batch);

            if ($this->option('step')) {
                $batch++;
            }
        }
    }

    /**
     * Run the up method on the patch
     *
     * @param $file
     * @param  int  $batch
     */
    protected function runUp($file, int $batch): void
    {
        $patch = $this->patcher->resolve($name = $this->patcher->getPatchName($file));

        $this->line("<comment>Running Patch:</comment> {$name}");

        $result = $this->patcher->runPatch($patch, 'up', $name, $batch);

        $runTime = number_format($result['executionTime'], 2);

        if ($result['exception']) {
            $this->repository->log(
                $name,
                $batch,
                $result['log'] ?? [],
                $result['executionTime'],
                $result['memoryUsed'],
                null,
                null,
                'failed',
                $result['exception']->getMessage(),
                $result['exception']->getTraceAsString()
            );

            $this->error("<fg=red>Failed:</> {$name} ({$runTime}ms)");
            $this->error("  Error: {$result['exception']->getMessage()}");

            if (config('laravel-patches.stop_on_error', true)) {
                throw $result['exception'];
            }
        } else {
            $this->repository->log(
                $name,
                $batch,
                $result['log'] ?? [],
                $result['executionTime'],
                $result['memoryUsed']
            );

            $this->line("<info>Patched:</info> {$name} ({$runTime}ms)");
        }
    }
}
