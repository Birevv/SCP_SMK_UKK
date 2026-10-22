<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class StatsOverviewWidget extends BaseWidget
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

        if (Permission::where('name', 'View:StatsOverviewWidget')->exists()) {
            return $user->can('View:StatsOverviewWidget');
        }

        return false;
    }

    protected function getStats(): array
    {
        $today = Carbon::today();
        $config = SystemConfiguration::current();

        $totalUsers = User::count();
        $totalStudents = User::where(function ($query) {
            $query->whereHas('roles', fn ($q) => $q->where('name', 'Siswa'))
                ->orWhereHas('role', fn ($q) => $q->where('name', 'Siswa'));
        })->count();

        $totalTeachers = User::where(function ($query) {
            $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['Guru', 'Guru BK', 'Wali Kelas']))
                ->orWhereHas('role', fn ($q) => $q->whereIn('name', ['Guru', 'Guru BK', 'Wali Kelas']));
        })->count();

        $journalsToday = Journal::whereDate('date', $today)->count();
        $totalSchedules = Schedule::where('cycle_type', $config->active_cycle)->count();

        $attendanceToday = Attendance::whereDate('date', $today);
        $hadirToday = (clone $attendanceToday)->where('status', 'Hadir')->count();
        $totalAttendanceToday = $attendanceToday->count();

        return [
            Stat::make('Total Pengguna', $totalUsers)
                ->description($totalTeachers.' guru, '.$totalStudents.' siswa')
                ->descriptionIcon('heroicon-o-users')
                ->color('primary'),
            Stat::make('Total Kelas', Classroom::count())
                ->description(Room::count().' ruangan, '.Subject::count().' mapel')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('success'),
            Stat::make('Jadwal Aktif', $totalSchedules)
                ->description($config->active_cycle.' • '.($config->is_schedule_locked ? 'Terkunci' : 'Terbuka'))
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),
            Stat::make('Jurnal Hari Ini', $journalsToday)
                ->description('Kehadiran: '.$hadirToday.'/'.$totalAttendanceToday)
                ->descriptionIcon('heroicon-o-document-text')
                ->color('warning'),
        ];
    }
}
