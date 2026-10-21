<?php

namespace App\Filament\Widgets;

use App\Models\ClassLevel;
use App\Models\Major;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class WakaScheduleWidget extends Widget
{
    protected string $view = 'filament.widgets.waka-schedule-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public ?string $selectedMajor = 'ALL';

    public ?string $selectedGrade = 'ALL';

    public ?string $selectedDay = null;

    public ?string $selectedCycle = null;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasRole('Waka Kurikulum')
            || $user?->role?->name === 'Waka Kurikulum'
            || $user?->hasRole('Super Admin')
            || $user?->role?->name === 'Super Admin';
    }

    public function mount(): void
    {
        $config = SystemConfiguration::current();
        $this->selectedCycle = $config->active_cycle ?? 'Putaran 1';

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
        $config = SystemConfiguration::current();
        $today = Carbon::today();
        $now = Carbon::now();

        $majors = Major::orderBy('code')->get();
        $grades = ClassLevel::orderBy('level')->get();

        $query = Schedule::with(['subject', 'classroom.major', 'classroom.classLevel', 'classroom.rombel', 'teacher', 'room'])
            ->where('day_of_week', $this->selectedDay ?? 'Senin')
            ->where('cycle_type', $this->selectedCycle ?? $config->active_cycle);

        if ($this->selectedMajor && $this->selectedMajor !== 'ALL') {
            $query->forMajor($this->selectedMajor);
        }

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
                'teacher_name' => $schedule->teacher?->full_name ?? '-',
                'classroom_name' => $schedule->classroom?->name ?? '-',
                'major_code' => $schedule->classroom?->major?->code ?? '-',
                'grade_name' => $schedule->classroom?->classLevel?->name ?? '-',
                'room_name' => $schedule->room?->name ?? '-',
                'has_journal' => $hasJournal,
                'is_overdue' => $isOverdue,
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

        $totalSchedules = $schedules->count();
        $filledCount = $schedules->where('has_journal', true)->count();
        $unfilledCount = $totalSchedules - $filledCount;
        $overdueCount = $schedules->where('is_overdue', true)->count();

        return [
            'schedules' => $schedules,
            'majors' => $majors,
            'grades' => $grades,
            'majorStats' => $majorStats,
            'totalSchedules' => $totalSchedules,
            'filledCount' => $filledCount,
            'unfilledCount' => $unfilledCount,
            'overdueCount' => $overdueCount,
            'activeCycle' => $config->active_cycle,
        ];
    }
}
