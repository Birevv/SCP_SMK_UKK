<?php

namespace App\Filament\Resources\SystemConfigurations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SystemConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('active_cycle')
                    ->label('Putaran Aktif')
                    ->color(fn (string $state): string => match($state) {
                        'Putaran 1' => 'success',
                        'Putaran 2' => 'danger',
                        default => 'gray',    
                    })
                    ->badge(),
                IconColumn::make('is_schedule_locked')
                    ->label('Status Jadwal')
                    ->boolean(),
                TextColumn::make('updatedBy.full_name')
                    ->label('Diubah Oleh'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Terakhir Diubah'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
