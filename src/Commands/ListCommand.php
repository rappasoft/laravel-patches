<?php

namespace Rappasoft\LaravelPatches\Commands;

use Illuminate\Console\Command;
use Rappasoft\LaravelPatches\Models\Patch;
use Rappasoft\LaravelPatches\Patcher;
use Rappasoft\LaravelPatches\Repository;

class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'patch:list
                {--status= : Filter by status (pending|ran|failed)}
                {--batch= : Filter by batch number}
                {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available patches';

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
        $patches = [];

        foreach ($files as $name => $file) {
            $patchRecord = Patch::where('patch', $name)->first();

            $patchInfo = [
                'name' => $name,
                'status' => $patchRecord ? $patchRecord->status : 'pending',
                'batch' => $patchRecord?->batch,
                'ran_on' => $patchRecord?->ran_on?->toDateTimeString(),
                'execution_time_ms' => $patchRecord?->execution_time_ms,
            ];

            // Apply filters
            if ($this->option('status') && $patchInfo['status'] !== $this->option('status')) {
                continue;
            }

            if ($this->option('batch') && $patchInfo['batch'] != $this->option('batch')) {
                continue;
            }

            $patches[] = $patchInfo;
        }

        if (empty($patches)) {
            if ($this->option('json')) {
                $this->line(json_encode([], JSON_PRETTY_PRINT));
            } else {
                $this->info('No patches found matching the criteria.');
            }
            return 0;
        }

        // Output as JSON
        if ($this->option('json')) {
            $this->line(json_encode($patches, JSON_PRETTY_PRINT));
            return 0;
        }

        // Output as table
        $headers = ['Patch', 'Status', 'Batch', 'Ran On', 'Time (ms)'];
        $rows = [];

        foreach ($patches as $patch) {
            $status = match ($patch['status']) {
                'success' => '<fg=green>Success</>',
                'failed' => '<fg=red>Failed</>',
                default => '<fg=yellow>Pending</>',
            };

            $rows[] = [
                $patch['name'],
                $status,
                $patch['batch'] ?? '<fg=gray>-</>',
                $patch['ran_on'] ?? '-',
                $patch['execution_time_ms'] ?? '-',
            ];
        }

        $this->table($headers, $rows);

        $this->newLine();
        $this->info('Total patches: ' . count($patches));

        return 0;
    }
}
