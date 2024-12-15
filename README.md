# Laravel PostgreSQL Materialized View Statistics

A Laravel package that automatically tracks and manages statistics for PostgreSQL materialized views.

## Features

- Automatically tracks creation, modification, and refresh operations on materialized views
- Records refresh durations, counts, and timestamps
- Provides statistics through an easy-to-query view
- Includes Artisan commands for statistics management
- Supports PostgreSQL 12 or later
- Compatible with Laravel 10+
- Requires PHP 8.1+

## Installation

You can install the package via composer:

```bash
composer require trogers1884/laravel-mvstats
```

The package will automatically register its service provider.

### Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- PostgreSQL 12.0 or higher

### Database Objects Created

This package creates the following database objects:

1. Tables:
    - `public.tr1884_mvstats_tbl_matv_stats` - Base statistics table

2. Views:
    - `public.tr1884_mvstats_vw_matv_stats` - Formatted view of statistics

3. Functions:
    - `public.tr1884_mvstats_fn_mv_activity_init()` - Initializes tracking for existing materialized views
    - `public.tr1884_mvstats_fn_mv_activity_reset_stats()` - Resets statistics
    - Several internal trigger functions

4. Event Triggers:
    - Triggers for tracking materialized view operations

## Usage

### Viewing Statistics

Once installed, the package automatically tracks all materialized view operations. You can query the statistics view:

```sql
SELECT * FROM public.tr1884_mvstats_vw_matv_stats;
```

The view provides the following columns:

- `mv_name` - Name of the materialized view (schema.name format)
- `create_mv` - Creation timestamp
- `mod_mv` - Last modification timestamp
- `refresh_mv_last` - Last refresh timestamp
- `refresh_count` - Number of refreshes
- `refresh_mv_time_last` - Duration of last refresh
- `refresh_mv_time_total` - Total refresh time
- `refresh_mv_time_min` - Minimum refresh duration
- `refresh_mv_time_max` - Maximum refresh duration
- `reset_last` - Last statistics reset timestamp

### Artisan Commands

#### Reset Statistics

Reset statistics for a specific materialized view:
```bash
php artisan mvstats:reset-stats schema.view_name
```

Reset statistics for all materialized views:
```bash
php artisan mvstats:reset-stats --all
```

### Examples

Query views that haven't been refreshed in the last 24 hours:
```sql
SELECT mv_name, refresh_mv_last
FROM public.tr1884_mvstats_vw_matv_stats
WHERE refresh_mv_last < NOW() - INTERVAL '24 hours'
   OR refresh_mv_last IS NULL;
```

Find views with the longest average refresh times:
```sql
SELECT 
    mv_name,
    refresh_count,
    refresh_mv_time_total / NULLIF(refresh_count, 0) as avg_refresh_time
FROM public.tr1884_mvstats_vw_matv_stats
WHERE refresh_count > 0
ORDER BY avg_refresh_time DESC;
```

## Uninstallation

### Option 1: Keep Historical Data

1. Remove the package:
```bash
composer remove trogers1884/laravel-mvstats
```

2. The database objects will remain for historical reference.

### Option 2: Complete Removal

1. First, remove all database objects:
```sql
SELECT public.tr1884_mvstats_fn_mv_drop_objects();
```

2. Then remove the package:
```bash
composer remove trogers1884/laravel-mvstats
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please see [SECURITY.md](SECURITY.md) for reporting procedures.

## Credits

- [Tom Rogers](https://github.com/trogers1884)
- Jeremy Gleed (jeremy_gleed at yahoo.com)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.