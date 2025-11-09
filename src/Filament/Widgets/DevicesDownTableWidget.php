<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Widgets;

use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\DevicesDownTable;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class DevicesDownTableWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Devices Currently Down')
            ->description('Monitors that are currently down or have SSL issues')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->getStateUsing(fn (array $record) => $record['id'] ?? null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('URL/IP')
                    ->getStateUsing(fn (array $record) => $record['url'] ?? null)
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->getStateUsing(fn (array $record) => $record['type'] ?? null)
                    ->color(fn (string $state): string => match ($state) {
                        'https' => 'primary',
                        'http' => 'success',
                        'ping' => 'warning',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (array $record) => $record['status'] ?? null)
                    ->color(fn (string $state): string => match ($state) {
                        'down' => 'danger',
                        'ssl_expired' => 'warning',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->getStateUsing(fn (array $record) => $record['error_message'] ?? null)
                    ->limit(50)
                    ->tooltip(fn (array $record) => $record['error_message'] ?? null),
                Tables\Columns\TextColumn::make('last_checked')
                    ->label('Last Checked')
                    ->getStateUsing(fn (array $record) => $record['last_checked'] ?? null)
                    ->dateTime()
                    ->since(),
            ]);
    }

    public function getTableRecords(): array
    {
        $widget = new DevicesDownTable();
        return $widget->getData(10);
    }
}

