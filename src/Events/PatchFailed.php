<?php

namespace Rappasoft\LaravelPatches\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class PatchFailed
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
     * The exception that was thrown.
     */
    public Throwable $exception;

    /**
     * Execution time before failure in milliseconds.
     */
    public int $executionTime;

    /**
     * Create a new event instance.
     */
    public function __construct(string $patch, int $batch, Throwable $exception, int $executionTime)
    {
        $this->patch = $patch;
        $this->batch = $batch;
        $this->exception = $exception;
        $this->executionTime = $executionTime;
    }
}
