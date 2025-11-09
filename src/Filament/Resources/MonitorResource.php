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

    protected static ?string $navigationGroup = 'Monitoring';

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
                    ->label('URL or IP Address')
                    ->required()
                    ->helperText('Enter a URL (http:// or https://) or IP address for ping monitoring')
                    ->maxLength(255),

                Forms\Components\Select::make('monitor_type')
                    ->label('Monitor Type')
                    ->options([
                        'https' => 'HTTPS',
                        'http' => 'HTTP',
                        'ping' => 'Ping (ICMP)',
                    ])
                    ->default('https')
                    ->required()
                    ->helperText('Select the type of monitoring to perform'),

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
                    ->copyable(),

                Tables\Columns\TextColumn::make('monitor_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'https' => 'primary',
                        'http' => 'success',
                        'ping' => 'warning',
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
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),

                Tables\Filters\Filter::make('status')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'up' => 'Up',
                                'down' => 'Down',
                                'ssl_expired' => 'SSL Expired',
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['status'])) {
                            $monitorIds = \AxelvdS\UptimeMonitorExtended\Models\MonitorLog::select('monitor_id')
                                ->where('status', $data['status'])
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

