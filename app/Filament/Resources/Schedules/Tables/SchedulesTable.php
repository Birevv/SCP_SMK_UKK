<?php

namespace App\Filament\Resources\Schedules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicYear.name')
                    ->label('Tahun Ajaran')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('teacher.full_name')
                    ->searchable()
                    ->label('Guru'),
                
                TextColumn::make('classroom.name')
                    ->sortable()
                    ->label('Kelas'),

                TextColumn::make('room.name')
                    ->sortable()
                    ->label('Ruangan'),

                TextColumn::make('day_of_week')
                    ->badge()
                    ->color('info')
                    ->label('Hari'),

                TextColumn::make('start_time')
                    ->time('H:i')
                    ->label('Waktu Mulai'),

                TextColumn::make('end_time')
                    ->time('H:i')
                    ->label('Waktu Selesai'),

                TextColumn::make('cycle_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Putaran 1' => 'primary',
                        'Putaran 2' => 'warning',
                        default => 'gray',
                    })
                    ->label('Putaran Siklus'),

            ])
            ->filters([
                SelectFilter::make('day_of_week')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                    ]),
                
                SelectFilter::make('cycle_type')
                    ->options([
                        'Putaran 1' => 'Putaran 1',
                        'Putaran 2' => 'Putaran 2',
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
