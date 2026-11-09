<?php

namespace App\Filament\Widgets;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\SchoolSession;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Support\PermissionHelper;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class HomeroomScheduleWidget extends Widget
{
    protected string $view = 'filament.widgets.homeroom-schedule-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public ?string $selectedGrade = 'ALL';

    public ?int $selectedClassroomId = null;

    public ?string $selectedDay = null;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user() ?? Filament::auth()?->user();
        if (! $user) {
            return false;
        }

        $isHomeroomOrBk = $user->hasAnyRole(['Wali Kelas', 'Guru BK'])
            || in_array($user->role?->name, ['Wali Kelas', 'Guru BK']);

        if (! $isHomeroomOrBk) {
            return false;
        }

        if (PermissionHelper::exists('View:HomeroomScheduleWidget')) {
            return $user->can('View:HomeroomScheduleWidget');
        }

        return true;
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $firstClass = $user?->classrooms()->first();
        if ($firstClass) {
            $this->selectedClassroomId = $firstClass->id;
            $this->selectedGrade = $firstClass->classLevel?->name ?? 'ALL';
        }

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

    public function setGrade(string $grade): void
    {
        $this->selectedGrade = $grade;
        $this->selectedClassroomId = null;
    }

    public function setClassroom(?int $classroomId): void
    {
        $this->selectedClassroomId = $classroomId;
    }

    protected function getViewData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return [
                'schedules' => collect(),
                'grades' => collect(),
                'classrooms' => collect(),
                'activeCycle' => 'Putaran 1',
            ];
        }

        $config = SystemConfiguration::current();
        $today = Carbon::today();
        $now = Carbon::now();
        $currentDayIso = $now->dayOfWeekIso;

        $selectedDayName = $this->selectedDay ?? match ($currentDayIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            default => 'Senin',
        };

        $targetDayIndex = match ($selectedDayName) {
            'Senin' => 1,
            'Selasa' => 2,
            'Rabu' => 3,
            'Kamis' => 4,
            'Jumat' => 5,
            default => 1,
        };

        // Jika hari ini akhir pekan (Sabtu/Minggu), wali kelas melihat jadwal minggu depan yang akan datang
        if ($currentDayIso >= 6) {
            $weekStart = $today->copy()->startOfWeek()->addWeek();
        } else {
            $weekStart = $today->copy()->startOfWeek();
        }

        $targetDate = $weekStart->copy()->addDays($targetDayIndex - 1)->startOfDay();

        $allGrades = ClassLevel::orderBy('level')->get();
        $activeAcademicYearId = AcademicYear::getActiveId();

        // Get accessible classrooms
        $userClassrooms = $user->classrooms;
        $isBk = $user->hasRole('Guru BK') || $user->role?->name === 'Guru BK';

        $availableClassroomsQuery = $isBk || $userClassrooms->isEmpty()
            ? Classroom::query()
            : Classroom::whereIn('id', $userClassrooms->pluck('id'));

        if ($this->selectedGrade && $this->selectedGrade !== 'ALL') {
            $availableClassroomsQuery->forGrade($this->selectedGrade);
        }

        $availableClassrooms = $availableClassroomsQuery->orderBy('name')->get();

        $query = Schedule::with(['subject', 'classroom.classLevel', 'classroom.major', 'teacher', 'room'])
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $selectedDayName);

        if ($activeAcademicYearId) {
            $query->where('academic_year_id', $activeAcademicYearId);
        }

        if ($this->selectedClassroomId) {
            $query->where('classroom_id', $this->selectedClassroomId);
        } elseif ($this->selectedGrade && $this->selectedGrade !== 'ALL') {
            $query->forGrade($this->selectedGrade);
        } elseif (! $isBk && $userClassrooms->isNotEmpty()) {
            $query->whereIn('classroom_id', $userClassrooms->pluck('id'));
        }

        $rawSchedules = $query->orderBy('start_time')->get();
        $scheduleIds = $rawSchedules->pluck('id')->filter()->all();

        $filledScheduleIds = ! empty($scheduleIds)
            ? Journal::whereIn('schedule_id', $scheduleIds)
                ->whereDate('date', $targetDate)
                ->pluck('id', 'schedule_id')
                ->toArray()
            : [];

        $daySessions = SchoolSession::getSessionsForDay($selectedDayName);

        $schedules = $rawSchedules->map(function (Schedule $schedule) use ($now, $targetDate, $filledScheduleIds, $daySessions) {
            $journalId = $filledScheduleIds[$schedule->id] ?? null;
            $hasJournal = $journalId !== null;

            $startSession = $schedule->start_session ? ($daySessions[$schedule->start_session] ?? null) : null;
            $endSession = $schedule->end_session ? ($daySessions[$schedule->end_session] ?? null) : null;

            $startTimeStr = $startSession ? $startSession['start'] : substr((string) $schedule->start_time, 0, 5);
            $endTimeStr = $endSession ? $endSession['end'] : substr((string) $schedule->end_time, 0, 5);

            $scheduleEndDateTime = Carbon::parse($targetDate->toDateString().' '.$endTimeStr);
            $isFuture = $targetDate->isFuture();
            $isToday = $targetDate->isToday();

            // Jika hari sekarang < hari jadwal atau masih dalam batas tenggat 1 hari -> Belum diisi
            // Jika jadwal sudah terlewat > 1 hari dan tidak mengisi -> Tidak Mengisi Jurnal
            if ($hasJournal) {
                $status = 'filled';
                $statusLabel = 'Sudah Diisi';
                $isExpired = false;
            } elseif ($isFuture || ($isToday && $now->lessThanOrEqualTo($scheduleEndDateTime->copy()->addDay()))) {
                $status = 'unfilled';
                $statusLabel = 'Belum Diisi';
                $isExpired = false;
            } else {
                $status = 'not_filled';
                $statusLabel = 'Tidak Mengisi Jurnal';
                $isExpired = true;
            }

            return [
                'id' => $schedule->id,
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
                'start_session' => $schedule->start_session,
                'end_session' => $schedule->end_session,
                'session_label' => $schedule->start_session && $schedule->end_session
                    ? ($schedule->start_session === $schedule->end_session ? "Jam ke - {$schedule->start_session}" : "Jam ke {$schedule->start_session} - {$schedule->end_session}")
                    : null,
                'subject_name' => $schedule->subject?->name ?? '-',
                'classroom_name' => $schedule->classroom?->name ?? '-',
                'teacher_name' => $schedule->teacher?->full_name ?? '-',
                'room_name' => $schedule->room?->name ?? '-',
                'has_journal' => $hasJournal,
                'is_expired' => $isExpired,
                'is_overdue' => $isExpired,
                'status' => $hasJournal ? 'filled' : ($isExpired ? 'not_filled' : 'unfilled'),
                'status_label' => $hasJournal ? 'Sudah diisi' : ($isExpired ? 'Tidak mengisi jurnal' : 'Belum diisi'),
            ];
        });

        return [
            'schedules' => $schedules,
            'grades' => $allGrades,
            'availableClassrooms' => $availableClassrooms,
            'activeCycle' => $config->active_cycle,
            'totalCount' => $schedules->count(),
            'filledCount' => $schedules->where('has_journal', true)->count(),
            'unfilledCount' => $schedules->where('has_journal', false)->where('is_expired', false)->count(),
            'notFilledCount' => $schedules->where('is_expired', true)->count(),
        ];
    }
}
