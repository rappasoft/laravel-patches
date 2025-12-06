<?php

namespace Rappasoft\LaravelPatches\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatchExecuting
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
     * The patch instance.
     */
    public object $instance;

    /**
     * Create a new event instance.
     */
    public function __construct(string $patch, int $batch, object $instance)
    {
        $this->patch = $patch;
        $this->batch = $batch;
        $this->instance = $instance;
    }
}
