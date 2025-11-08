<?php

namespace AxelvdS\UptimeMonitorExtended\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class UptimeMonitorExtendedPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'uptime-monitor-extended';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        //
    }
}

