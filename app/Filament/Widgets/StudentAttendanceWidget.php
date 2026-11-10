<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\LeaveRequests\LeaveRequestResource;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Support\PermissionHelper;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class StudentAttendanceWidget extends Widget
{
    protected string $view = 'filament.widgets.student-attendance-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user() ?? Filament::auth()?->user();
        if (! $user) {
            return false;
        }

        if (! ($user->hasRole('Siswa') || $user->role?->name === 'Siswa')) {
            return false;
        }

        if (PermissionHelper::exists('View:StudentAttendanceWidget')) {
            return $user->can('View:StudentAttendanceWidget');
        }

        return true;
    }

    protected function getViewData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user || (! $user->hasRole('Siswa') && $user->role?->name !== 'Siswa')) {
            return ['stats' => null];
        }

        $studentId = $user->id;
        $studentName = $user->full_name;
        $today = Carbon::today('Asia/Jakarta');
        $dayName = match (Carbon::now('Asia/Jakarta')->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            default => null,
        };

        $config = SystemConfiguration::current();
        $activeCycle = $config->active_cycle;

        $classroomIds = $user->classrooms()->pluck('classrooms.id')->toArray();
        if (empty($classroomIds) && $user->classroom_id) {
            $classroomIds = [$user->classroom_id];
        }

        $classroomName = $user->classrooms->pluck('name')->join(', ') ?: ($user->classroom?->name ?? '-');

        $attendances = Attendance::with(['journal.schedule.subject'])
            ->where('student_id', $studentId)
            ->latest('date')
            ->latest('id')
            ->get();

        $hadir = $attendances->where('status', 'Hadir')->count();
        $sakit = $attendances->where('status', 'Sakit')->count();
        $izin = $attendances->where('status', 'Izin')->count();
        $alpa = $attendances->where('status', 'Alpa')->count();
        $total = $attendances->count();

        $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 100.0;

        $todaySchedules = $dayName ? Schedule::with(['subject', 'room', 'teacher'])
            ->whereIn('classroom_id', $classroomIds)
            ->where('day_of_week', $dayName)
            ->where('cycle_type', $activeCycle)
            ->orderBy('start_time')
            ->get() : collect();

        $recentLeaves = LeaveRequest::with('verifiedBy')
            ->where('student_id', $studentId)
            ->latest()
            ->take(5)
            ->get();

        $createLeaveUrl = LeaveRequestResource::getUrl('create');

        return [
            'stats' => [
                'student_name' => $studentName,
                'classroom_name' => $classroomName,
                'active_cycle' => $activeCycle,
                'day_display' => $dayName ? $dayName.', '.$today->locale('id')->isoFormat('D MMMM Y') : 'Akhir Pekan (Libur KBM)',
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total' => $total,
                'percentage' => $percentage,
                'history' => $attendances->take(10),
                'today_schedules' => $todaySchedules,
                'recent_leaves' => $recentLeaves,
                'create_leave_url' => $createLeaveUrl,
            ],
        ];
    }
}
