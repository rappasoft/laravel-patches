<?php

namespace Rappasoft\LaravelPatches\Commands;

use Illuminate\Console\Command;
use Rappasoft\LaravelPatches\Models\Patch;
use Rappasoft\LaravelPatches\Patcher;
use Rappasoft\LaravelPatches\Repository;

class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patch:status
                {--pending : Show only pending patches}
                {--ran : Show only ran patches}
                {--batch= : Filter by batch number}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the status of all patches';

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
     * Create a new command instance.
     */
    public function __construct(Patcher $patcher, Repository $repository)
    {
        parent::__construct();

        $this->patcher = $patcher;
        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->patcher->patchesTableExists()) {
            $this->error(__('The patches table does not exist, did you forget to migrate?'));
            return 1;
        }

        $files = $this->patcher->getPatchFiles($this->patcher->getPatchPaths());
        $ran = collect($this->repository->getRan());

        $headers = ['Status', 'Patch', 'Batch', 'Ran On', 'Time (ms)', 'Status'];
        $rows = [];

        foreach ($files as $name => $file) {
            $patchRecord = Patch::where('patch', $name)->first();

            if ($patchRecord) {
                // Filter by options
                if ($this->option('pending')) {
                    continue;
                }

                if ($this->option('batch') && $patchRecord->batch != $this->option('batch')) {
                    continue;
                }

                $status = $patchRecord->status === 'success' ? '<fg=green>✓</>' : '<fg=red>✗</>';
                $statusText = $patchRecord->status === 'success' ? '<fg=green>Success</>' : '<fg=red>Failed</>';

                $rows[] = [
                    $status,
                    $name,
                    $patchRecord->batch,
                    $patchRecord->ran_on ? $patchRecord->ran_on->format('Y-m-d H:i:s') : '-',
                    $patchRecord->execution_time_ms ?? '-',
                    $statusText,
                ];
            } else {
                // Pending patch
                if ($this->option('ran')) {
                    continue;
                }

                $rows[] = [
                    '<fg=yellow>⋯</>',
                    $name,
                    '<fg=gray>Pending</>',
                    '-',
                    '-',
                    '<fg=yellow>Pending</>',
                ];
            }
        }

        if (empty($rows)) {
            $this->info('No patches found matching the criteria.');
            return 0;
        }

        $this->table($headers, $rows);

        // Summary
        $totalRan = $ran->count();
        $totalPending = count($files) - $totalRan;

        $this->newLine();
        $this->line("<info>Ran:</info> {$totalRan}");
        $this->line("<comment>Pending:</comment> {$totalPending}");
        $this->line("Total: " . count($files));

        return 0;
    }
}
