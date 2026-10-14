<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class HomeroomStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasAnyRole(['Wali Kelas', 'Guru BK'])
            || in_array($user?->role?->name, ['Wali Kelas', 'Guru BK']);
    }

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $classroom = $user->classrooms()->first();
        $today = Carbon::today();

        if ($classroom) {
            $studentIds = User::whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroom->id))
                ->where(function ($query) {
                    $query->whereHas('roles', fn ($q) => $q->where('name', 'Siswa'))
                        ->orWhereHas('role', fn ($q) => $q->where('name', 'Siswa'));
                })
                ->pluck('id');

            $scopeLabel = 'Kelas '.$classroom->name;
        } else {
            $studentIds = User::where(function ($query) {
                $query->whereHas('roles', fn ($q) => $q->where('name', 'Siswa'))
                    ->orWhereHas('role', fn ($q) => $q->where('name', 'Siswa'));
            })->pluck('id');

            $scopeLabel = 'Seluruh Siswa';
        }

        $totalStudents = $studentIds->count();

        $pendingLeaveRequests = LeaveRequest::whereIn('student_id', $studentIds)
            ->where('status', 'Pending')
            ->count();

        $todayAttendances = Attendance::whereIn('student_id', $studentIds)
            ->whereDate('date', $today)
            ->get();

        $hadirCount = $todayAttendances->where('status', 'Hadir')->unique('student_id')->count();
        $absentCount = $todayAttendances->whereIn('status', ['Sakit', 'Izin', 'Alpa'])->unique('student_id')->count();

        $studentsWithHighAbsence = Attendance::whereIn('student_id', $studentIds)
            ->where('status', 'Alpa')
            ->whereMonth('date', $today->month)
            ->whereYear('date', $today->year)
            ->selectRaw('student_id, COUNT(*) as total_alpa')
            ->groupBy('student_id')
            ->having('total_alpa', '>=', 3)
            ->get()
            ->count();

        return [
            Stat::make('Jumlah Siswa', $totalStudents)
                ->description($scopeLabel)
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make('Izin Menunggu', $pendingLeaveRequests)
                ->description($pendingLeaveRequests > 0 ? 'Perlu diverifikasi segera' : 'Tidak ada izin pending')
                ->descriptionIcon($pendingLeaveRequests > 0 ? 'heroicon-o-exclamation-circle' : 'heroicon-o-check-circle')
                ->color($pendingLeaveRequests > 0 ? 'warning' : 'success'),
            Stat::make('Hadir Hari Ini', $totalStudents > 0 ? ($hadirCount.'/'.$totalStudents) : '0/0')
                ->description($absentCount > 0 ? $absentCount.' siswa tidak hadir' : 'Semua hadir')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->color($absentCount > 0 ? 'warning' : 'success'),
            Stat::make('Siswa Rawan Alpa', $studentsWithHighAbsence)
                ->description('Alpa ≥ 3x bulan ini')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($studentsWithHighAbsence > 0 ? 'danger' : 'success'),
        ];
    }
}
