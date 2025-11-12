<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the routes for the dashboard and API endpoints.
    |
    */
    'route_prefix' => env('UPTIME_MONITOR_ROUTE_PREFIX', 'uptime-monitor'),
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Log Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep monitoring logs in days.
    | Set to null to keep logs indefinitely.
    |
    */
    'log_retention_days' => env('UPTIME_MONITOR_LOG_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Default Check Frequency
    |--------------------------------------------------------------------------
    |
    | Default frequency in minutes for new monitors if not specified.
    |
    */
    'default_frequency_minutes' => env('UPTIME_MONITOR_DEFAULT_FREQUENCY', 5),

    /*
    |--------------------------------------------------------------------------
    | Ping Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for ping monitoring.
    |
    */
    'ping' => [
        'timeout' => env('UPTIME_MONITOR_PING_TIMEOUT', 3),
        'count' => env('UPTIME_MONITOR_PING_COUNT', 1),
        'interval' => env('UPTIME_MONITOR_PING_INTERVAL', 0.2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for dashboard widgets and graphs.
    |
    */
    'dashboard' => [
        'enabled' => env('UPTIME_MONITOR_DASHBOARD_ENABLED', true),
        'graph_data_points' => env('UPTIME_MONITOR_GRAPH_DATA_POINTS', 24),
        'refresh_interval' => env('UPTIME_MONITOR_DASHBOARD_REFRESH', 60), // seconds
        'graph_height' => env('UPTIME_MONITOR_GRAPH_HEIGHT', 200), // pixels
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Filament admin panel integration.
    |
    */
    'filament' => [
        'navigation_label' => env('UPTIME_MONITOR_NAVIGATION_LABEL', 'Monitors'),
        // Optional: Set to null or don't set to have no navigation group
        'navigation_group' => env('UPTIME_MONITOR_NAVIGATION_GROUP') ?: null,
        // Panels to register resources and widgets for. Set to null to register for all panels.
        // Default: ['app'] - only register for the 'app' panel
        // To register for multiple panels, set UPTIME_MONITOR_PANELS=app,admin in .env
        // To register for all panels, set UPTIME_MONITOR_PANELS=all in .env
        'panels' => (function () {
            $panels = env('UPTIME_MONITOR_PANELS');
            if ($panels === null || $panels === '') {
                return ['app']; // Default to 'app' panel only
            }
            if (strtolower($panels) === 'null' || strtolower($panels) === 'all') {
                return null; // Register for all panels
            }
            return explode(',', $panels);
        })(),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Schedule Configuration
    |--------------------------------------------------------------------------
    |
    | Automatically register scheduled commands in the Laravel scheduler.
    | Set to false if you want to manually register them in app/Console/Kernel.php
    |
    */
    'auto_schedule' => env('UPTIME_MONITOR_AUTO_SCHEDULE', true),
];

