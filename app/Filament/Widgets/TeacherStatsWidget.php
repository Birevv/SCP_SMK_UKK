<?php

namespace App\Filament\Widgets;

use App\Models\Journal;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TeacherStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user?->hasRole('Guru') || $user?->role?->name === 'Guru';
    }

    protected function getStats(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return [];
        }

        $today = Carbon::today();
        $dayName = match (Carbon::now()->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            default => null,
        };
        $activeCycle = SystemConfiguration::current()->active_cycle;

        $todayScheduleCount = $dayName ? Schedule::where('teacher_id', $user->id)
            ->where('day_of_week', $dayName)
            ->where('cycle_type', $activeCycle)
            ->count() : 0;

        $monthlyJournalCount = Journal::where('teacher_id', $user->id)
            ->whereMonth('date', $today->month)
            ->whereYear('date', $today->year)
            ->count();

        $totalSchedules = Schedule::where('teacher_id', $user->id)
            ->where('cycle_type', $activeCycle)
            ->count();

        $unfilledToday = $dayName ? Schedule::where('teacher_id', $user->id)
            ->where('day_of_week', $dayName)
            ->where('cycle_type', $activeCycle)
            ->where('end_time', '<', Carbon::now()->format('H:i:s'))
            ->whereDoesntHave('journals', function ($query) use ($today) {
                $query->whereDate('date', $today);
            })
            ->count() : 0;

        $dayDisplay = $dayName
            ? $dayName.', '.$today->locale('id')->isoFormat('D MMMM Y')
            : 'Akhir Pekan (Tidak Ada KBM)';

        return [
            Stat::make('Jadwal Hari Ini', $todayScheduleCount)
                ->description($dayDisplay)
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),
            Stat::make('Jurnal Bulan Ini', $monthlyJournalCount)
                ->description('Total jurnal yang sudah diisi')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('success'),
            Stat::make('Total Jadwal Aktif', $totalSchedules)
                ->description($activeCycle)
                ->descriptionIcon('heroicon-o-clock')
                ->color('primary'),
            Stat::make('Belum Diisi Hari Ini', $unfilledToday)
                ->description($unfilledToday > 0 ? 'Jurnal perlu segera diisi!' : 'Semua jurnal sudah terisi')
                ->descriptionIcon($unfilledToday > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle')
                ->color($unfilledToday > 0 ? 'danger' : 'success'),
        ];
    }
}
