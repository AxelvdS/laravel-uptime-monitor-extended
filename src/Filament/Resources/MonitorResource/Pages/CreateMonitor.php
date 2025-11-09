<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource\Pages;

use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMonitor extends CreateRecord
{
    protected static string $resource = MonitorResource::class;

    public function getTitle(): string
    {
        $label = config('uptime-monitor-extended.filament.navigation_label', 'Monitors');
        // Convert plural to singular for page title
        if (str_ends_with($label, 's') && strlen($label) > 1) {
            $label = substr($label, 0, -1);
        }
        return 'Create ' . $label;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure look_for_string is an empty string if null (Spatie's column is NOT NULL DEFAULT '')
        $data['look_for_string'] = $data['look_for_string'] ?? '';

        // Convert URL object to string if it's an object (shouldn't happen on create, but just in case)
        if (isset($data['url']) && is_object($data['url'])) {
            $data['url'] = (string) $data['url'];
        }

        return $data;
    }
}

