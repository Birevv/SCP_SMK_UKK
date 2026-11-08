<?php

namespace App\Filament\Widgets;

use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\Schedule;
use App\Models\SchoolSession;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class WakaScheduleWidget extends Widget
{
    protected string $view = 'filament.widgets.waka-schedule-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public ?string $selectedMajor = 'ALL';

    public ?string $selectedGrade = 'ALL';

    public ?string $selectedClassroom = 'ALL';

    public ?string $selectedDate = null;

    public ?string $selectedDay = null;

    public ?string $selectedCycle = null;

    public ?string $selectedStatus = 'ALL';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user() ?? Filament::auth()?->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin') {
            return true;
        }

        if (Permission::where('name', 'View:WakaScheduleWidget')->exists()) {
            return $user->can('View:WakaScheduleWidget');
        }

        return $user->hasRole('Waka Kurikulum') || $user->role?->name === 'Waka Kurikulum';
    }

    public function mount(): void
    {
        $config = SystemConfiguration::current();
        $this->selectedCycle = $config->active_cycle ?? 'Putaran 1';
        $this->selectedDate = Carbon::today()->toDateString();

        $dayIndex = Carbon::now()->dayOfWeekIso;
        $this->selectedDay = match ($dayIndex) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            default => 'Senin',
        };
    }

    public function setMajor(string $major): void
    {
        $this->selectedMajor = $major;
        $this->selectedClassroom = 'ALL';
    }

    public function setGrade(string $grade): void
    {
        $this->selectedGrade = $grade;
        $this->selectedClassroom = 'ALL';
    }

    public function updatedSelectedGrade(): void
    {
        $this->selectedClassroom = 'ALL';
    }

    public function updatedSelectedDate(?string $date): void
    {
        if (filled($date)) {
            try {
                $dayIndex = Carbon::parse($date)->dayOfWeekIso;
                $mappedDay = match ($dayIndex) {
                    1 => 'Senin',
                    2 => 'Selasa',
                    3 => 'Rabu',
                    4 => 'Kamis',
                    5 => 'Jumat',
                    default => null,
                };
                if ($mappedDay) {
                    $this->selectedDay = $mappedDay;
                }
            } catch (\Throwable) {
                // Ignore invalid date input
            }
        }
    }

    public function setDay(string $day): void
    {
        $this->selectedDay = $day;
    }

    public function setStatus(string $status): void
    {
        $this->selectedStatus = $status;
    }

    public function resetFilters(): void
    {
        $this->selectedMajor = 'ALL';
        $this->selectedGrade = 'ALL';
        $this->selectedClassroom = 'ALL';
        $this->selectedStatus = 'ALL';
        $this->mount();
    }

    protected function getViewData(): array
    {
        $config = SystemConfiguration::current();
        $targetDate = filled($this->selectedDate)
            ? Carbon::parse($this->selectedDate)->startOfDay()
            : Carbon::today();
        $now = Carbon::now();

        $majors = Major::orderBy('code')->get();
        $grades = ClassLevel::orderBy('level')->get();

        $classroomsQuery = Classroom::query()->orderBy('name');
        if ($this->selectedMajor && $this->selectedMajor !== 'ALL') {
            $classroomsQuery->forMajor($this->selectedMajor);
        }
        if ($this->selectedGrade && $this->selectedGrade !== 'ALL') {
            $classroomsQuery->forGrade($this->selectedGrade);
        }
        $classrooms = $classroomsQuery->get();

        $query = Schedule::with([
            'subject',
            'classroom.major',
            'classroom.classLevel',
            'classroom.rombel',
            'teacher',
            'room',
            'journals' => fn ($q) => $q->whereDate('date', $targetDate),
        ])
            ->where('day_of_week', $this->selectedDay ?? 'Senin')
            ->where('cycle_type', $this->selectedCycle ?? $config->active_cycle);

        if ($this->selectedMajor && $this->selectedMajor !== 'ALL') {
            $query->forMajor($this->selectedMajor);
        }

        if ($this->selectedGrade && $this->selectedGrade !== 'ALL') {
            $query->forGrade($this->selectedGrade);
        }

        if ($this->selectedClassroom && $this->selectedClassroom !== 'ALL') {
            $query->where('classroom_id', $this->selectedClassroom);
        }

        $daySessions = SchoolSession::getSessionsForDay($this->selectedDay ?? 'Senin');

        $allSchedules = $query->orderBy('start_time')->get()->map(function (Schedule $schedule) use ($targetDate, $now, $daySessions) {
            $journal = $schedule->journals->first();
            $hasJournal = $journal !== null;

            $startSession = $schedule->start_session ? ($daySessions[$schedule->start_session] ?? null) : null;
            $endSession = $schedule->end_session ? ($daySessions[$schedule->end_session] ?? null) : null;

            $startTimeStr = $startSession ? $startSession['start'] : substr((string) $schedule->start_time, 0, 5);
            $endTimeStr = $endSession ? $endSession['end'] : substr((string) $schedule->end_time, 0, 5);

            // Batas waktu pengisian jurnal adalah 1 hari (24 jam sejak jadwal pembelajaran selesai)
            $scheduleEndDateTime = Carbon::parse($targetDate->toDateString().' '.$endTimeStr);
            $isExpired = ! $hasJournal && $now->greaterThan($scheduleEndDateTime->copy()->addDay());

            return [
                'id' => $schedule->id,
                'journal_id' => $journal?->id,
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
                'start_session' => $schedule->start_session,
                'end_session' => $schedule->end_session,
                'session_label' => $schedule->start_session && $schedule->end_session
                    ? ($schedule->start_session === $schedule->end_session ? "Jam ke - {$schedule->start_session}" : "Jam ke {$schedule->start_session} - {$schedule->end_session}")
                    : null,
                'subject_name' => $schedule->subject?->name ?? '-',
                'teacher_name' => $schedule->teacher?->full_name ?? '-',
                'classroom_name' => $schedule->classroom?->name ?? '-',
                'major_code' => $schedule->classroom?->major?->code ?? '-',
                'grade_name' => $schedule->classroom?->classLevel?->name ?? '-',
                'room_name' => $schedule->room?->name ?? '-',
                'teaching_material' => $journal?->teaching_material,
                'has_journal' => $hasJournal,
                'is_expired' => $isExpired,
                'is_overdue' => $isExpired,
                'status' => $hasJournal ? 'filled' : ($isExpired ? 'not_filled' : 'unfilled'),
                'status_label' => $hasJournal ? 'Sudah diisi' : ($isExpired ? 'Tidak mengisi jurnal' : 'Belum diisi'),
            ];
        });

        // Quick stats per major
        $majorStats = $majors->map(function ($major) use ($config) {
            $count = Schedule::where('day_of_week', $this->selectedDay ?? 'Senin')
                ->where('cycle_type', $this->selectedCycle ?? $config->active_cycle)
                ->forMajor($major->code)
                ->count();

            return [
                'code' => $major->code,
                'name' => $major->name,
                'count' => $count,
            ];
        });

        $totalSchedules = $allSchedules->count();
        $filledCount = $allSchedules->where('has_journal', true)->count();
        $notFilledCount = $allSchedules->where('is_expired', true)->count();
        $unfilledCount = $allSchedules->where('has_journal', false)->where('is_expired', false)->count();

        $schedules = match ($this->selectedStatus) {
            'filled' => $allSchedules->where('has_journal', true)->values(),
            'unfilled' => $allSchedules->where('has_journal', false)->where('is_expired', false)->values(),
            'not_filled', 'overdue' => $allSchedules->where('is_expired', true)->values(),
            default => $allSchedules,
        };

        return [
            'schedules' => $schedules,
            'majors' => $majors,
            'grades' => $grades,
            'classrooms' => $classrooms,
            'majorStats' => $majorStats,
            'totalSchedules' => $totalSchedules,
            'filledCount' => $filledCount,
            'unfilledCount' => $unfilledCount,
            'notFilledCount' => $notFilledCount,
            'overdueCount' => $notFilledCount,
            'activeCycle' => $config->active_cycle,
            'formattedDate' => $targetDate->translatedFormat('d M Y'),
        ];
    }
}
