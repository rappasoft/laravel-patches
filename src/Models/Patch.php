<?php

namespace Rappasoft\LaravelPatches\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Patch
 *
 * @package Rappasoft\LaravelPatches\Models
 */
class Patch extends Model
{

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array<string>|bool
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'log' => 'array',
        'ran_on' => 'datetime',
        'execution_time_ms' => 'integer',
        'memory_used_mb' => 'float',
        'status' => 'string',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        return config('laravel-patches.table_name');
    }
}
