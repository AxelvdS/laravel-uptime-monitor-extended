<?php

namespace AxelvdS\UptimeMonitorExtended;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class UptimeMonitorExtendedServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/uptime-monitor-extended.php',
            'uptime-monitor-extended'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/uptime-monitor-extended.php' => config_path('uptime-monitor-extended.php'),
        ], 'uptime-monitor-extended-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'uptime-monitor-extended-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \AxelvdS\UptimeMonitorExtended\Commands\CheckMonitorsExtended::class,
                \AxelvdS\UptimeMonitorExtended\Commands\CleanupLogs::class,
            ]);
        }

        // Register Filament features if Filament is installed
        if ($this->isFilamentInstalled()) {
            $this->registerFilamentFeatures();
        } else {
            // Register legacy dashboard routes/views only if Filament is not installed
            $this->registerLegacyDashboard();
        }
    }

    /**
     * Check if Filament is installed.
     */
    protected function isFilamentInstalled(): bool
    {
        return class_exists(\Filament\Facades\Filament::class) ||
               class_exists(\Filament\Filament::class);
    }

    /**
     * Register Filament features.
     */
    protected function registerFilamentFeatures(): void
    {
        // Register Filament service provider
        if (class_exists(\AxelvdS\UptimeMonitorExtended\Filament\UptimeMonitorExtendedFilamentServiceProvider::class)) {
            $this->app->register(\AxelvdS\UptimeMonitorExtended\Filament\UptimeMonitorExtendedFilamentServiceProvider::class);
        }
    }

    /**
     * Register legacy dashboard (Blade views and routes).
     */
    protected function registerLegacyDashboard(): void
    {
        // Only register if dashboard is enabled in config
        if (config('uptime-monitor-extended.dashboard.enabled', true)) {
            // Publish views (for dashboard widgets)
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/uptime-monitor-extended'),
            ], 'uptime-monitor-extended-views');

            // Load views
            $this->loadViewsFrom(__DIR__ . '/../resources/views', 'uptime-monitor-extended');

            // Register routes
            $this->registerRoutes();
        }
    }

    /**
     * Register package routes.
     */
    protected function registerRoutes(): void
    {
        Route::group([
            'prefix' => config('uptime-monitor-extended.route_prefix', 'uptime-monitor'),
            'middleware' => config('uptime-monitor-extended.middleware', ['web']),
        ], function () {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        });
    }
}

