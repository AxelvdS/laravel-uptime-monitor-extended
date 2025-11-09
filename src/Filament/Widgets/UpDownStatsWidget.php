<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Widgets;

use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\UpDownWidget;
use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UpDownStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $widget = new UpDownWidget();
        $data = $widget->getData();

        return [
            Stat::make('Devices Up', $data['up'])
                ->description($data['percentage_up'] . '% of ' . $data['total'] . ' total')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->url(MonitorResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'up']]]))
                ->openUrlInNewTab(false),
            Stat::make('Devices Down', $data['down'])
                ->description('Requires attention')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->url(MonitorResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'down']]]))
                ->openUrlInNewTab(false),
            Stat::make('SSL Expired', $data['ssl_expired'])
                ->description('Certificates expired')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning')
                ->url(MonitorResource::getUrl('index', ['tableFilters' => ['status' => ['value' => 'ssl_expired']]]))
                ->openUrlInNewTab(false),
        ];
    }
}

