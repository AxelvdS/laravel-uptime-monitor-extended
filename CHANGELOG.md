# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-01

### Added
- IP address monitoring via ping (ICMP)
- Per-monitor frequency configuration
- Active/inactive toggle per monitor
- Extended logging with configurable retention
- Dashboard widgets:
  - Devices up/down/SSL expired statistics
  - Table of devices currently down
  - Uptime graph over time
- Support for HTTP/HTTPS monitoring (extends Spatie's functionality)
- SSL certificate monitoring
- Response checking (login pages, API responses)
- Cleanup command for old logs
- Extended check command with per-monitor frequency support

### Dependencies
- Requires Spatie Laravel Uptime Monitor v3.0+
- Laravel 10.0+ or 11.0+
- PHP 8.1+

