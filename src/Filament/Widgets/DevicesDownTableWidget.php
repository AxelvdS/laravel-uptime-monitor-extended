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
            ->query($this->query())
            ->heading(config('uptime-monitor-extended.filament.navigation_label', 'Monitors') . ' Currently Down')
            ->description(config('uptime-monitor-extended.filament.navigation_label', 'Monitors') . ' that are currently down or have SSL issues')
            ->defaultSort('last_checked', 'desc')
            ->actions([]) // Disable actions since we're using arrays, not Models
            ->bulkActions([]) // Disable bulk actions
            ->recordAction(null) // Explicitly disable record action
            ->recordUrl(null) // Disable record URLs
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('url')
                    ->label('URL/IP')
                    ->formatStateUsing(function ($state) {
                        // Convert URL object to string if needed
                        $url = is_object($state) && method_exists($state, '__toString') 
                            ? (string) $state 
                            : (string) $state;
                        
                        // Remove // prefix that Spatie's URL object adds
                        $url = preg_replace('#^//+#', '', $url);
                        
                        return $url;
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('monitor_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'https' => 'primary',
                        'http' => 'success',
                        'ping' => 'warning',
                        'tcp' => 'info',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        // Get status from custom attribute (set in getTableRecords)
                        return $record->getAttribute('status') ?? null;
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'down' => 'danger',
                        'ssl_expired' => 'warning',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->getStateUsing(function ($record) {
                        // Get error_message from custom attribute (set in getTableRecords)
                        return $record->getAttribute('error_message') ?? null;
                    })
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->getAttribute('error_message') ?? null;
                    }),
                Tables\Columns\TextColumn::make('last_checked')
                    ->label('Last Checked')
                    ->getStateUsing(function ($record) {
                        // Get last_checked from custom attribute (set in getTableRecords)
                        $lastChecked = $record->getAttribute('last_checked') ?? null;
                        return $lastChecked ? \Carbon\Carbon::parse($lastChecked) : null;
                    })
                    ->dateTime()
                    ->since(),
            ]);
    }

    /**
     * Get the table query.
     * Required by Filament for table operations, but we override getTableRecords() for custom data.
     */
    protected function query(): Builder
    {
        // Return a query for the Monitor model (required by Filament)
        // This is used for model detection, but actual records come from getTableRecords()
        return \Spatie\UptimeMonitor\Models\Monitor::query();
    }

    /**
     * Get the table records.
     * This overrides the default query-based behavior to use custom data.
     * We convert arrays to Models so Filament can handle them properly.
     */
    public function getTableRecords(): Collection
    {
        $widget = new DevicesDownTable();
        $data = $widget->getData(10);
        
        // Get the actual Monitor models from the database
        $monitorIds = collect($data)->pluck('id')->filter()->toArray();
        $monitors = \Spatie\UptimeMonitor\Models\Monitor::whereIn('id', $monitorIds)->get()->keyBy('id');
        
        // Map data to actual Monitor models and add custom attributes
        $models = collect($data)->map(function ($item) use ($monitors) {
            $monitor = $monitors->get($item['id'] ?? null);
            
            if (!$monitor) {
                // Fallback: create a new model if not found
                $monitor = new \Spatie\UptimeMonitor\Models\Monitor();
                $monitor->id = $item['id'] ?? null;
                $monitor->exists = true;
            }
            
            // Add custom attributes from DevicesDownTable data
            $monitor->setAttribute('status', $item['status'] ?? null);
            $monitor->setAttribute('error_message', $item['error_message'] ?? null);
            $monitor->setAttribute('last_checked', $item['last_checked'] ?? null);
            
            return $monitor;
        })->filter();
        
        return Collection::make($models);
    }

}

