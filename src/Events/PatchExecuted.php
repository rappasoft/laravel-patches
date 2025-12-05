<?php

namespace Rappasoft\LaravelPatches\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatchExecuted
{
    use Dispatchable, SerializesModels;

    /**
     * The patch name.
     */
    public string $patch;

    /**
     * The batch number.
     */
    public int $batch;

    /**
     * The patch logs.
     */
    public ?array $log;

    /**
     * Execution time in milliseconds.
     */
    public int $executionTime;

    /**
     * Memory used in MB.
     */
    public float $memoryUsed;

    /**
     * Create a new event instance.
     */
    public function __construct(string $patch, int $batch, ?array $log, int $executionTime, float $memoryUsed)
    {
        $this->patch = $patch;
        $this->batch = $batch;
        $this->log = $log;
        $this->executionTime = $executionTime;
        $this->memoryUsed = $memoryUsed;
    }
}
