---
title: Usage
weight: 7
---

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
