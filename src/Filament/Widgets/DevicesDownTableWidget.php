<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Widgets;

use AxelvdS\UptimeMonitorExtended\Dashboard\Widgets\DevicesDownTable;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * Get the table query.
     * Required by Filament for table operations, but we override getTableRecords() for custom data.
     */
    public function query(): Builder
    {
        // Return a query for the Monitor model (required by Filament)
        // This is used for model detection, but actual records come from getTableRecords()
        return \Spatie\UptimeMonitor\Models\Monitor::query();
    }

    /**
     * Get the table records.
     * This overrides the default query-based behavior to use custom data.
     */
    public function getTableRecords(): Collection
    {
        $widget = new DevicesDownTable();
        return Collection::make($widget->getData(10));
    }
}

