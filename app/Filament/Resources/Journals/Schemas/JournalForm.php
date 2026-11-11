<?php

namespace App\Filament\Resources\Journals\Schemas;

use App\Filament\Forms\Components\RealtimePhotoCapture;
use App\Filament\Resources\Journals\Pages\CreateJournal;
use App\Models\LeaveRequest;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JournalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('date')
                    ->default(now())
                    ->required()
                    ->label('Tanggal Jurnal')
                    ->maxDate(function () {
                        $user = Auth::user();
                        if ($user && ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin')) {
                            return null;
                        }

                        return Carbon::today();
                    })
                    ->minDate(function () {
                        $user = Auth::user();
                        if ($user && ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin')) {
                            return null;
                        }

                        return Carbon::today();
                    })
                    ->validationMessages([
                        'before_or_equal' => 'Jurnal mengajar hanya dapat diisi pada hari pembelajaran (hari ini). Pengisian untuk tanggal mendatang / besok tidak diperbolehkan.',
                        'after_or_equal' => 'Pengisian jurnal masa lalu tidak diperbolehkan. Jurnal hanya dapat diisi pada hari jadwal mengajar (hari ini).',
                    ])
                    ->helperText(function () {
                        $user = Auth::user();
                        if ($user && ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin')) {
                            return null;
                        }

                        return 'Pengisian jurnal hanya dapat dilakukan pada hari pembelajaran (hari ini).';
                    })
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $scheduleId = $get('schedule_id');
                        if ($scheduleId) {
                            $schedule = Schedule::find($scheduleId);
                            $newDay = $state ? match (Carbon::parse($state)->dayOfWeekIso) {
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                                7 => 'Minggu',
                                default => null,
                            } : null;

                            $isAlreadyFilled = $state ? Journal::where('schedule_id', $scheduleId)
                                ->whereDate('date', $state)
                                ->exists() : false;

                            if (! $schedule || $schedule->day_of_week !== $newDay || $isAlreadyFilled) {
                                $set('schedule_id', null);
                                $set('attendances', []);
                            } else {
                                $set('attendances', self::getStudentsAttendanceData((int) $scheduleId, $state));
                            }
                        }
                    }),

                Select::make('schedule_id')
                    ->relationship(
                        name: 'schedule',
                        titleAttribute: 'id',
                        modifyQueryUsing: function (Builder $query, Get $get, ?Model $record) {
                            $user = Auth::user();
                            $isSchoolAdmin = $user && ($user->hasRole('Super Admin')
                                || $user->role?->name === 'Super Admin'
                                || $user->hasRole('Waka Kurikulum')
                                || $user->role?->name === 'Waka Kurikulum');

                            if (! $isSchoolAdmin && $user) {
                                $query->where('teacher_id', $user->id);
                            }

                            $dateStr = $get('date') ?? ($record?->date ? $record->date->toDateString() : now()->toDateString());
                            $targetDate = filled($dateStr) ? Carbon::parse($dateStr) : Carbon::today();
                            $dayOfWeek = match ($targetDate->dayOfWeekIso) {
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                                7 => 'Minggu',
                                default => null,
                            };

                            $requestedScheduleId = request()->query('schedule_id');

                            if ($dayOfWeek) {
                                $query->where(function (Builder $q) use ($dayOfWeek, $record, $requestedScheduleId) {
                                    $q->where('day_of_week', $dayOfWeek);
                                    if ($record?->schedule_id) {
                                        $q->orWhere('id', $record->schedule_id);
                                    }
                                    if ($requestedScheduleId) {
                                        $q->orWhere('id', (int) $requestedScheduleId);
                                    }
                                });
                            }

                            // Saring jadwal yang sudah diisi jurnal pada tanggal pembelajaran ini
                            // Jika sedang mengedit ($record), jadwal milik jurnal yang sedang diedit tetap diizinkan
                            $query->whereDoesntHave('journals', function (Builder $jQ) use ($targetDate, $record) {
                                $jQ->whereDate('date', $targetDate);
                                if ($record) {
                                    $jQ->where('id', '!=', $record->id);
                                }
                            });

                            return $query->forActiveCycle()->with(['subject', 'classroom', 'room']);
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn (Schedule $record) => "{$record->subject?->name} - {$record->classroom?->name} ({$record->day_of_week}, ".substr($record->start_time, 0, 5).' - '.substr($record->end_time, 0, 5).')')
                    ->required()
                    ->label('Jadwal Mengajar')
                    ->preload()
                    ->searchable()
                    ->live()
                    ->rules([
                        fn (Get $get, ?Model $record) => function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            if (! $value) {
                                return;
                            }
                            if ($record?->schedule_id == $value) {
                                return;
                            }
                            $dateStr = $get('date') ?? ($record?->date ? $record->date->toDateString() : now()->toDateString());
                            $targetDate = filled($dateStr) ? Carbon::parse($dateStr) : Carbon::today();
                            $expectedDay = match ($targetDate->dayOfWeekIso) {
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                                7 => 'Minggu',
                                default => null,
                            };
                            $schedule = Schedule::find($value);
                            if ($schedule && $expectedDay && $schedule->day_of_week !== $expectedDay) {
                                $fail("Jadwal yang dipilih ({$schedule->day_of_week}) tidak sesuai dengan hari pembelajaran ({$expectedDay}).");
                                return;
                            }

                            // Validasi: 1 jadwal hanya bisa diisi 1 kali per tanggal
                            $isAlreadyFilled = Journal::where('schedule_id', $value)
                                ->whereDate('date', $targetDate)
                                ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                ->exists();

                            if ($isAlreadyFilled) {
                                $fail('Jurnal untuk jadwal mengajar ini pada tanggal yang dipilih sudah pernah diisi. Setiap jadwal hanya dapat diisi 1 kali per tanggal pembelajaran.');
                            }
                        },
                    ])
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $set('attendances', self::getStudentsAttendanceData($state ? (int) $state : null, $get('date')));
                    }),

                Hidden::make('teacher_id')
                    ->default(Auth::id()),

                Textarea::make('teaching_material')
                    ->required()
                    ->columnSpanFull()
                    ->label('Materi & Ringkasan Pembelajaran'),

                Textarea::make('notes')
                    ->columnSpanFull()
                    ->label('Catatan Tambahan / Kendala'),

                RealtimePhotoCapture::make('activity_photo')
                    ->image()
                    ->disk('public')
                    ->directory('journal_photos')
                    ->visibility('public')
                    ->maxSize(5120)
                    ->enableWatermark(true)
                    ->defaultFacingMode('environment')
                    ->captureType('journal')
                    ->columnSpanFull()
                    ->label('Bukti Foto Kegiatan Pembelajaran (Realtime)')
                    ->helperText('Ambil foto kegiatan pembelajaran secara langsung menggunakan kamera perangkat (kamera depan/belakang). Foto wajib diambil secara realtime.')
                    ->required(fn () => ! app()->runningUnitTests())
                    ->validationMessages([
                        'required' => 'Bukti foto kegiatan mengajar wajib diambil secara realtime.',
                    ]),

                Section::make('Daftar Presensi Siswa')
                    ->description('Daftar presensi siswa otomatis dimuat berdasarkan kelas pada jadwal yang dipilih.')
                    ->columnSpanFull()
                    ->headerActions([
                        Action::make('markAllHadir')
                            ->label('Set Semua Hadir')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->action(function (Set $set, Get $get) {
                                $attendances = $get('attendances') ?? [];
                                foreach ($attendances as &$item) {
                                    $item['status'] = 'Hadir';
                                }
                                $set('attendances', $attendances);
                            })
                            ->visible(fn (Get $get) => ! empty($get('attendances'))),
                    ])
                    ->schema([
                        Placeholder::make('no_students')
                            ->hiddenLabel()
                            ->content('Belum ada siswa yang terdaftar di kelas pada jadwal ini.')
                            ->visible(fn (Get $get) => empty($get('attendances'))),

                        Repeater::make('attendances')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->compact()
                            ->table([
                                TableColumn::make('Nama Siswa')
                                    ->width('260px'),
                                TableColumn::make('Status Kehadiran')
                                    ->width('200px'),
                                TableColumn::make('Catatan / Keterangan'),
                            ])
                            ->schema([
                                Hidden::make('student_id'),
                                TextInput::make('student_name')
                                    ->hiddenLabel()
                                    ->readOnly()
                                    ->extraAttributes([
                                        'class' => '[&_.fi-input-wrp]:!border-0 [&_.fi-input-wrp]:!bg-transparent [&_.fi-input-wrp]:!shadow-none [&_.fi-input-wrp]:!ring-0',
                                    ])
                                    ->extraInputAttributes([
                                        'class' => '!border-0 !bg-transparent !shadow-none !ring-0 font-medium text-gray-950 dark:text-gray-100 cursor-default focus:!ring-0 !p-0',
                                    ]),
                                Select::make('status')
                                    ->hiddenLabel()
                                    ->options([
                                        'Hadir' => 'Hadir',
                                        'Sakit' => 'Sakit',
                                        'Izin' => 'Izin',
                                        'Alpa' => 'Alpa',
                                    ])
                                    ->required()
                                    ->selectablePlaceholder(false),
                                TextInput::make('notes')
                                    ->hiddenLabel()
                                    ->placeholder('Tambahkan catatan...'),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->visible(fn (Get $get) => ! empty($get('attendances'))),
                    ])
                    ->visible(fn ($livewire, Get $get) => $livewire instanceof CreateJournal && filled($get('schedule_id'))),
            ]);
    }

    /**
     * Ambil data siswa di kelas beserta status awal presensi (sinkronisasi surat izin jika ada).
     *
     * @return array<int, array{student_id: int, student_name: string, status: string, notes: ?string}>
     */
    public static function getStudentsAttendanceData(?int $scheduleId, ?string $date = null): array
    {
        if (! $scheduleId) {
            return [];
        }

        $schedule = Schedule::with('classroom')->find($scheduleId);
        $classroomId = $schedule?->classroom_id;
        if (! $classroomId) {
            return [];
        }

        $students = User::where(function ($query) {
            $query->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
                ->orWhereHas('roles', fn ($q) => $q->where('name', 'Siswa'));
        })
            ->where('status', 'Aktif')
            ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroomId)->where('user_class.status', 'Aktif'))
            ->orderBy('full_name')
            ->get();

        if ($students->isEmpty()) {
            return [];
        }

        $targetDate = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();

        $approvedLeaves = LeaveRequest::whereIn('student_id', $students->pluck('id'))
            ->where('status', 'Approved')
            ->whereDate('start_date', '<=', $targetDate)
            ->whereDate('end_date', '>=', $targetDate)
            ->get()
            ->keyBy('student_id');

        $attendances = [];
        foreach ($students as $student) {
            $leave = $approvedLeaves->get($student->id);
            $status = $leave ? match (strtolower(str_replace(' ', '_', (string) $leave->reason_type))) {
                'sakit' => 'Sakit',
                default => 'Izin',
            } : 'Hadir';
            $notes = $leave ? 'Otomatis tersinkronisasi dari Surat Izin (Approved oleh Wali Kelas)' : null;

            $attendances[] = [
                'student_id' => $student->id,
                'student_name' => $student->full_name,
                'status' => $status,
                'notes' => $notes,
            ];
        }

        return $attendances;
    }
}
