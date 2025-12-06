<?php

namespace Rappasoft\LaravelPatches\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PatchRollingBack
{
    use Dispatchable, SerializesModels;

    /**
     * The patch name.
     */
    public string $patch;

    /**
     * The patch instance.
     */
    public object $instance;

    /**
     * Create a new event instance.
     */
    public function __construct(string $patch, object $instance)
    {
        $this->patch = $patch;
        $this->instance = $instance;
    }
}
