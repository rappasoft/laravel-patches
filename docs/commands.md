---
title: Commands
weight: 3
---

Laravel Patches provides several commands to manage your patches.

## Creating Patches

### make:patch

Create a new patch file:

```bash
php artisan make:patch patch_name
```

This creates a timestamped patch file in `database/patches`.

---

## Running Patches

### patch

Run all pending patches:

```bash
php artisan patch
```

**Options:**

- `--force` - Force the operation to run in production
- `--step` - Run each patch in its own batch (allows individual rollback)
- `--dry-run` - Preview patches without executing them

**Examples:**

```bash
# Run in production
php artisan patch --force

# Run each patch in separate batches
php artisan patch --step

# Preview what would run
php artisan patch --dry-run
```

**Dry Run Mode:**

The `--dry-run` flag lets you safely preview which patches would execute without making any database changes:

```bash
php artisan patch --dry-run
```

Output example:
```
Dry run mode - No patches will be executed

Would run: 2024_01_01_000000_fix_user_data
Would run: 2024_01_02_000000_update_settings

Total patches: 2
```

---

## Rolling Back Patches

### patch:rollback

Rollback the last batch of patches:

```bash
php artisan patch:rollback
```

**Options:**

- `--step=X` - Rollback X number of patches

**Examples:**

```bash
# Rollback last batch
php artisan patch:rollback

# Rollback last 3 patches
php artisan patch:rollback --step=3
```

---

## Viewing Patch Status

### patch:status

Display comprehensive status of all patches:

```bash
php artisan patch:status
```

**Options:**

- `--pending` - Show only pending patches
- `--ran` - Show only executed patches
- `--batch=N` - Filter by batch number

**Output Example:**

```
┌────────┬──────────────────────────────┬───────┬─────────────────────┬───────────┬─────────┐
│ Status │ Patch                        │ Batch │ Ran On              │ Time (ms) │ Status  │
├────────┼──────────────────────────────┼───────┼─────────────────────┼───────────┼─────────┤
│ ✓      │ 2024_01_01_fix_users         │ 1     │ 2024-01-15 10:30:00 │ 145       │ Success │
│ ✓      │ 2024_01_02_update_settings   │ 1     │ 2024-01-15 10:30:01 │ 89        │ Success │
│ ⋯      │ 2024_01_03_cleanup_data      │ Pending│ -                  │ -         │ Pending │
└────────┴──────────────────────────────┴───────┴─────────────────────┴───────────┴─────────┘

Ran: 2
Pending: 1
Total: 3
```

**Examples:**

```bash
# Show only pending patches
php artisan patch:status --pending

# Show only executed patches
php artisan patch:status --ran

# Show patches from batch 2
php artisan patch:status --batch=2
```

---

## Listing Patches

### patch:list

List all available patches with optional filtering:

```bash
php artisan patch:list
```

**Options:**

- `--status=STATUS` - Filter by status (`pending`, `ran`, `failed`)
- `--batch=N` - Filter by batch number
- `--json` - Output as JSON

**Examples:**

```bash
# List all patches
php artisan patch:list

# List only pending patches
php artisan patch:list --status=pending

# List patches from batch 1
php artisan patch:list --batch=1

# Output as JSON
php artisan patch:list --json
```

**JSON Output Example:**

```json
[
  {
    "name": "2024_01_01_fix_users",
    "status": "success",
    "batch": 1,
    "ran_on": "2024-01-15 10:30:00",
    "execution_time_ms": 145
  },
  {
    "name": "2024_01_02_update_settings",
    "status": "pending",
    "batch": null,
    "ran_on": null,
    "execution_time_ms": null
  }
]
```

---

## Command Summary

| Command | Description |
|---------|-------------|
| `make:patch` | Create a new patch file |
| `patch` | Run pending patches |
| `patch:rollback` | Rollback patches |
| `patch:status` | View patch status |
| `patch:list` | List all patches |

## Best Practices

1. **Always use `--dry-run` first** in production to preview changes
2. **Use `--step` mode** when testing new patches for easier rollback
3. **Check status regularly** with `patch:status` to monitor your patches
4. **Use descriptive names** when creating patches
5. **Test patches** in staging before running in production
