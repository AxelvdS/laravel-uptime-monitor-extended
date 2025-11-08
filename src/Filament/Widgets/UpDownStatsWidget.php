<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Widgets;

use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\UpDownWidget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UpDownStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $widget = new UpDownWidget();
        $data = $widget->getData();

        return [
            Stat::make('Devices Up', $data['up'])
                ->description($data['percentage_up'] . '% of ' . $data['total'] . ' total')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Devices Down', $data['down'])
                ->description('Requires attention')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('SSL Expired', $data['ssl_expired'])
                ->description('Certificates expired')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),
        ];
    }
}

