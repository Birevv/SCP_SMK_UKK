<?php

namespace App\Filament\Widgets;

use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Journal;
use App\Models\Major;
use App\Models\Schedule;
use App\Models\SchoolSession;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Support\PermissionHelper;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TeacherScheduleWidget extends Widget
{
    protected string $view = 'filament.widgets.teacher-schedule-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public ?string $selectedGrade = 'ALL';

    public ?string $selectedMajor = 'ALL';

    public ?string $selectedDay = null;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user() ?? Filament::auth()?->user();
        if (! $user) {
            return false;
        }

        if (! ($user->hasRole('Guru') || $user->role?->name === 'Guru')) {
            return false;
        }

        if (PermissionHelper::exists('View:TeacherScheduleWidget')) {
            return $user->can('View:TeacherScheduleWidget');
        }

        return true;
    }

    public function mount(): void
    {
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
    }

    public function setMajor(string $major): void
    {
        $this->selectedMajor = $major;
    }

    public function setDay(string $day): void
    {
        $this->selectedDay = $day;
    }

    protected function getViewData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return [
                'schedules' => collect(),
                'grades' => collect(),
                'majors' => collect(),
                'teacherGrades' => collect(),
            ];
        }

        $config = SystemConfiguration::current();
        $today = Carbon::today();
        $now = Carbon::now();
        $currentDayIso = $now->dayOfWeekIso; // 1 = Senin, ..., 7 = Minggu

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

        // Jika hari ini akhir pekan (Sabtu/Minggu), guru melihat jadwal minggu depan yang akan datang
        if ($currentDayIso >= 6) {
            $weekStart = $today->copy()->startOfWeek()->addWeek();
        } else {
            $weekStart = $today->copy()->startOfWeek();
        }

        $targetDate = $weekStart->copy()->addDays($targetDayIndex - 1)->startOfDay();

        // Find which grades this teacher actually teaches
        $teacherClassroomIds = Schedule::where('teacher_id', $user->id)
            ->pluck('classroom_id')
            ->unique();

        $allGrades = ClassLevel::orderBy('level')->get();
        $allMajors = Major::orderBy('code')->get();
        $activeAcademicYearId = AcademicYear::getActiveId();

        $query = Schedule::with(['subject', 'classroom.classLevel', 'classroom.major', 'classroom.rombel', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $selectedDayName);

        if ($activeAcademicYearId) {
            $query->where('academic_year_id', $activeAcademicYearId);
        }

        if ($this->selectedGrade && $this->selectedGrade !== 'ALL') {
            $query->forGrade($this->selectedGrade);
        }

        if ($this->selectedMajor && $this->selectedMajor !== 'ALL') {
            $query->forMajor($this->selectedMajor);
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

            // Jika hari sekarang < hari jadwal -> Belum diisi, nonaktif (diisi pada hari H)
            // Jika hari ini dan belum lewat batas -> Belum diisi, aktif (bisa diisi hari ini)
            // Jika jadwal sudah terlewat > 1 hari dan tidak mengisi -> Tidak Mengisi Jurnal
            if ($hasJournal) {
                $status = 'filled';
                $statusLabel = 'Sudah Diisi';
                $isExpired = false;
                $canFill = false;
            } elseif ($isFuture) {
                $status = 'unfilled';
                $statusLabel = 'Belum Diisi';
                $isExpired = false;
                $canFill = false;
            } elseif ($isToday && $now->lessThanOrEqualTo($scheduleEndDateTime->copy()->addDay())) {
                $status = 'unfilled';
                $statusLabel = 'Belum Diisi';
                $isExpired = false;
                $canFill = true;
            } else {
                $status = 'not_filled';
                $statusLabel = 'Tidak Mengisi Jurnal';
                $isExpired = true;
                $canFill = false;
            }

            return [
                'id' => $schedule->id,
                'journal_id' => $journalId,
                'start_time' => $startTimeStr,
                'end_time' => $endTimeStr,
                'start_session' => $schedule->start_session,
                'end_session' => $schedule->end_session,
                'session_label' => $schedule->start_session && $schedule->end_session
                    ? ($schedule->start_session === $schedule->end_session ? "Jam ke - {$schedule->start_session}" : "Jam ke {$schedule->start_session} - {$schedule->end_session}")
                    : null,
                'subject_name' => $schedule->subject?->name ?? '-',
                'classroom_name' => $schedule->classroom?->name ?? '-',
                'grade_name' => $schedule->classroom?->classLevel?->name ?? '-',
                'major_code' => $schedule->classroom?->major?->code ?? '-',
                'room_name' => $schedule->room?->name ?? '-',
                'has_journal' => $hasJournal,
                'is_expired' => $isExpired,
                'is_overdue' => $isExpired,
                'is_future' => $isFuture,
                'can_fill' => $canFill,
                'target_date' => $targetDate->toDateString(),
                'target_date_formatted' => $targetDate->translatedFormat('d M Y'),
                'status' => $status,
                'status_label' => $statusLabel,
            ];
        });

        return [
            'schedules' => $schedules,
            'grades' => $allGrades,
            'majors' => $allMajors,
            'activeCycle' => $config->active_cycle,
            'totalCount' => $schedules->count(),
            'filledCount' => $schedules->where('has_journal', true)->count(),
            'unfilledCount' => $schedules->where('has_journal', false)->where('is_expired', false)->count(),
            'notFilledCount' => $schedules->where('is_expired', true)->count(),
        ];
    }
}
