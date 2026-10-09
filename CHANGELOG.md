# Changelog

All notable changes to `laravel-patches` will be documented in this file.

## Unreleased

### Added
- Laravel 13 support while retaining Laravel 11 and 12 compatibility.
- Compatibility with current Testbench, Pest, and PHPUnit releases.

### Fixed
- Preserve patch execution history and stop when a rollback fails.
- Dispatch rollback completion and failure events with the original batch.
- Test every supported Laravel/PHP combination in CI and update GitHub Actions, including the patched PHP setup action.
- Correct documentation version metadata, migration setup instructions, and error handling guidance.

## 4.0.0 - 2025-12-05

### Added
- Laravel 11 & 12 support
- PHP 8.2+ support
- Comprehensive test suite with Pest PHP
- Repository tests (11 test cases)
- Patcher tests (12 test cases)
- Patch base class tests (6 test cases)
- Model tests (9 test cases)
- Integration tests (7 test cases)
- Enhanced command tests with additional coverage

### Changed
- **BREAKING**: Minimum PHP version is now 8.2
- **BREAKING**: Minimum Laravel version is now 11.0 (supports 11.x - 12.x)
- Updated all dependencies for Laravel 11 compatibility
- Migrated from PHPUnit to Pest for testing
- Updated Model to use modern Laravel 11 conventions
- Removed deprecated `$dates` property from Model
- Updated `$casts` to include datetime casting for `ran_on`
- Changed from `$fillable` to `$guarded = []` for mass assignment
- Updated README with requirements section

### Fixed
- Fixed deprecated Model property usage for Laravel 11
- Model now properly casts `ran_on` as datetime

## 3.0.0 - 2023-04-03

### Added
- Laravel 10 Support

## 2.0.1 - 2022-02-26

### Changed

- Fix table name in model - https://github.com/rappasoft/laravel-patches/pull/4

## 2.0.0 - 2022-02-21

### Added

- Laravel 9 Support
- Ability to specify table name

## 1.0.0 - 2021-03-07

- Initial Release
