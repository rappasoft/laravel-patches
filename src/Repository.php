<?php

namespace Rappasoft\LaravelPatches;

use Illuminate\Support\Facades\DB;

/**
 * Class Repository
 *
 * @package Rappasoft\LaravelPatches
 */
class Repository
{
    /**
     * @return string
     */
    public function getTable(): string
    {
        return 'patches';
    }

    /**
     * Get list of patches.
     *
     * @param  int  $steps
     *
     * @return array
     */
    public function getPatches(int $steps): array
    {
        $query = DB::table($this->getTable())->where('batch', '>=', '1');

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
        return DB::table($this->getTable())
            ->orderBy('batch')
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
        $query = DB::table($this->getTable())->where('batch', $this->getLastBatchNumber());

        return $query->orderBy('patch', 'desc')
            ->get()
            ->all();
    }

    /**
     * Log that a patch was run.
     *
     * @param  string  $file
     * @param  int  $batch
     *
     * @return void
     */
    public function log(string $file, int $batch): void
    {
        DB::table($this->getTable())->insert(['patch' => $file, 'batch' => $batch]);
    }

    /**
     * Delete a patch from the database
     *
     * @param  object  $patch
     */
    public function delete(object $patch): void
    {
        DB::table($this->getTable())->where('patch', $patch->patch)->delete();
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
        return DB::table($this->getTable())->max('batch');
    }
}
