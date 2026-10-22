<?php

namespace App\Filament\Widgets;

use App\Models\ClassLevel;
use App\Models\Journal;
use App\Models\Major;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

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

        if (Permission::where('name', 'View:TeacherScheduleWidget')->exists()) {
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

        // Find which grades this teacher actually teaches
        $teacherClassroomIds = Schedule::where('teacher_id', $user->id)
            ->pluck('classroom_id')
            ->unique();

        $allGrades = ClassLevel::orderBy('level')->get();
        $allMajors = Major::orderBy('code')->get();

        $query = Schedule::with(['subject', 'classroom.classLevel', 'classroom.major', 'classroom.rombel', 'room'])
            ->where('teacher_id', $user->id)
            ->where('cycle_type', $config->active_cycle)
            ->where('day_of_week', $this->selectedDay ?? 'Senin');

        if ($this->selectedGrade && $this->selectedGrade !== 'ALL') {
            $query->forGrade($this->selectedGrade);
        }

        if ($this->selectedMajor && $this->selectedMajor !== 'ALL') {
            $query->forMajor($this->selectedMajor);
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
            'majors' => $allMajors,
            'activeCycle' => $config->active_cycle,
            'totalCount' => $schedules->count(),
            'filledCount' => $schedules->where('has_journal', true)->count(),
            'unfilledCount' => $schedules->where('has_journal', false)->count(),
        ];
    }
}
