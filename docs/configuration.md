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
