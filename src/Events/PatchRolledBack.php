<?php

namespace Rappasoft\LaravelPatches\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatchRolledBack
{
    use Dispatchable, SerializesModels;

    /**
     * The patch name.
     */
    public string $patch;

    /**
     * Execution time in milliseconds.
     */
    public int $executionTime;

    /**
     * Create a new event instance.
     */
    public function __construct(string $patch, int $executionTime)
    {
        $this->patch = $patch;
        $this->executionTime = $executionTime;
    }
}
