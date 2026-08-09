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
        $role = Auth::user()?->role?->name;

        return in_array($role, ['Siswa', 'Orang Tua']);
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        if (! $user) {
            return ['stats' => null];
        }

        $studentId = null;
        $studentName = '';

        if ($user->role?->name === 'Siswa') {
            $studentId = $user->id;
            $studentName = $user->full_name;
        } elseif ($user->role?->name === 'Orang Tua') {
            $child = User::where('parent_id', $user->id)->first() ?? User::find($user->parent_id);
            if ($child) {
                $studentId = $child->id;
                $studentName = $child->full_name;
            }
        }

        if (! $studentId) {
            return ['stats' => null];
        }

        $attendances = Attendance::with(['journal.schedule'])
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
