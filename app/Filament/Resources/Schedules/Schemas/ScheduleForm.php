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
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if ($state === 'Jumat') {
                            $jumatSessions = self::getSessions('Jumat');
                            $validStarts = array_column($jumatSessions, 'start');
                            $validEnds = array_column($jumatSessions, 'end');

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
                                    $targetNum = $num + 1; // Default 2 sesi (90 menit) jika tersedia dalam batasan sesi
                                    $set('end_time', $sessions[$targetNum]['end'] ?? $session['end']);
                                    break;
                                }
                            }
                        }
                    })
                    ->formatStateUsing(fn ($state) => $state ? substr((string) $state, 0, 5) : null)
                    ->rules([
                        fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                            if ($get('day_of_week') === 'Jumat' && $value >= '13:50') {
                                $fail('Jam mulai hari Jumat maksimal pada Jam ke - 8 (13:15).');
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
                            if ($get('day_of_week') === 'Jumat' && $value > '13:50') {
                                $fail('Jadwal hari Jumat maksimal hanya sampai Jam ke - 8 (13:50).');
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
        if ($dayOfWeek === 'Jumat') {
            return [
                1 => ['start' => '07:15', 'end' => '07:55', 'label' => 'Jam ke - 1'],
                2 => ['start' => '07:55', 'end' => '08:35', 'label' => 'Jam ke - 2'],
                3 => ['start' => '08:35', 'end' => '09:15', 'label' => 'Jam ke - 3'],
                4 => ['start' => '09:30', 'end' => '10:10', 'label' => 'Jam ke - 4'],
                5 => ['start' => '10:10', 'end' => '10:50', 'label' => 'Jam ke - 5'],
                6 => ['start' => '10:50', 'end' => '11:25', 'label' => 'Jam ke - 6'],
                7 => ['start' => '12:40', 'end' => '13:15', 'label' => 'Jam ke - 7'],
                8 => ['start' => '13:15', 'end' => '13:50', 'label' => 'Jam ke - 8'],
            ];
        }

        return [
            1 => ['start' => '07:15', 'end' => '07:55', 'label' => 'Jam ke - 1'],
            2 => ['start' => '07:55', 'end' => '08:35', 'label' => 'Jam ke - 2'],
            3 => ['start' => '08:35', 'end' => '09:15', 'label' => 'Jam ke - 3'],
            4 => ['start' => '09:30', 'end' => '10:10', 'label' => 'Jam ke - 4'],
            5 => ['start' => '10:10', 'end' => '10:50', 'label' => 'Jam ke - 5'],
            6 => ['start' => '10:50', 'end' => '11:30', 'label' => 'Jam ke - 6'],
            7 => ['start' => '12:30', 'end' => '13:10', 'label' => 'Jam ke - 7'],
            8 => ['start' => '13:10', 'end' => '13:50', 'label' => 'Jam ke - 8'],
            9 => ['start' => '13:50', 'end' => '14:30', 'label' => 'Jam ke - 9'],
            10 => ['start' => '14:30', 'end' => '15:10', 'label' => 'Jam ke - 10'],
        ];
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
