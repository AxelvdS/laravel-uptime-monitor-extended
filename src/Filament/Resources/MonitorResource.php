<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Resources;

use AxelvdS\UptimeMonitorExtended\Filament\Resources\MonitorResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\UptimeMonitor\Models\Monitor;

class MonitorResource extends Resource
{
    protected static ?string $model = Monitor::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    public static function getNavigationLabel(): string
    {
        return config('uptime-monitor-extended.filament.navigation_label', 'Monitors');
    }

    public static function getNavigationGroup(): ?string
    {
        return config('uptime-monitor-extended.filament.navigation_group', 'Monitoring');
    }

    public static function getLabel(): string
    {
        return config('uptime-monitor-extended.filament.navigation_label', 'Monitor');
    }

    public static function getPluralLabel(): string
    {
        return config('uptime-monitor-extended.filament.navigation_label', 'Monitors');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->helperText('A friendly name to identify this monitor (e.g., "Main Router", "API Server")')
                    ->maxLength(255)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('description')
                    ->label('Description')
                    ->helperText('Optional description of what this monitor is for')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\TextInput::make('url')
                    ->label('URL, IP Address, or Host:Port')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->helperText('Enter a URL (http:// or https://), IP address for ping, or host:port for TCP (e.g., 192.168.1.1:22)')
                    ->maxLength(255)
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
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

                Forms\Components\Select::make('monitor_type')
                    ->label('Monitor Type')
                    ->options([
                        'https' => 'HTTPS',
                        'http' => 'HTTP',
                        'ping' => 'Ping (ICMP)',
                        'tcp' => 'TCP Port',
                    ])
                    // ->default('https')
                    ->required()
                    ->helperText('Select the type of monitoring to perform. For TCP Port, use format: host:port (e.g., 192.168.1.1:22)'),

                Forms\Components\TextInput::make('frequency_minutes')
                    ->label('Check Frequency (minutes)')
                    ->numeric()
                    ->default(config('uptime-monitor-extended.default_frequency_minutes', 5))
                    ->required()
                    ->minValue(1)
                    ->helperText('How often to check this monitor'),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Enable or disable monitoring for this monitor'),

                Forms\Components\Textarea::make('look_for_string')
                    ->label('Look for String')
                    ->helperText('Optional: Check for specific content in the response')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->helperText('Optional notes or description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('url')
                    ->label('URL/IP')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->formatStateUsing(function ($state): string {
                        // Convert URL object to string if needed
                        $url = is_object($state) && method_exists($state, '__toString') 
                            ? (string) $state 
                            : (string) $state;
                        
                        // Remove // prefix that Spatie's URL object adds
                        $url = preg_replace('#^//+#', '', $url);
                        
                        return $url;
                    }),

                Tables\Columns\TextColumn::make('monitor_type')
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

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('frequency_minutes')
                    ->label('Frequency (min)')
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_check_at')
                    ->label('Last Checked')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function (Monitor $record) {
                        // Get latest log status
                        $latestLog = \AxelvdS\UptimeMonitorExtended\Models\MonitorLog::where('monitor_id', $record->id)
                            ->latest('checked_at')
                            ->first();
                        return $latestLog?->status ?? 'unknown';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'up' => 'success',
                        'down' => 'danger',
                        'ssl_expired' => 'warning',
                        default => 'secondary',
                    }),

                Tables\Columns\TextColumn::make('uptime_check_failed_at')
                    ->label('Failed At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('monitor_type')
                    ->options([
                        'https' => 'HTTPS',
                        'http' => 'HTTP',
                        'ping' => 'Ping',
                        'tcp' => 'TCP Port',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'up' => 'Up',
                        'down' => 'Down',
                        'ssl_expired' => 'SSL Expired',
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $monitorIds = \AxelvdS\UptimeMonitorExtended\Models\MonitorLog::select('monitor_id')
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
                Tables\Actions\Action::make('check_now')
                    ->label('Check Now')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Monitor $record) {
                        \Illuminate\Support\Facades\Artisan::call('uptime-monitor:check-extended', [
                            '--monitor-id' => $record->id,
                        ]);
                    })
                    ->requiresConfirmation()
                    ->successNotificationTitle('Monitor check initiated'),

                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonitors::route('/'),
            'create' => Pages\CreateMonitor::route('/create'),
            'edit' => Pages\EditMonitor::route('/{record}/edit'),
        ];
    }
}

