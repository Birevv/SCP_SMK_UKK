<?php

namespace App\Filament\Resources\Schedules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SchedulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject_name')
                    ->searchable()
                    ->sortable()
                    ->label('Mata Pelajaran'),

                TextColumn::make('teacher.full_name')
                    ->searchable()
                    ->label('Guru'),
                
                TextColumn::make('classroom.name')
                    ->sortable()
                    ->label('Kelas'),

                TextColumn::make('room.name')
                    ->sortable()
                    ->label('Ruangan'),

                BadgeColumn::make('day_of_week')
                    ->color('info')
                    ->label('hari'),

                TextColumn::make('start_time')
                    ->label('Waktu Mulai'),

                TextColumn::make('end_time')
                    ->label('Waktu Selesai'),

                BadgeColumn::make('cycle_type')
                    ->colors([
                        'primary' => 'Putaran 1',
                        'warning' => 'Putaran 2',
                    ])
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
