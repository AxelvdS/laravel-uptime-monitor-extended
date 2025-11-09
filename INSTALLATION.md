# Installation Guide

This guide will walk you through installing and setting up Laravel Uptime Monitor Extended in your Laravel project.

## Prerequisites

- PHP 8.1 or higher (PHP 8.3+ recommended)
- Laravel 10.0 or higher
- Composer
- Database (MySQL, PostgreSQL, SQLite, etc.)

> **Note**: Spatie's Laravel Uptime Monitor (v4.0+) will be automatically installed as a dependency.

## Step 1: Install This Package

Install the extended package:

```bash
composer require axelvds/laravel-uptime-monitor-extended
```

> **Note**: Spatie's Laravel Uptime Monitor (v4.0+) is automatically installed as a dependency. You don't need to install it separately.

## Step 2: Publish Spatie's Migrations

Publish Spatie's migrations:

```bash
php artisan vendor:publish --provider="Spatie\UptimeMonitor\UptimeMonitorServiceProvider"
```

## Step 3: Publish This Package's Configuration and Migrations

Publish the package assets:

```bash
php artisan vendor:publish --provider="AxelvdS\UptimeMonitorExtended\UptimeMonitorExtendedServiceProvider"
```

This will publish:
- Configuration file: `config/uptime-monitor-extended.php`
- Migrations: `database/migrations/`
- Views: `resources/views/vendor/uptime-monitor-extended/`

## Step 4: Run Migrations

Run the migrations:

```bash
php artisan migrate
```

This will automatically run migrations in the correct order:
1. **Spatie's migrations** - Creates the `monitors` table
2. **This package's migrations** - Extends the `monitors` table with additional columns (monitor_type, frequency_minutes, is_active, etc.) and creates the `monitor_logs` table

> **Note**: The migrations are timestamped to ensure they run in the correct order automatically. If you get an error about the `monitors` table not existing, make sure you've published Spatie's migrations first (Step 2).

## Step 6: Configure Environment Variables (Optional)

Add these to your `.env` file:

```env
# Uptime Monitor Extended Configuration
UPTIME_MONITOR_ROUTE_PREFIX=uptime-monitor
UPTIME_MONITOR_LOG_RETENTION_DAYS=30
UPTIME_MONITOR_DEFAULT_FREQUENCY=5
UPTIME_MONITOR_PING_TIMEOUT=3
UPTIME_MONITOR_PING_COUNT=1
UPTIME_MONITOR_PING_INTERVAL=0.2
UPTIME_MONITOR_DASHBOARD_ENABLED=true
UPTIME_MONITOR_GRAPH_DATA_POINTS=24
UPTIME_MONITOR_DASHBOARD_REFRESH=60
```

## Step 7: Schedule Commands

Add these commands to your `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Check monitors every minute
    $schedule->command('uptime-monitor:check-extended')
        ->everyMinute()
        ->withoutOverlapping();
    
    // Clean up old logs daily
    $schedule->command('uptime-monitor:cleanup-logs')
        ->daily();
}
```

## Step 8: Create Your First Monitor

You can create monitors via Artisan command (using Spatie's commands):

```bash
php artisan monitor:create https://example.com
```

Or programmatically:

```php
use Spatie\UptimeMonitor\Models\Monitor;

// HTTP/HTTPS monitor
Monitor::create([
    'url' => 'https://example.com',
    'monitor_type' => 'https',
    'frequency_minutes' => 5,
    'is_active' => true,
]);

// Ping monitor
Monitor::create([
    'url' => '192.168.1.1',
    'monitor_type' => 'ping',
    'frequency_minutes' => 1,
    'is_active' => true,
    'notes' => 'Main router',
]);
```

## Step 9: Test the Installation

Run a manual check:

```bash
php artisan uptime-monitor:check-extended
```

Access the dashboard:

```
http://your-app.com/uptime-monitor
```

## Troubleshooting

### Ping Not Working

If ping monitoring doesn't work:

1. Check if ping is available: `which ping` (Linux/Mac) or `where ping` (Windows)
2. Ensure your server has permission to execute ping commands
3. Some shared hosting restricts ping - use HTTP monitoring instead

### Monitors Not Checking

1. Ensure monitors are marked as `is_active = true`
2. Check that the scheduled command is running
3. Verify `frequency_minutes` is set correctly
4. Check `last_check_at` to see when monitors were last checked

### Dashboard Not Loading

1. Ensure routes are registered: `php artisan route:list | grep uptime-monitor`
2. Check that views are published
3. Verify middleware configuration in config file

## Next Steps

- Read the [README.md](README.md) for usage examples
- Check [DEVELOPER.md](DEVELOPER.md) for technical details
- Review the configuration file for customization options

