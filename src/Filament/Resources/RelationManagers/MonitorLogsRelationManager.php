<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Resources\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use AxelvdS\UptimeMonitorExtended\Models\MonitorLog;

class MonitorLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'monitorLogs';

    protected static ?string $recordTitleAttribute = 'id';

    /**
     * Check if this relation manager can be viewed for the given record.
     * Override to bypass the check that tries to call the relationship method on the model.
     */
    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        // Since we can't modify Spatie's Monitor model, we always return true
        // The relationship is created dynamically in getRelationship()
        return true;
    }

    /**
     * Get the relationship.
     * Since we can't modify Spatie's Monitor model, we create a dynamic relationship.
     */
    public function getRelationship(): \Illuminate\Database\Eloquent\Relations\Relation
    {
        $owner = $this->getOwnerRecord();
        
        // Create a dynamic hasMany relationship
        return $owner->hasMany(
            \AxelvdS\UptimeMonitorExtended\Models\MonitorLog::class,
            'monitor_id',
            'id'
        );
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('response_time_ms')
                    ->maxLength(255),
                Forms\Components\Textarea::make('error_message')
                    ->maxLength(65535),
                Forms\Components\DateTimePicker::make('checked_at')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'up' => 'success',
                        'down' => 'danger',
                        'ssl_issue' => 'warning',
                        'ssl_expiring' => 'info',
                        default => 'secondary',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('response_time_ms')
                    ->label('Response Time')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' ms' : '-')
                    ->sortable(),
                Tables\Columns\TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->wrap(),
                Tables\Columns\TextColumn::make('checked_at')
                    ->label('Checked At')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'up' => 'Up',
                        'down' => 'Down',
                        'ssl_issue' => 'SSL Issue',
                        'ssl_expiring' => 'SSL Expiring',
                    ]),
            ])
            ->headerActions([
                // No create action - logs are created automatically by monitoring checks
            ])
            ->actions([
                // No edit/delete actions - logs are read-only
            ])
            ->bulkActions([
                // No bulk actions - logs are read-only
            ])
            ->defaultSort('checked_at', 'desc');
    }
}

