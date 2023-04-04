![Package Logo](https://banners.beyondco.de/Laravel%20Patches.png?theme=light&packageManager=composer+require&packageName=rappasoft%2Flaravel-patches&pattern=architect&style=style_1&description=Run+patches+migration+style+in+your+Laravel+applications.&md=1&showWatermark=0&fontSize=100px&images=puzzle)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rappasoft/laravel-patches.svg?style=flat-square)](https://packagist.org/packages/rappasoft/laravel-patches)
[![Styling](https://github.com/rappasoft/laravel-patches/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/rappasoft/laravel-patches/actions/workflows/php-cs-fixer.yml)
[![Tests](https://github.com/rappasoft/laravel-patches/actions/workflows/run-tests.yml/badge.svg)](https://github.com/rappasoft/laravel-patches/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/rappasoft/laravel-patches.svg?style=flat-square)](https://packagist.org/packages/rappasoft/laravel-patches)

This package generates patch files in the same fashion Laravel generates migrations. Each file is timestamped with an up and a down method and is associated with a batch. You may run or rollback patches with the commands below.

This is a very simple package. It runs whatever is in your up and down methods on each patch in the order the patches are defined. It currently does not handle any errors or database transactions, please make sure you account for everything and have a backup plan when running patches in production.

## Installation

You can install the package via composer:

```bash
composer require rappasoft/laravel-patches
```

## Documentation and Usage Instructions

See the [documentation](https://rappasoft.com/docs/laravel-patches) for detailed installation and usage instructions.

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
