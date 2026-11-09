<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AttendanceReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Presensi';

    protected static ?string $title = 'Laporan Rekapitulasi Presensi Siswa';

    protected static string|UnitEnum|null $navigationGroup = 'Laporan & Rekap';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.attendance-report';

    public ?int $classroom_id = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public function getAvailableClassrooms(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $isSuperAdminOrWaka = $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';

        if ($isSuperAdminOrWaka) {
            return Classroom::query()->orderBy('name')->pluck('name', 'id')->toArray();
        }

        // Untuk Walikelas dan Guru BK: hanya menampilkan kelas yang diampu
        $userClassrooms = $user->classrooms()
            ->wherePivot('status', 'Aktif')
            ->orderBy('name')
            ->pluck('classrooms.name', 'classrooms.id')
            ->toArray();

        if (empty($userClassrooms)) {
            $userClassrooms = $user->classrooms()
                ->orderBy('name')
                ->pluck('classrooms.name', 'classrooms.id')
                ->toArray();
        }

        if (empty($userClassrooms) && $user->classroom_id && $user->classroom) {
            $userClassrooms = [$user->classroom_id => $user->classroom->name];
        }

        if (empty($userClassrooms)) {
            return Classroom::query()->orderBy('name')->pluck('name', 'id')->toArray();
        }

        return $userClassrooms;
    }

    public function mount(): void
    {
        $this->start_date = Carbon::now()->startOfMonth()->toDateString();
        $this->end_date = Carbon::now()->endOfMonth()->toDateString();

        $availableClassrooms = $this->getAvailableClassrooms();
        $this->classroom_id = ! empty($availableClassrooms) ? (int) array_key_first($availableClassrooms) : null;

        $this->form->fill([
            'classroom_id' => $this->classroom_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('View:AttendanceReport') ?? false;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('classroom_id')
                    ->label('Pilih Kelas')
                    ->options(fn () => $this->getAvailableClassrooms())
                    ->required()
                    ->live(),

                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->live(),

                DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->live(),
            ])
            ->columns(3);
    }

    public function getReportDataProperty(): array
    {
        if (! $this->classroom_id || ! $this->start_date || ! $this->end_date) {
            return [];
        }

        $availableClassrooms = $this->getAvailableClassrooms();
        if (! array_key_exists((int) $this->classroom_id, $availableClassrooms) && ! array_key_exists((string) $this->classroom_id, $availableClassrooms)) {
            return [];
        }

        $students = User::where('status', 'Aktif')
            ->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
            ->whereHas('classrooms', function ($q) {
                $q->where('classrooms.id', $this->classroom_id)
                    ->where('user_class.status', 'Aktif');
            })->get();
        $selectedClassroom = Classroom::find($this->classroom_id);

        $report = [];

        // Eager-load semua presensi siswa dalam periode terpilih sekaligus (Mencegah N+1 Query)
        $attendancesGrouped = Attendance::whereIn('student_id', $students->pluck('id'))
            ->whereHas('journal', function ($query) {
                $query->whereBetween('date', [$this->start_date, $this->end_date]);
            })
            ->get()
            ->groupBy('student_id');

        foreach ($students as $student) {
            $attendances = $attendancesGrouped->get($student->id, collect());

            $hadir = $attendances->where('status', 'Hadir')->count();
            $sakit = $attendances->where('status', 'Sakit')->count();
            $izin = $attendances->where('status', 'Izin')->count();
            $alpa = $attendances->where('status', 'Alpa')->count();
            $totalPertemuan = $attendances->count();

            $percentage = $totalPertemuan > 0 ? round(($hadir / $totalPertemuan) * 100, 1) : 100;

            $report[] = [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'username' => $student->username,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total' => $totalPertemuan,
                'percentage' => $percentage,
            ];
        }

        return [
            'classroom' => $selectedClassroom,
            'start_date' => Carbon::parse($this->start_date)->isoFormat('D MMMM Y'),
            'end_date' => Carbon::parse($this->end_date)->isoFormat('D MMMM Y'),
            'students' => $report,
        ];
    }
}
