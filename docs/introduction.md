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

This is a very simple package. It runs whatever is in your up and down methods on each patch in the order the patches are defined. It currently does not handle any errors or database transactions, please make sure you account for everything and have a backup plan when running patches in production.
