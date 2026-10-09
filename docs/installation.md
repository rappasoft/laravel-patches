---
title: Installation
weight: 5
---

You can install the package via composer:

```bash
composer require rappasoft/laravel-patches
```

Publish and run both package migrations:

```bash
php artisan vendor:publish --provider="Rappasoft\LaravelPatches\LaravelPatchesServiceProvider" --tag="laravel-patches-migrations"
php artisan migrate
```

When upgrading from v3, publish the migrations again to add the metadata migration, then run `php artisan migrate`. Both the patches table and its metadata columns are required before running patches.

