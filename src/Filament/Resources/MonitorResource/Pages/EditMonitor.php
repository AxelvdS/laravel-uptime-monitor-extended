<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource\Pages;

use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMonitor extends EditRecord
{
    protected static string $resource = MonitorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure look_for_string is an empty string if null (Spatie's column is NOT NULL DEFAULT '')
        $data['look_for_string'] = $data['look_for_string'] ?? '';

        return $data;
    }
}

