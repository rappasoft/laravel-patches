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
                {--step : Force the patches to be run so they can be rolled back individually}';

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

        if (! $this->patcher->setOutput($this->output)->patchesTableExists()) {
            return 1;
        }

        $files = $this->patcher->getPatchFiles($this->patcher->getPatchPaths());

        $this->patcher->requireFiles($patches = $this->pendingPatches($files, $this->repository->getRan()));

        $this->runPending($patches);

        return 0;
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

        $startTime = microtime(true);

        $log = $this->patcher->runPatch($patch, 'up');

        $runTime = number_format((microtime(true) - $startTime) * 1000, 2);

        $this->repository->log($name, $batch, $log);

        $this->line("<info>Patched:</info> {$name} ({$runTime}ms)");
    }
}
