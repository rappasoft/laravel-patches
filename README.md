![Package Logo](https://banners.beyondco.de/Laravel%20Patches.png?theme=light&packageManager=composer+require&packageName=rappasoft%2Flaravel-patches&pattern=architect&style=style_1&description=Run+patches+migration+style+in+your+Laravel+applications.&md=1&showWatermark=0&fontSize=100px&images=puzzle)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rappasoft/laravel-patches.svg?style=flat-square)](https://packagist.org/packages/rappasoft/laravel-patches)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/rappasoft/laravel-patches/Run%20Tests?label=tests)](https://github.com/rappasoft/laravel-patches/actions?query=workflow%3ATests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/rappasoft/laravel-patches/Check%20&%20fix%20styling?label=code%20style)](https://github.com/rappasoft/laravel-patches/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/rappasoft/laravel-patches.svg?style=flat-square)](https://packagist.org/packages/rappasoft/laravel-patches)

### Enjoying this package? [Buy me a beer 🍺](https://www.buymeacoffee.com/rappasoft)

This package generates patch files in the same fashion Laravel generates migrations. Each file is timestamped with an up and a down method and is associated with a batch. You may run or rollback patches with the commands below.

This is a very simple package. It runs whatever is in your up and down methods on each patch in the order the patches are defined. It currently does not handle any errors or database transactions, please make sure you account for everything and have a backup plan when running patches in production.

## Installation

You can install the package via composer:

```bash
composer require rappasoft/laravel-patches
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Rappasoft\LaravelPatches\LaravelPatchesServiceProvider" --tag="laravel-patches-config"
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --provider="Rappasoft\LaravelPatches\LaravelPatchesServiceProvider" --tag="laravel-patches-migrations"
php artisan migrate
```

## Usage

### Making Patches

```bash
php artisan make:patch patch_1_0_0
```

This created a timestamped patch file under database/patches.

### Running Patches

To run all available patches:

```bash
php artisan patch
```

To run each available patch in its own batch:

```bash
php artisan patch --step
```

To force the patches to run in production (deploy scripts, etc.):

```bash
php artisan patch --force
```

### Rolling Back Patches

To rollback all patches of the last batch:

```bash
php artisan patch:rollback
```

To rollback the last X patches regardless of batch:

```bash
php artisan patch:rollback --step=X
```

### Patch File Helpers

You may use the following helper commands from your patch files:

Log a line to the patches log column (up method only):

```php
$this->log('10 users modified');
```

Call an Artisan command with options:

```php
$this->call($command, $parameters);
```

Call a seeder by class name: 

```php
$this->seed($class);
```

Truncate a table by name:

```php
$this->truncate($table);
```
*Note: Does not disable foreign key checks.*

**Please feel free to PR new helpers.**

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Anthony Rappa](https://github.com/rappasoft)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
