<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource\Pages;

use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMonitor extends CreateRecord
{
    protected static string $resource = MonitorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure look_for_string is an empty string if null (Spatie's column is NOT NULL DEFAULT '')
        $data['look_for_string'] = $data['look_for_string'] ?? '';

        return $data;
    }
}

