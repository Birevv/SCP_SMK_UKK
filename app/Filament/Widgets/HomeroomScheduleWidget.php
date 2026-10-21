<?php

namespace App\Filament\Widgets;

use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
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
        $user = Auth::user();

        return $user?->hasAnyRole(['Wali Kelas', 'Guru BK'])
            || in_array($user?->role?->name, ['Wali Kelas', 'Guru BK']);
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

        $allGrades = ClassLevel::orderBy('level')->get();

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
            ->where('day_of_week', $this->selectedDay ?? 'Senin');

        if ($this->selectedClassroomId) {
            $query->where('classroom_id', $this->selectedClassroomId);
        } elseif ($this->selectedGrade && $this->selectedGrade !== 'ALL') {
            $query->forGrade($this->selectedGrade);
        } elseif (! $isBk && $userClassrooms->isNotEmpty()) {
            $query->whereIn('classroom_id', $userClassrooms->pluck('id'));
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
                'teacher_name' => $schedule->teacher?->full_name ?? '-',
                'room_name' => $schedule->room?->name ?? '-',
                'has_journal' => $hasJournal,
                'is_overdue' => $isOverdue,
            ];
        });

        return [
            'schedules' => $schedules,
            'grades' => $allGrades,
            'availableClassrooms' => $availableClassrooms,
            'activeCycle' => $config->active_cycle,
            'totalCount' => $schedules->count(),
            'filledCount' => $schedules->where('has_journal', true)->count(),
            'unfilledCount' => $schedules->where('has_journal', false)->count(),
        ];
    }
}
