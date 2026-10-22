<?php

namespace App\Filament\Resources\SchoolSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SchoolSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('day_of_week')
                    ->label('Hari')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Senin' => 'primary',
                        'Selasa' => 'info',
                        'Rabu' => 'warning',
                        'Kamis' => 'success',
                        'Jumat' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('session_number')
                    ->label('Jam Ke-')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('label')
                    ->label('Label Sesi')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Jam Mulai')
                    ->time('H:i')
                    ->icon('heroicon-o-clock')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('Jam Selesai')
                    ->time('H:i')
                    ->icon('heroicon-o-clock')
                    ->sortable(),

                TextColumn::make('duration_minutes')
                    ->label('Durasi')
                    ->formatStateUsing(fn ($state) => "{$state} menit")
                    ->badge()
                    ->color('success')
                    ->sortable(),
            ])
            ->defaultSort('session_number')
            ->filters([
                SelectFilter::make('day_of_week')
                    ->label('Filter Hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
