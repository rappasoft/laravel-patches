---
title: Configuration
weight: 6
---

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Rappasoft\LaravelPatches\LaravelPatchesServiceProvider" --tag="laravel-patches-config"
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --provider="Rappasoft\LaravelPatches\LaravelPatchesServiceProvider" --tag="laravel-patches-migrations"
php artisan migrate
```

## Configuration Options

The configuration file `config/laravel-patches.php` includes the following options:

```php
return [
    /**
     * Table name for storing patch information
     */
    'table_name' => env('PATCHES_TABLE_NAME', 'patches'),

    /**
     * Transaction settings
     * Wrap patches in database transactions for automatic rollback on errors
     */
    'use_transactions' => env('PATCHES_USE_TRANSACTIONS', false),

    /**
     * Error handling
     * Stop execution on first error, or continue running remaining patches
     */
    'stop_on_error' => env('PATCHES_STOP_ON_ERROR', true),
    'log_errors' => env('PATCHES_LOG_ERRORS', true),

    /**
     * Metadata tracking
     * Track execution metrics like time, memory, user, and environment
     */
    'track_metadata' => env('PATCHES_TRACK_METADATA', true),
    'track_memory' => env('PATCHES_TRACK_MEMORY', true),
    'track_user' => env('PATCHES_TRACK_USER', true),

    /**
     * Display settings
     * Show patch descriptions in commands
     */
    'show_descriptions' => env('PATCHES_SHOW_DESCRIPTIONS', true),
];
```

## Environment Variables

You can control these settings via your `.env` file:

```bash
# Table Configuration
PATCHES_TABLE_NAME=patches

# Transaction Settings
PATCHES_USE_TRANSACTIONS=false

# Error Handling
PATCHES_STOP_ON_ERROR=true
PATCHES_LOG_ERRORS=true

# Metadata Tracking
PATCHES_TRACK_METADATA=true
PATCHES_TRACK_MEMORY=true
PATCHES_TRACK_USER=true

# Display
PATCHES_SHOW_DESCRIPTIONS=true
```

## Customizing Table Name

If you need to use a custom table name:

```php
// config/laravel-patches.php
'table_name' => 'custom_patches_table',
```

Then republish and run migrations to create the table with your custom name.
