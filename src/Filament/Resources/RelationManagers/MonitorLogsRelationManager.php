<?php

namespace AxelvdS\UptimeMonitorExtended\Filament\Resources\RelationManagers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms;
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
    public function getRelationship(): Relation
    {
        $owner = $this->getOwnerRecord();
        
        // Create a dynamic hasMany relationship
        return $owner->hasMany(
            MonitorLog::class,
            'monitor_id',
            'id'
        );
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('status')
                    ->required()
                    ->maxLength(255),
                TextInput::make('response_time_ms')
                    ->maxLength(255),
                Textarea::make('error_message')
                    ->maxLength(65535),
                DateTimePicker::make('checked_at')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('status')
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
                TextColumn::make('response_time_ms')
                    ->label('Response Time')
                    ->formatStateUsing(fn ($state) => $state ? $state . ' ms' : '-')
                    ->sortable(),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->error_message)
                    ->wrap(),
                TextColumn::make('checked_at')
                    ->label('Checked At')
                    ->dateTime()
                    ->sortable()
                    ->since(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                SelectFilter::make('status')
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

