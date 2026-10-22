<?php

namespace App\Filament\Resources\Schedules\Tables;

use App\Models\ClassLevel;
use App\Models\Major;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('teacher.full_name')
                    ->searchable()
                    ->label('Guru'),

                TextColumn::make('classroom.name')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->label('Kelas'),

                TextColumn::make('classroom.major.code')
                    ->label('Jurusan')
                    ->badge()
                    ->color('gray')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('room.name')
                    ->sortable()
                    ->label('Ruangan'),

                TextColumn::make('day_of_week')
                    ->badge()
                    ->color('primary')
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
                SelectFilter::make('class_level')
                    ->label('Tingkat Kelas')
                    ->options(function () {
                        $levels = ClassLevel::orderBy('level')->pluck('name', 'name')->toArray();
                        if (empty($levels)) {
                            return [
                                'X' => 'Kelas X (10)',
                                'XI' => 'Kelas XI (11)',
                                'XII' => 'Kelas XII (12)',
                                'Lulus' => 'Lulus',
                            ];
                        }

                        return $levels;
                    })
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->forGrade($data['value'])
                        : $query
                    ),

                SelectFilter::make('major')
                    ->label('Jurusan')
                    ->searchable()
                    ->preload()
                    ->options(function () {
                        $majors = Major::orderBy('code')->pluck('name', 'code')->toArray();
                        if (empty($majors)) {
                            return [
                                'RPL' => 'RPL - Rekayasa Perangkat Lunak',
                                'AKL' => 'AKL - Akuntansi dan Keuangan Lembaga',
                                'TKJ' => 'TKJ - Teknik Komputer dan Jaringan',
                            ];
                        }

                        return collect($majors)->mapWithKeys(fn ($name, $code) => [$code => "{$code} - {$name}"])->toArray();
                    })
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->forMajor($data['value'])
                        : $query
                    ),

                SelectFilter::make('classroom_id')
                    ->label('Kelas Spesifik')
                    ->relationship('classroom', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('day_of_week')
                    ->label('Hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                    ]),

                SelectFilter::make('cycle_type')
                    ->label('Putaran')
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
