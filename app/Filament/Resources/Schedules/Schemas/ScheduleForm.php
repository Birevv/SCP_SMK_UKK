<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Rules\NoScheduleConflict;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ScheduleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('teacher_id')
                    ->relationship(
                        name: 'teacher',
                        titleAttribute: 'full_name',
                        modifyQueryUsing: fn ($query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Guru'))
                    )
                    ->default(fn () => Auth::user()?->role?->name === 'Guru' ? Auth::id() : null)
                    ->disabled(fn () => Auth::user()?->role?->name === 'Guru')
                    ->dehydrated()
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Guru Pengajar')
                    ->rules([
                        fn (Get $get, ?Schedule $record): NoScheduleConflict => new NoScheduleConflict(
                            dayOfWeek: (string) $get('day_of_week'),
                            startTime: (string) $get('start_time'),
                            endTime: (string) $get('end_time'),
                            cycleType: (string) $get('cycle_type'),
                            teacherId: $get('teacher_id') ? (int) $get('teacher_id') : (Auth::user()?->role?->name === 'Guru' ? Auth::id() : null),
                            ignoreId: $record?->id,
                            type: 'teacher',
                        ),
                    ]),

                Select::make('classroom_id')
                    ->relationship('classroom', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Kelas')
                    ->rules([
                        fn (Get $get, ?Schedule $record): NoScheduleConflict => new NoScheduleConflict(
                            dayOfWeek: (string) $get('day_of_week'),
                            startTime: (string) $get('start_time'),
                            endTime: (string) $get('end_time'),
                            cycleType: (string) $get('cycle_type'),
                            classroomId: $get('classroom_id') ? (int) $get('classroom_id') : null,
                            ignoreId: $record?->id,
                            type: 'classroom',
                        ),
                    ]),

                Select::make('room_id')
                    ->relationship('room', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->label('Ruangan')
                    ->rules([
                        fn (Get $get, ?Schedule $record): NoScheduleConflict => new NoScheduleConflict(
                            dayOfWeek: (string) $get('day_of_week'),
                            startTime: (string) $get('start_time'),
                            endTime: (string) $get('end_time'),
                            cycleType: (string) $get('cycle_type'),
                            roomId: $get('room_id') ? (int) $get('room_id') : null,
                            ignoreId: $record?->id,
                            type: 'room',
                        ),
                    ]),

                Select::make('academic_year_id')
                    ->label('Tahun Ajaran')
                    ->relationship('academicYear', 'name')
                    ->default(fn () => AcademicYear::getActiveId())
                    ->helperText('Otomatis terisi sesuai Tahun Ajaran yang aktif.')
                    ->required(),

                Select::make('subject_id')
                    ->label('Mata Pelajaran')
                    ->relationship('subject', 'name')
                    ->required(),
                Select::make('day_of_week')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                    ])
                    ->required()
                    ->label('Hari'),

                Select::make('start_time')
                    ->label('Jam Mulai')
                    ->prefixIcon('heroicon-o-clock')
                    ->options(fn () => self::getTimeOptions())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if (! $state) {
                            return;
                        }
                        $currentEnd = $get('end_time');
                        if (! $currentEnd || $currentEnd <= $state) {
                            $suggestedEnd = date('H:i', strtotime('+2 hours', strtotime("2000-01-01 {$state}")));
                            if ($suggestedEnd <= '18:00') {
                                $set('end_time', $suggestedEnd);
                            }
                        }
                    })
                    ->formatStateUsing(fn ($state) => $state ? substr((string) $state, 0, 5) : null)
                    ->required(),

                Select::make('end_time')
                    ->label('Jam Selesai')
                    ->prefixIcon('heroicon-o-clock')
                    ->options(fn (Get $get) => self::getTimeOptions($get('start_time')))
                    ->searchable()
                    ->live()
                    ->helperText(function (Get $get) {
                        $start = $get('start_time');
                        $end = $get('end_time');
                        if ($start && $end && $end > $start) {
                            $diffMinutes = (strtotime("2000-01-01 {$end}") - strtotime("2000-01-01 {$start}")) / 60;
                            $hours = floor($diffMinutes / 60);
                            $mins = $diffMinutes % 60;
                            $durStr = ($hours > 0 ? "{$hours} Jam " : '').($mins > 0 ? "{$mins} Menit" : '');

                            return 'Durasi Belajar: '.trim($durStr)." ({$diffMinutes} menit)";
                        }

                        return null;
                    })
                    ->after('start_time')
                    ->formatStateUsing(fn ($state) => $state ? substr((string) $state, 0, 5) : null)
                    ->required(),

                Select::make('cycle_type')
                    ->options([
                        'Putaran 1' => 'Putaran 1',
                        'Putaran 2' => 'Putaran 2',
                    ])
                    ->default(fn () => SystemConfiguration::current()->active_cycle ?? 'Putaran 1')
                    ->required()
                    ->label('Putaran Siklus'),
            ]);
    }

    public static function getTimeOptions(?string $minTime = null): array
    {
        $options = [];
        $start = strtotime('06:30');
        $end = strtotime('18:00');

        while ($start <= $end) {
            $time24 = date('H:i', $start);

            if ($minTime && $time24 <= $minTime) {
                $start = strtotime('+15 minutes', $start);

                continue;
            }

            $h = (int) date('H', $start);
            $m = date('i', $start);
            $lh = ($h > 12) ? ($h - 12) : ($h === 0 ? 12 : $h);
            $period = match (true) {
                $h < 11 => 'Pagi',
                $h < 15 => 'Siang',
                default => 'Sore',
            };
            $ms = $m !== '00' ? ".{$m}" : '';
            $options[$time24] = "{$time24}  —  Jam {$lh}{$ms} {$period}";

            $start = strtotime('+15 minutes', $start);
        }

        return $options;
    }
}
