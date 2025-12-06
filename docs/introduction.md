---
title: Introduction
weight: 1
---

<section class="article_badges">
    <a href="https://packagist.org/packages/rappasoft/laravel-patches"><img src="https://img.shields.io/packagist/v/rappasoft/laravel-patches.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/rappasoft/laravel-patches/actions/workflows/php-cs-fixer.yml"><img src="https://github.com/rappasoft/laravel-patches/actions/workflows/php-cs-fixer.yml/badge.svg" alt="Styling"></a>
    <a href="https://github.com/rappasoft/laravel-patches/actions/workflows/run-tests.yml"><img src="https://github.com/rappasoft/laravel-patches/actions/workflows/run-tests.yml/badge.svg" alt="Tests"></a>
    <a href="https://packagist.org/packages/rappasoft/laravel-patches"><img src="https://img.shields.io/packagist/dt/rappasoft/laravel-patches.svg?style=flat-square" alt="Total Downloads"></a>
</section>

This package generates patch files in the same fashion Laravel generates migrations. Each file is timestamped with an up and a down method and is associated with a batch. You may run or rollback patches with the commands below.

## Features

- **Migration-style patches** with `up()` and `down()` methods
- **Batch tracking** for organized rollbacks
- **Comprehensive metadata** tracking (execution time, memory, user, environment)
- **Event system** for monitoring and custom workflows
- **Transaction support** for atomic operations
- **Error handling** with detailed logging and recovery options
- **Multiple commands** for managing patches (`status`, `list`, `dry-run`)
- **Laravel 11 & 12 compatible**

## Requirements

- PHP 8.2+
- Laravel 11.x - 12.x
