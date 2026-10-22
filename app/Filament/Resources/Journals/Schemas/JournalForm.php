<?php

namespace App\Filament\Resources\Journals\Schemas;

use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
use Illuminate\Support\Facades\Auth;

class JournalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('schedule_id')
                    ->relationship(
                        name: 'schedule',
                        titleAttribute: 'id',
                        modifyQueryUsing: function (Builder $query) {
                            $user = Auth::user();
                            if ($user && $user->role?->name === 'Guru') {
                                $query->where('teacher_id', $user->id);
                            }

                            return $query->forActiveCycle()->with(['subject', 'classroom', 'room']);
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn (Schedule $record) => "{$record->subject?->name} - {$record->classroom?->name} ({$record->day_of_week}, ".substr($record->start_time, 0, 5).' - '.substr($record->end_time, 0, 5).')')
                    ->required()
                    ->label('Jadwal Mengajar')
                    ->preload()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $set('attendances', self::getStudentsAttendanceData($state ? (int) $state : null, $get('date')));
                    }),

                Hidden::make('teacher_id')
                    ->default(Auth::id()),

                DatePicker::make('date')
                    ->default(now())
                    ->required()
                    ->label('Tanggal Jurnal')
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if ($scheduleId = $get('schedule_id')) {
                            $set('attendances', self::getStudentsAttendanceData((int) $scheduleId, $state));
                        }
                    }),

                Textarea::make('teaching_material')
                    ->required()
                    ->columnSpanFull()
                    ->label('Materi & Ringkasan Pembelajaran'),

                Textarea::make('notes')
                    ->columnSpanFull()
                    ->label('Catatan Tambahan / Kendala'),

                FileUpload::make('activity_photo')
                    ->image()
                    ->disk('public')
                    ->directory('journal_photos')
                    ->visibility('public')
                    ->maxSize(5120)
                    ->columnSpanFull()
                    ->label('Bukti Foto Kegiatan (Realtime)'),

                Section::make('Daftar Presensi Siswa')
                    ->description('Daftar presensi siswa otomatis dimuat berdasarkan kelas pada jadwal yang dipilih.')
                    ->columnSpanFull()
                    ->headerActions([
                        \Filament\Actions\Action::make('markAllHadir')
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
                    ->visible(fn ($livewire, Get $get) => $livewire instanceof \App\Filament\Resources\Journals\Pages\CreateJournal && filled($get('schedule_id'))),
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
            ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroomId))
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
            $status = $leave ? $leave->reason_type : 'Hadir';
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
