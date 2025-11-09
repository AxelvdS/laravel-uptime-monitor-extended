# Developer Documentation

This document provides technical information for developers who want to contribute to or extend this package.

## Package Structure

```
laravel-uptime-monitor-extended/
├── config/
│   └── uptime-monitor-extended.php    # Package configuration
├── database/
│   └── migrations/                     # Database migrations
├── resources/
│   └── views/                          # Blade templates
├── routes/
│   └── web.php                         # Package routes
├── src/
│   ├── Checks/                         # Check implementations
│   │   ├── MonitorChecker.php         # Main checker orchestrator
│   │   └── PingCheck.php              # Ping check implementation
│   ├── Commands/                       # Artisan commands
│   │   ├── CheckMonitorsExtended.php  # Extended check command
│   │   └── CleanupLogs.php            # Log cleanup command
│   ├── Dashboard/                     # Dashboard widgets
│   │   └── Widgets/
│   │       ├── UpDownWidget.php       # Up/down statistics
│   │       ├── DevicesDownTable.php   # Devices down table
│   │       └── UptimeGraph.php        # Uptime graph data
│   ├── Http/
│   │   └── Controllers/
│   │       └── DashboardController.php # Dashboard controller
│   ├── Models/
│   │   └── MonitorLog.php             # Monitor log model
│   └── UptimeMonitorExtendedServiceProvider.php
└── composer.json
```

## Architecture

### Check System

The package uses a checker system that supports multiple monitor types:

1. **MonitorChecker** - Main orchestrator that routes checks based on monitor type
2. **PingCheck** - Handles ICMP ping checks for IP addresses
3. **HTTP/HTTPS** - Uses Spatie's built-in checking functionality

### Monitor Types

- `ping` - ICMP ping for IP addresses
- `http` - HTTP monitoring (no SSL checks)
- `https` - HTTPS monitoring with SSL certificate checks

### Database Schema

#### Extended Monitors Table

The package extends Spatie's `monitors` table with:

- `monitor_type` (string) - Type of monitor
- `frequency_minutes` (integer, nullable) - Per-monitor frequency
- `is_active` (boolean) - Active/inactive toggle
- `last_check_at` (timestamp, nullable) - Last check timestamp
- `ping_timeout` (integer, nullable) - Ping timeout
- `notes` (text, nullable) - Optional notes

#### Monitor Logs Table

Stores check history:

- `monitor_id` (foreign key) - Reference to monitor
- `status` (enum) - up, down, ssl_issue, ssl_expiring
- `response_time_ms` (string, nullable) - Response time in milliseconds
- `error_message` (text, nullable) - Error message if failed
- `metadata` (json, nullable) - Additional metadata
- `checked_at` (timestamp) - When the check was performed

## Extending the Package

### Adding a New Monitor Type

1. Create a new check class in `src/Checks/`:

```php
namespace AxelvdS\UptimeMonitorExtended\Checks;

class TcpCheck
{
    public function check(string $host, int $port): array
    {
        // Implementation
    }
}
```

2. Add the check to `MonitorChecker`:

```php
protected function checkTcp(Monitor $monitor): array
{
    // Implementation
}
```

3. Update the `check()` method to handle the new type:

```php
return match ($monitorType) {
    'ping' => $this->checkPing($monitor),
    'http', 'https' => $this->checkHttp($monitor),
    'tcp' => $this->checkTcp($monitor), // New type
    default => $this->checkHttp($monitor),
};
```

### Adding a New Widget

1. Create a widget class in `src/Dashboard/Widgets/`:

```php
namespace AxelvdS\UptimeMonitorExtended\Dashboard\Widgets;

class CustomWidget
{
    public function getData(): array
    {
        // Return widget data
    }
}
```

2. Add a controller method in `DashboardController`:

```php
public function customWidget(): JsonResponse
{
    return response()->json($this->customWidget->getData());
}
```

3. Add a route in `routes/web.php`:

```php
Route::get('/api/custom-widget', [DashboardController::class, 'customWidget']);
```

### Custom Response Checkers

For advanced response checking, you can create custom response checkers as described in [Spatie's documentation](https://spatie.be/docs/laravel-uptime-monitor/v3/advanced-usage/sending-and-verifying-a-payload).

## Testing

### Running Tests

```bash
composer test
```

### Testing Ping Checks

Note: Ping checks require system-level access. In test environments, you may need to mock the ping functionality.

### Testing HTTP Checks

HTTP checks use Spatie's functionality, which can be tested using HTTP mocking libraries.

## Events

The package extends Spatie's events. You can listen to:

- `Spatie\UptimeMonitor\Events\UptimeCheckFailed`
- `Spatie\UptimeMonitor\Events\UptimeCheckRecovered`
- `Spatie\UptimeMonitor\Events\UptimeCheckSucceeded`

## Commands

### CheckMonitorsExtended

Checks all active monitors that are due for checking based on their `frequency_minutes` setting.

```bash
php artisan uptime-monitor:check-extended
php artisan uptime-monitor:check-extended --monitor-id=1
```

### CleanupLogs

Removes old log entries based on the configured retention period.

```bash
php artisan uptime-monitor:cleanup-logs
```

## Platform Compatibility

### Ping Monitoring

Ping monitoring works differently on different platforms:

- **Linux/Mac**: Uses `ping -c` (count) and `-W` (timeout)
- **Windows**: Uses `ping -n` (count) and `-w` (timeout in milliseconds)

The `PingCheck` class handles these differences automatically.

## Performance Considerations

- Monitor checks are performed sequentially to avoid overwhelming servers
- Log retention helps keep the database size manageable
- Dashboard queries are optimized with indexes on `monitor_id` and `checked_at`

## Security Considerations

- Ping commands are executed via Symfony Process component
- User input is validated before being used in commands
- Dashboard routes should be protected with authentication middleware

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

## License

MIT License - see LICENSE.md for details.

