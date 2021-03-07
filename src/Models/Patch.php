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
     * @var string[]
     */
    protected $fillable = [
        'patch',
        'batch',
        'log',
        'ran_on',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'ran_on',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'log' => 'array',
    ];
}
