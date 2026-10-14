<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class StudentAttendanceWidget extends Widget
{
    protected string $view = 'filament.widgets.student-attendance-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasRole('Siswa') || $user?->role?->name === 'Siswa';
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

        $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 100;

        return [
            'stats' => [
                'student_name' => $studentName,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total' => $total,
                'percentage' => $percentage,
                'history' => $attendances->take(10),
            ],
        ];
    }
}
