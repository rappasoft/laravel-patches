# Changelog

All notable changes to `laravel-patches` will be documented in this file.

## 4.1.0 - 2026-10-09

### Added
- Laravel 13 support while retaining Laravel 11 and 12 compatibility.
- Compatibility with current Testbench, Pest, and PHPUnit releases.

### Fixed
- Preserve patch execution history and stop when a rollback fails.
- Dispatch rollback completion and failure events with the original batch.
- Test every supported Laravel/PHP combination in CI, including master and release tags, and update GitHub Actions, including the patched PHP setup action.
- Correct documentation version metadata, migration setup instructions, and error handling guidance.

### Upgrade Notes
- Failed `down()` methods now stop rollback and rethrow the original exception instead of reporting success and deleting execution history. Correct the failed rollback before retrying. `stop_on_error` continues to apply only to running patches; rollback always stops on failure.
- Enable transactions when partial database changes in a failed rollback must also be reverted. Preserving the patch record does not automatically undo changes made by `down()`.
- PHP 8.2 remains supported with Laravel 11/12; Laravel 13 requires PHP 8.3+. No new migrations are introduced for applications upgrading from 4.0.x.
- Laravel 11 remains compatible, but is outside security support and has unresolved framework advisories. Prefer patched Laravel 12.69+ or 13.30+ in production. Four verified advisories are allowlisted only in the temporary Laravel 11 CI environment; application Composer security checks remain enabled.

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
