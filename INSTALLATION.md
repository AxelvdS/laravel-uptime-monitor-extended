# Installation Guide

This guide will walk you through installing and setting up Laravel Uptime Monitor Extended in your Laravel project.

## Prerequisites

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Composer
- Database (MySQL, PostgreSQL, SQLite, etc.)

## Step 1: Install Spatie's Laravel Uptime Monitor

This package extends Spatie's Laravel Uptime Monitor, so you need to install it first:

```bash
composer require spatie/laravel-uptime-monitor
```

Publish and run Spatie's migrations:

```bash
php artisan vendor:publish --provider="Spatie\UptimeMonitor\UptimeMonitorServiceProvider"
php artisan migrate
```

## Step 2: Install This Package

Install the extended package:

```bash
composer require axelvds/laravel-uptime-monitor-extended
```

## Step 3: Publish Configuration and Migrations

Publish the package assets:

```bash
php artisan vendor:publish --provider="AxelvdS\UptimeMonitorExtended\UptimeMonitorExtendedServiceProvider"
```

This will publish:
- Configuration file: `config/uptime-monitor-extended.php`
- Migrations: `database/migrations/`
- Views: `resources/views/vendor/uptime-monitor-extended/`

## Step 4: Run Migrations

Run the migrations to extend the monitors table and create the logs table:

```bash
php artisan migrate
```

## Step 5: Configure Environment Variables (Optional)

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

## Step 6: Schedule Commands

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

## Step 7: Create Your First Monitor

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

## Step 8: Test the Installation

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

