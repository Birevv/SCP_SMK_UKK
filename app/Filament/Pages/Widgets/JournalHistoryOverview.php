<?php

declare(strict_types=1);

namespace App\Filament\Pages\Widgets;

use App\Filament\Pages\JournalHistory;
use App\Models\Journal;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class JournalHistoryOverview extends BaseWidget
{
    public static function canView(): bool
    {
        return JournalHistory::canAccess();
    }

    /**
     * @return array{totalJournals: int, thisMonthJournals: int, uniqueClassesCount: int}
     */
    public function getStatsData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return [
                'totalJournals' => 0,
                'thisMonthJournals' => 0,
                'uniqueClassesCount' => 0,
            ];
        }

        $query = Journal::query();
        $isTeacherOnly = ! ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin' || $user->hasRole('Waka Kurikulum') || $user->role?->name === 'Waka Kurikulum');

        if ($isTeacherOnly) {
            $query->where('journals.teacher_id', $user->id);
        }

        $totalJournals = (clone $query)->count();

        $thisMonthJournals = (clone $query)
            ->whereMonth('date', Carbon::today()->month)
            ->whereYear('date', Carbon::today()->year)
            ->count();

        $uniqueClassesCount = (clone $query)
            ->join('schedules', 'journals.schedule_id', '=', 'schedules.id')
            ->distinct('schedules.classroom_id')
            ->count('schedules.classroom_id');

        return [
            'totalJournals' => $totalJournals,
            'thisMonthJournals' => $thisMonthJournals,
            'uniqueClassesCount' => $uniqueClassesCount,
        ];
    }

    protected function getStats(): array
    {
        $data = $this->getStatsData();
        $monthLabel = 'Bulan '.Carbon::today()->locale('id')->isoFormat('MMMM Y');

        return [
            Stat::make('Total Jurnal Terisi', number_format($data['totalJournals']))
                ->description('Seluruh riwayat mengajar')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('primary'),

            Stat::make('Jurnal Bulan Ini', number_format($data['thisMonthJournals']))
                ->description($monthLabel)
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),

            Stat::make('Kelas Diajar', number_format($data['uniqueClassesCount']))
                ->description('Jumlah kelas yang pernah diisi')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('gray'),
        ];
    }
}
