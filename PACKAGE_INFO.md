# Package Information

## Package Name

**axelvds/laravel-uptime-monitor-extended**

## Description

Extended uptime monitoring package for Laravel with IP/ping support, per-monitor frequency, active toggle, and dashboard widgets. Built on top of Spatie's Laravel Uptime Monitor.

## Key Features

✅ IP Address Monitoring (Ping)  
✅ URL Monitoring (HTTP/HTTPS)  
✅ SSL Certificate Monitoring  
✅ Per-Monitor Frequency  
✅ Active/Inactive Toggle  
✅ Response Checking  
✅ Configurable Log Retention  
✅ Dashboard Widgets  
✅ Real-time Statistics  
✅ Uptime Graphs  

## Requirements

- PHP 8.1+
- Laravel 10.0+ or 11.0+
- Spatie Laravel Uptime Monitor v3.0+

## Installation

```bash
composer require axelvds/laravel-uptime-monitor-extended
php artisan vendor:publish --provider="AxelvdS\UptimeMonitorExtended\UptimeMonitorExtendedServiceProvider"
php artisan migrate
```

## Quick Start

1. Install Spatie's Laravel Uptime Monitor first
2. Install this package
3. Run migrations
4. Create monitors
5. Schedule the check command
6. Access the dashboard

## Documentation Files

- **README.md** - Main documentation
- **INSTALLATION.md** - Detailed installation guide
- **EXAMPLES.md** - Usage examples
- **DEVELOPER.md** - Technical documentation for developers
- **CHANGELOG.md** - Version history

## Support

For issues and questions, please open an issue on GitHub.

## License

MIT License

## Credits

Extends [Spatie's Laravel Uptime Monitor](https://github.com/spatie/laravel-uptime-monitor)

