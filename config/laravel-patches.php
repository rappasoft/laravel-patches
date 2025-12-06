<?php

return [
    /**
     * Table name for storing patch information
     */
    'table_name' => env('PATCHES_TABLE_NAME', 'patches'),

    /**
     * Transaction settings
     */
    'use_transactions' => env('PATCHES_USE_TRANSACTIONS', false),

    /**
     * Error handling
     */
    'stop_on_error' => env('PATCHES_STOP_ON_ERROR', true),
    'log_errors' => env('PATCHES_LOG_ERRORS', true),

    /**
     * Metadata tracking
     */
    'track_metadata' => env('PATCHES_TRACK_METADATA', true),
    'track_memory' => env('PATCHES_TRACK_MEMORY', true),
    'track_user' => env('PATCHES_TRACK_USER', true),

    /**
     * Display settings
     */
    'show_descriptions' => env('PATCHES_SHOW_DESCRIPTIONS', true),
];
