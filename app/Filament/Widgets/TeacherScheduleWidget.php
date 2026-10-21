<?php

namespace App\Filament\Widgets;

use App\Models\ClassLevel;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class TeacherScheduleWidget extends Widget
{
    protected string $view = 'filament.widgets.teacher-schedule-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public ?string $selectedGrade = 'ALL';

    public ?string $selectedDay = null;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasRole('Guru') || $user?->role?->name === 'Guru';
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
                'teacherGrades' => collect(),
            ];
        }

        $config = SystemConfiguration::current();
        $today = Carbon::today();
        $now = Carbon::now();

        // Find which grades this teacher actually teaches
        $teacherClassroomIds = Schedule::where('teacher_id', $user->id)
            ->pluck('classroom_id')
            ->unique();

        $allGrades = ClassLevel::orderBy('level')->get();

        $query = Schedule::with(['subject', 'classroom.classLevel', 'classroom.major', 'classroom.rombel', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $this->selectedDay ?? 'Senin');

        if ($this->selectedGrade && $this->selectedGrade !== 'ALL') {
            $query->forGrade($this->selectedGrade);
        }

        $schedules = $query->orderBy('start_time')->get()->map(function (Schedule $schedule) use ($today, $now) {
            $hasJournal = $schedule->journals()->whereDate('date', $today)->exists();
            $endTime = Carbon::parse($schedule->end_time);
            $isOverdue = ! $hasJournal && $now->greaterThan($endTime->copy()->addMinutes(45));

            return [
                'id' => $schedule->id,
                'start_time' => substr($schedule->start_time, 0, 5),
                'end_time' => substr($schedule->end_time, 0, 5),
                'subject_name' => $schedule->subject?->name ?? '-',
                'classroom_name' => $schedule->classroom?->name ?? '-',
                'grade_name' => $schedule->classroom?->classLevel?->name ?? '-',
                'major_code' => $schedule->classroom?->major?->code ?? '-',
                'room_name' => $schedule->room?->name ?? '-',
                'has_journal' => $hasJournal,
                'is_overdue' => $isOverdue,
            ];
        });

        return [
            'schedules' => $schedules,
            'grades' => $allGrades,
            'activeCycle' => $config->active_cycle,
            'totalCount' => $schedules->count(),
            'filledCount' => $schedules->where('has_journal', true)->count(),
            'unfilledCount' => $schedules->where('has_journal', false)->count(),
        ];
    }
}
