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
     *
     * @return void
     */
    public function log(string $file, int $batch, array $log = []): void
    {
        Patch::create(['patch' => $file, 'batch' => $batch, 'log' => $log]);
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
