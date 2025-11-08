<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Widgets;

use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\UptimeGraph;
use Filament\Widgets\ChartWidget;

class UptimeGraphWidget extends ChartWidget
{
    protected static ?string $heading = 'Uptime Over Time';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $widget = new UptimeGraph();
        $data = $widget->getData(24, 60);

        return [
            'datasets' => [
                [
                    'label' => 'Up',
                    'data' => array_column($data, 'up'),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                ],
                [
                    'label' => 'Down',
                    'data' => array_column($data, 'down'),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
                [
                    'label' => 'SSL Expired',
                    'data' => array_column($data, 'ssl_expired'),
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
                    'borderColor' => 'rgb(245, 158, 11)',
                ],
            ],
            'labels' => array_map(function ($item) {
                return \Carbon\Carbon::parse($item['time'])->format('H:i');
            }, $data),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

