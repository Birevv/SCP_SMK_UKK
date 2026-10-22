<?php

namespace App\Filament\Resources\Schedules\Schemas;

use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\SchoolSession;
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
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if ($state) {
                            $sessions = self::getSessions($state);
                            $validStarts = array_column($sessions, 'start');
                            $validEnds = array_column($sessions, 'end');

                            $currentStart = $get('start_time');
                            $currentEnd = $get('end_time');

                            if ($currentStart && ! in_array(substr((string) $currentStart, 0, 5), $validStarts)) {
                                $set('start_time', null);
                                $set('end_time', null);
                            } elseif ($currentEnd && ! in_array(substr((string) $currentEnd, 0, 5), $validEnds)) {
                                $set('end_time', null);
                            }
                        }
                    })
                    ->label('Hari'),

                Select::make('start_time')
                    ->label('Jam Mulai')
                    ->prefixIcon('heroicon-o-clock')
                    ->options(fn (Get $get, ?Schedule $record) => self::getStartTimeOptions($record?->start_time, $get('day_of_week')))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if (! $state) {
                            return;
                        }
                        $day = $get('day_of_week');
                        $state = substr((string) $state, 0, 5);
                        $currentEnd = $get('end_time');
                        $sessions = self::getSessions($day);

                        if (! $currentEnd || $currentEnd <= $state) {
                            foreach ($sessions as $num => $session) {
                                if ($session['start'] === $state) {
                                    $targetNum = $num + 1; // Default 2 sesi jika tersedia dalam batasan sesi
                                    $set('end_time', $sessions[$targetNum]['end'] ?? $session['end']);
                                    break;
                                }
                            }
                        }
                    })
                    ->formatStateUsing(fn ($state) => $state ? substr((string) $state, 0, 5) : null)
                    ->rules([
                        fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $day = $get('day_of_week');
                            $sessions = self::getSessions($day);
                            if (! empty($sessions)) {
                                $lastSession = end($sessions);
                                if ($value > $lastSession['start']) {
                                    $fail("Jam mulai hari {$day} maksimal pada {$lastSession['label']} ({$lastSession['start']}).");
                                }
                            }
                        },
                    ])
                    ->required(),

                Select::make('end_time')
                    ->label('Jam Selesai')
                    ->prefixIcon('heroicon-o-clock')
                    ->options(fn (Get $get, ?Schedule $record) => self::getEndTimeOptions($get('start_time'), $record?->end_time, $get('day_of_week')))
                    ->searchable()
                    ->live()
                    ->helperText(function (Get $get) {
                        $start = $get('start_time');
                        $end = $get('end_time');
                        $day = $get('day_of_week');
                        if ($start && $end && $end > $start) {
                            $diffMinutes = (strtotime("2000-01-01 {$end}") - strtotime("2000-01-01 {$start}")) / 60;
                            $sessions = self::getSessions($day);
                            $sessionCount = 0;
                            foreach ($sessions as $session) {
                                if ($session['start'] >= $start && $session['end'] <= $end) {
                                    $sessionCount++;
                                }
                            }
                            if ($sessionCount > 0) {
                                return "Durasi Belajar: {$sessionCount} Sesi ({$diffMinutes} Menit)";
                            }

                            return "Durasi Belajar: {$diffMinutes} Menit";
                        }

                        return null;
                    })
                    ->after('start_time')
                    ->formatStateUsing(fn ($state) => $state ? substr((string) $state, 0, 5) : null)
                    ->rules([
                        fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $day = $get('day_of_week');
                            $sessions = self::getSessions($day);
                            if (! empty($sessions)) {
                                $lastSession = end($sessions);
                                if ($value > $lastSession['end']) {
                                    $fail("Jadwal hari {$day} maksimal hanya sampai {$lastSession['label']} ({$lastSession['end']}).");
                                }
                            }
                        },
                    ])
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

    public static function getSessions(?string $dayOfWeek = null): array
    {
        return SchoolSession::getSessionsForDay($dayOfWeek);
    }

    public static function getStartTimeOptions(?string $currentValue = null, ?string $dayOfWeek = null): array
    {
        $options = [];
        foreach (self::getSessions($dayOfWeek) as $num => $session) {
            $options[$session['start']] = "{$session['label']} ({$session['start']})";
        }

        if ($currentValue) {
            $formatted = substr($currentValue, 0, 5);
            if (! isset($options[$formatted])) {
                $options[$formatted] = "{$formatted} (Waktu Kustom)";
                ksort($options);
            }
        }

        return $options;
    }

    public static function getEndTimeOptions(?string $startTime = null, ?string $currentValue = null, ?string $dayOfWeek = null): array
    {
        $options = [];
        $startTime = $startTime ? substr($startTime, 0, 5) : null;

        foreach (self::getSessions($dayOfWeek) as $num => $session) {
            if ($startTime && $session['end'] <= $startTime) {
                continue;
            }
            $options[$session['end']] = "Selesai {$session['label']} ({$session['end']})";
        }

        if ($currentValue) {
            $formatted = substr($currentValue, 0, 5);
            if (! isset($options[$formatted])) {
                $options[$formatted] = "Selesai {$formatted} (Waktu Kustom)";
                ksort($options);
            }
        }

        return $options;
    }
}
