<?php

namespace AxelvdS\UptimeMonitorExtended\Filament;

use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource;
use AxelvdS\UptimeMonitorExtended\Filament\Widgets\DevicesDownTableWidget;
use AxelvdS\UptimeMonitorExtended\Filament\Widgets\UpDownStatsWidget;
use AxelvdS\UptimeMonitorExtended\Filament\Widgets\UptimeGraphWidget;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class UptimeMonitorExtendedFilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Discover Livewire components (widgets) in the package namespace
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component(UpDownStatsWidget::class);
            \Livewire\Livewire::component(DevicesDownTableWidget::class);
            \Livewire\Livewire::component(UptimeGraphWidget::class);
        }

        // Auto-register resources and widgets for all Filament panels
        if (class_exists(\Filament\Facades\Filament::class)) {
            \Filament\Facades\Filament::serving(function () {
                // Get all registered panels and register resources/widgets for each
                try {
                    $panels = \Filament\Facades\Filament::getPanels();
                    foreach ($panels as $panel) {
                        self::registerForPanel($panel);
                    }
                } catch (\Exception $e) {
                    // If panels aren't available yet, that's okay
                    // Users can manually register via registerForPanel()
                }
            });
        }
    }

    /**
     * Register resources and widgets for a Filament panel.
     */
    public static function registerForPanel(Panel $panel): void
    {
        $panel
            ->resources([
                MonitorResource::class,
            ])
            ->widgets([
                UpDownStatsWidget::class,
                DevicesDownTableWidget::class,
                UptimeGraphWidget::class,
            ]);
    }
}

