<?php

namespace Rappasoft\LaravelPatches;

use Rappasoft\LaravelPatches\Models\Patch;

/**
 * Class Repository
 *
 * @package Rappasoft\LaravelPatches
 */
class Repository
{
    /**
     * Get list of patches.
     *
     * @param  int  $steps
     *
     * @return array
     */
    public function getPatches(int $steps): array
    {
        $query = Patch::where('batch', '>=', '1');

        return $query->orderBy('batch', 'desc')
            ->orderBy('patch', 'desc')
            ->take($steps)
            ->get()
            ->all();
    }

    /**
     * Get the list of patches already ran
     *
     * @return array
     */
    public function getRan(): array
    {
        return Patch::orderBy('batch')
            ->orderBy('patch')
            ->pluck('patch')
            ->all();
    }

    /**
     * Get the last patches batch.
     *
     * @return array
     */
    public function getLast(): array
    {
        $query = Patch::where('batch', $this->getLastBatchNumber());

        return $query->orderBy('patch', 'desc')
            ->get()
            ->all();
    }

    /**
     * Log that a patch was run.
     *
     * @param  string  $file
     * @param  int  $batch
     * @param  array  $log
     * @param  int|null  $executionTime
     * @param  float|null  $memoryUsed
     * @param  string|null  $executedBy
     * @param  string|null  $environment
     * @param  string  $status
     * @param  string|null  $errorMessage
     * @param  string|null  $errorTrace
     *
     * @return void
     */
    public function log(
        string $file,
        int $batch,
        array $log = [],
        ?int $executionTime = null,
        ?float $memoryUsed = null,
        ?string $executedBy = null,
        ?string $environment = null,
        string $status = 'success',
        ?string $errorMessage = null,
        ?string $errorTrace = null
    ): void {
        $data = [
            'patch' => $file,
            'batch' => $batch,
            'log' => $log,
            'ran_on' => now(),
            'status' => $status,
        ];

        if (config('laravel-patches.track_metadata', true)) {
            $data['execution_time_ms'] = $executionTime;
            $data['environment'] = $environment ?? app()->environment();
        }

        if (config('laravel-patches.track_memory', true)) {
            $data['memory_used_mb'] = $memoryUsed;
        }

        if (config('laravel-patches.track_user', true)) {
            $data['executed_by'] = $executedBy ?? $this->getCurrentUser();
        }

        if (config('laravel-patches.log_errors', true) && $status === 'failed') {
            $data['error_message'] = $errorMessage;
            $data['error_trace'] = $errorTrace;
        }

        Patch::create($data);
    }

    /**
     * Get the current user executing the patch.
     *
     * @return string
     */
    protected function getCurrentUser(): string
    {
        if (app()->runningInConsole()) {
            return get_current_user() . '@' . gethostname();
        }

        return auth()->check() ? auth()->user()->email ?? auth()->id() : 'guest';
    }

    /**
     * Delete a patch from the database
     *
     * @param  object  $patch
     */
    public function delete(object $patch): void
    {
        Patch::where('patch', $patch->patch)->delete();
    }

    /**
     * Get the next patch batch number to use
     *
     * @return int
     */
    public function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Get the last patch batch number.
     *
     * @return int
     */
    public function getLastBatchNumber(): ?int
    {
        return Patch::max('batch');
    }
}
