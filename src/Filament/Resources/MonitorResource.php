<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Resources;

use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource\Pages;
use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource\Pages\CreateMonitor;
use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource\Pages\EditMonitor;
use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource\Pages\ListMonitors;
use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource\Pages\ViewMonitor;
use AxelvdS\UptimeMonitorExtended\Filament\Resources\RelationManagers\MonitorLogsRelationManager;
use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Artisan;
use Spatie\UptimeMonitor\Models\Monitor;

class MonitorResource extends Resource
{
    protected static ?string $model = Monitor::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-server';

    public static function getNavigationLabel(): string
    {
        return config('uptime-monitor-extended.filament.navigation_label', 'Monitors');
    }

    public static function getNavigationGroup(): ?string
    {
        // Return null if not configured, otherwise use the config value
        return config('uptime-monitor-extended.filament.navigation_group');
    }

    public static function getLabel(): string
    {
        $label = config('uptime-monitor-extended.filament.navigation_label', 'Monitors');
        // Convert plural to singular (simple: remove trailing 's')
        // This handles cases like "Monitors" -> "Monitor", "Uptime Monitors" -> "Uptime Monitor"
        if (str_ends_with($label, 's') && strlen($label) > 1) {
            return substr($label, 0, -1);
        }
        return $label;
    }

    public static function getPluralLabel(): string
    {
        return config('uptime-monitor-extended.filament.navigation_label', 'Monitors');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->helperText('A friendly name to identify this monitor (e.g., "Main Router", "API Server")')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Textarea::make('description')
                    ->label('Description')
                    ->helperText('Optional description of what this monitor is for')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                TextInput::make('url')
                    ->label('URL, IP Address, or Host:Port')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->helperText('Enter a URL (http:// or https://), IP address for ping, or host:port for TCP (e.g., 192.168.1.1:22)')
                    ->maxLength(255)
                    ->afterStateUpdated(function ($state, Set $set) {
                        if (empty($state)) {
                            return;
                        }

                        // Check for protocol first
                        if (str_starts_with($state, 'https://')) {
                            $set('monitor_type', 'https');
                            return;
                        }
                        
                        if (str_starts_with($state, 'http://')) {
                            $set('monitor_type', 'http');
                            return;
                        }

                        // Remove protocol if present for further checks
                        $cleanUrl = preg_replace('#^https?://#', '', $state);
                        
                        // Check for host:port format (TCP port check)
                        // Must have a colon and the part after colon should be a number
                        if (preg_match('#^(.+):(\d+)$#', $cleanUrl, $matches)) {
                            $port = (int) $matches[2];
                            if ($port >= 1 && $port <= 65535) {
                                $set('monitor_type', 'tcp');
                                return;
                            }
                        }
                        
                        // Check if it's a valid IP address (for ping)
                        if (filter_var($cleanUrl, FILTER_VALIDATE_IP)) {
                            $set('monitor_type', 'ping');
                            return;
                        }

                        // Default to https if it looks like a URL
                        if (str_contains($cleanUrl, '.')) {
                            $set('monitor_type', 'https');
                        }
                    }),

                Select::make('monitor_type')
                    ->label('Monitor Type')
                    ->options([
                        'https' => 'HTTPS',
                        'http' => 'HTTP',
                        'ping' => 'Ping (ICMP)',
                        'tcp' => 'TCP Port',
                    ])
                    ->default('https')
                    ->required()
                    ->helperText('Select the type of monitoring to perform. For TCP Port, use format: host:port (e.g., 192.168.1.1:22)'),

                TextInput::make('frequency_minutes')
                    ->label('Check Frequency (minutes)')
                    ->numeric()
                    ->default(config('uptime-monitor-extended.default_frequency_minutes', 5))
                    ->required()
                    ->minValue(1)
                    ->helperText('How often to check this monitor'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Enable or disable monitoring for this monitor'),

                Textarea::make('look_for_string')
                    ->label('Look for String')
                    ->helperText('Optional: Check for specific content in the response')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Textarea::make('notes')
                    ->label('Notes')
                    ->helperText('Optional notes or description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('url')
                    ->label('Address')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state): string {
                        // Convert URL object to string if needed
                        $url = is_object($state) && method_exists($state, '__toString') 
                            ? (string) $state 
                            : (string) $state;
                        
                        // Remove // prefix that Spatie's URL object adds
                        $url = preg_replace('#^//+#', '', $url);
                        
                        return $url;
                    }),

                TextColumn::make('monitor_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'https' => 'primary',
                        'http' => 'success',
                        'ping' => 'warning',
                        'tcp' => 'info',
                        default => 'secondary',
                    })
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('frequency_minutes')
                    ->label('Frequency (min)')
                    ->sortable(),

                TextColumn::make('last_check_at')
                    ->label('Last Checked')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Monitor $record) {
                        // Get latest log status
                        $latestLog = MonitorLog::where('monitor_id', $record->id)
                            ->latest('checked_at')
                            ->first();
                        return $latestLog?->status ?? 'unknown';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'up' => 'success',
                        'down' => 'danger',
                        'ssl_issue' => 'warning',
                        default => 'secondary',
                    }),

                TextColumn::make('uptime_check_failed_at')
                    ->label('Failed At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('monitor_type')
                    ->options([
                        'https' => 'HTTPS',
                        'http' => 'HTTP',
                        'ping' => 'Ping',
                        'tcp' => 'TCP Port',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Active Status'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'up' => 'Up',
                        'down' => 'Down',
                        'ssl_issue' => 'SSL Issue',
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $monitorIds = MonitorLog::select('monitor_id')
                                ->where('status', $data['value'])
                                ->whereIn('id', function ($subQuery) {
                                    $subQuery->selectRaw('MAX(id)')
                                        ->from('monitors_logs')
                                        ->groupBy('monitor_id');
                                })
                                ->pluck('monitor_id');
                            
                            $query->whereIn('id', $monitorIds);
                        }
                    }),
            ])
            ->actions([
                Action::make('check_now')
                    ->label('Check Now')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Monitor $record) {
                        Artisan::call('uptime-monitor:check-extended', [
                            '--monitor-id' => $record->id,
                        ]);
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Monitor check initiated'),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordUrl(fn (Monitor $record) => MonitorResource::getUrl('view', ['record' => $record]));
    }

    public static function getRelations(): array
    {
        return [
            MonitorLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitors::route('/'),
            'create' => CreateMonitor::route('/create'),
            'edit' => EditMonitor::route('/{record}/edit'),
            'view' => ViewMonitor::route('/{record}'),
        ];
    }
}

