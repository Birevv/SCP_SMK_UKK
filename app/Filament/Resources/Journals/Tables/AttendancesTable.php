<?php

namespace App\Filament\Resources\Journals\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttendancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('student.full_name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Siswa'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Hadir' => 'success',
                        'Sakit' => 'warning',
                        'Izin' => 'info',
                        'Alpa' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status'),

                TextColumn::make('notes')
                    ->label('Catatan / Keterangan'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Sakit' => 'Sakit',
                        'Izin' => 'Izin',
                        'Alpa' => 'Alpa',
                    ]),
            ]);
    }
}
