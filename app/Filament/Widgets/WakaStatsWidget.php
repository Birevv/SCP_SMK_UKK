<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class WakaStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user() ?? Filament::auth()?->user();
        if (! $user) {
            return false;
        }

        if ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin') {
            return true;
        }

        if (Permission::where('name', 'View:WakaStatsWidget')->exists()) {
            return $user->can('View:WakaStatsWidget');
        }

        return $user->hasRole('Waka Kurikulum') || $user->role?->name === 'Waka Kurikulum';
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $config = SystemConfiguration::current();

        $totalTeachers = User::where(function ($query) {
            $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['Guru', 'Guru BK', 'Wali Kelas']))
                ->orWhereHas('role', fn ($q) => $q->whereIn('name', ['Guru', 'Guru BK', 'Wali Kelas']));
        })->count();

        $totalStudents = User::where(function ($query) {
            $query->whereHas('roles', fn ($q) => $q->where('name', 'Siswa'))
                ->orWhereHas('role', fn ($q) => $q->where('name', 'Siswa'));
        })->count();

        $totalClassrooms = Classroom::count();
        $totalSubjects = Subject::count();

        $totalSchedules = Schedule::where('cycle_type', $config->active_cycle)->count();

        $journalsToday = Journal::whereDate('date', $today)->count();

        $attendanceToday = Attendance::whereDate('date', $today);
        $hadirToday = (clone $attendanceToday)->where('status', 'Hadir')->count();
        $totalAttendanceToday = $attendanceToday->count();
        $attendanceRate = $totalAttendanceToday > 0
            ? round(($hadirToday / $totalAttendanceToday) * 100, 1).'%'
            : '-';

        return [
            Stat::make('Total Guru & Staf', $totalTeachers)
                ->description('Guru, Wali Kelas, Guru BK')
                ->descriptionIcon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make('Total Siswa', $totalStudents)
                ->description($totalClassrooms.' kelas')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('info'),
            Stat::make('Mata Pelajaran', $totalSubjects)
                ->description($totalSchedules.' jadwal ('.$config->active_cycle.')')
                ->descriptionIcon('heroicon-o-book-open')
                ->color('success'),
            Stat::make('Jurnal Hari Ini', $journalsToday)
                ->description('Tingkat kehadiran: '.$attendanceRate)
                ->descriptionIcon('heroicon-o-document-check')
                ->color('warning'),
        ];
    }
}
