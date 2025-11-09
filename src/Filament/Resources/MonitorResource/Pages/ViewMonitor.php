<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource\Pages;

use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMonitor extends ViewRecord
{
    protected static string $resource = MonitorResource::class;

    public function getTitle(): string
    {
        $label = config('uptime-monitor-extended.filament.navigation_label', 'Monitors');
        // Convert plural to singular for page title
        if (str_ends_with($label, 's') && strlen($label) > 1) {
            $label = substr($label, 0, -1);
        }
        return 'View ' . $label;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Convert Spatie's Url object to string for Livewire compatibility
        if (isset($data['url']) && is_object($data['url'])) {
            $data['url'] = (string) $data['url'];
        }

        return $data;
    }
}

