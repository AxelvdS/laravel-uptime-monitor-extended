# Usage Examples

This document provides practical examples of using Laravel Uptime Monitor Extended.

## Creating Monitors

### HTTP/HTTPS Monitor

```php
use Spatie\UptimeMonitor\Models\Monitor;

// Basic HTTPS monitor
$monitor = Monitor::create([
    'url' => 'https://example.com',
    'monitor_type' => 'https',
    'frequency_minutes' => 5,
    'is_active' => true,
]);

// HTTPS monitor with response checking
$monitor = Monitor::create([
    'url' => 'https://api.example.com/health',
    'monitor_type' => 'https',
    'frequency_minutes' => 5,
    'is_active' => true,
    'look_for_string' => '{"status":"ok"}', // Check for specific content
]);

// HTTP monitor for IP address
$monitor = Monitor::create([
    'url' => 'http://192.168.1.100:8080',
    'monitor_type' => 'http',
    'frequency_minutes' => 1,
    'is_active' => true,
    'notes' => 'Internal API server',
]);
```

### Ping Monitor

```php
use Spatie\UptimeMonitor\Models\Monitor;

// Ping monitor for a server
$monitor = Monitor::create([
    'url' => '192.168.1.1',
    'monitor_type' => 'ping',
    'frequency_minutes' => 1,
    'is_active' => true,
    'notes' => 'Main router',
]);

// Ping monitor for external IP
$monitor = Monitor::create([
    'url' => '8.8.8.8',
    'monitor_type' => 'ping',
    'frequency_minutes' => 5,
    'is_active' => true,
    'notes' => 'Google DNS',
]);
```

## Managing Monitors

### Toggle Active Status

```php
$monitor = Monitor::find(1);

// Deactivate
$monitor->is_active = false;
$monitor->save();

// Activate
$monitor->is_active = true;
$monitor->save();
```

### Update Frequency

```php
$monitor = Monitor::find(1);
$monitor->frequency_minutes = 10; // Check every 10 minutes
$monitor->save();
```

### Bulk Operations

```php
// Activate all monitors
Monitor::query()->update(['is_active' => true]);

// Deactivate monitors for a specific type
Monitor::where('monitor_type', 'ping')->update(['is_active' => false]);

// Update frequency for all HTTPS monitors
Monitor::where('monitor_type', 'https')
    ->update(['frequency_minutes' => 5]);
```

## Checking Monitors

### Manual Check

```bash
# Check all active monitors
php artisan uptime-monitor:check-extended

# Check a specific monitor
php artisan uptime-monitor:check-extended --monitor-id=1
```

### Programmatic Check

```php
use AxelvdS\UptimeMonitorExtended\Checks\MonitorChecker;
use Spatie\UptimeMonitor\Models\Monitor;

$checker = new MonitorChecker();
$monitor = Monitor::find(1);

$result = $checker->check($monitor);

if ($result['success']) {
    echo "Monitor is up!\n";
    echo "Status: {$result['status']}\n";
    if (isset($result['response_time_ms'])) {
        echo "Response time: {$result['response_time_ms']}ms\n";
    }
} else {
    echo "Monitor is down!\n";
    echo "Error: {$result['message']}\n";
}
```

## Accessing Logs

### Get Monitor Logs

```php
use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;

// Get all logs for a monitor
$logs = MonitorLog::where('monitor_id', 1)
    ->orderBy('checked_at', 'desc')
    ->get();

// Get logs by status
$downLogs = MonitorLog::where('monitor_id', 1)
    ->where('status', 'down')
    ->get();

// Get logs for a date range
$logs = MonitorLog::where('monitor_id', 1)
    ->whereBetween('checked_at', [
        now()->subDays(7),
        now()
    ])
    ->get();
```

### Get Latest Status

```php
use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;
use Illuminate\Support\Facades\DB;

$latestLog = MonitorLog::where('monitor_id', 1)
    ->orderBy('checked_at', 'desc')
    ->first();

if ($latestLog) {
    echo "Status: {$latestLog->status}\n";
    echo "Checked at: {$latestLog->checked_at}\n";
    if ($latestLog->error_message) {
        echo "Error: {$latestLog->error_message}\n";
    }
}
```

## Using Dashboard Widgets

### Get Statistics

```php
use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\UpDownWidget;

$widget = new UpDownWidget();
$stats = $widget->getData();

echo "Total: {$stats['total']}\n";
echo "Up: {$stats['up']}\n";
echo "Down: {$stats['down']}\n";
echo "SSL Expired: {$stats['ssl_expired']}\n";
echo "Percentage Up: {$stats['percentage_up']}%\n";
```

### Get Devices Down

```php
use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\DevicesDownTable;

$widget = new DevicesDownTable();
$devices = $widget->getData(10); // Get top 10

foreach ($devices as $device) {
    echo "{$device['url']} - {$device['status']}\n";
    if ($device['error_message']) {
        echo "  Error: {$device['error_message']}\n";
    }
}
```

### Get Graph Data

```php
use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\UptimeGraph;

$widget = new UptimeGraph();
$data = $widget->getData(24, 60); // Last 24 hours, 60-minute intervals

foreach ($data as $point) {
    echo "Time: {$point['time']}\n";
    echo "  Up: {$point['up']}\n";
    echo "  Down: {$point['down']}\n";
    echo "  SSL Expired: {$point['ssl_expired']}\n";
}
```

## Event Listeners

### Listen to Spatie Events

```php
// In your EventServiceProvider or a service provider
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;
use Spatie\UptimeMonitor\Events\UptimeCheckSucceeded;

Event::listen(UptimeCheckFailed::class, function ($event) {
    $monitor = $event->monitor;
    // Send notification, log, etc.
    Log::error("Monitor {$monitor->url} is down!");
});

Event::listen(UptimeCheckRecovered::class, function ($event) {
    $monitor = $event->monitor;
    Log::info("Monitor {$monitor->url} has recovered!");
});

Event::listen(UptimeCheckSucceeded::class, function ($event) {
    $monitor = $event->monitor;
    // Monitor is up
});
```

## Cleanup Logs

### Manual Cleanup

```bash
php artisan uptime-monitor:cleanup-logs
```

### Programmatic Cleanup

```php
use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;
use Carbon\Carbon;

$retentionDays = config('uptime-monitor-extended.log_retention_days', 30);

if ($retentionDays !== null) {
    $cutoffDate = Carbon::now()->subDays($retentionDays);
    $deleted = MonitorLog::where('checked_at', '<', $cutoffDate)->delete();
    echo "Deleted {$deleted} old log entries.\n";
}
```

## Query Examples

### Find Monitors Due for Checking

```php
use Spatie\UptimeMonitor\Models\Monitor;
use Carbon\Carbon;

$monitors = Monitor::where('is_active', true)
    ->where(function ($query) {
        $query->whereNull('last_check_at')
            ->orWhereRaw('TIMESTAMPDIFF(MINUTE, last_check_at, NOW()) >= COALESCE(frequency_minutes, ?)', [
                config('uptime-monitor-extended.default_frequency_minutes', 5)
            ]);
    })
    ->get();
```

### Get Monitors by Type

```php
// Get all ping monitors
$pingMonitors = Monitor::where('monitor_type', 'ping')->get();

// Get all HTTPS monitors
$httpsMonitors = Monitor::where('monitor_type', 'https')->get();
```

### Get Uptime Statistics

```php
use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;

// Get uptime percentage for a monitor
$totalChecks = MonitorLog::where('monitor_id', 1)->count();
$upChecks = MonitorLog::where('monitor_id', 1)->where('status', 'up')->count();
$uptimePercentage = $totalChecks > 0 ? ($upChecks / $totalChecks) * 100 : 0;

echo "Uptime: {$uptimePercentage}%\n";
```

