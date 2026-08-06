<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Override;

class UnfilledJournalAlertWidget extends Widget
{
    protected string $view = 'filament.widgets.unfilled-journal-alert-widget';

    protected static ?int $sort = -1;

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user?->role?->name === 'Guru';
    }

    public function getViewData(): array
    {
        $todayInIndonesian = match (Carbon::now()->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };

        $thresholdTime = Carbon::now()->subMinutes(30)->format('H:i:s');

        $unfilledSchedules = Schedule::with(['classroom', 'room'])
            ->where('teacher_id', Auth::id())
            ->where('day_of_week', $todayInIndonesian)
            ->forActiveCycle()
            ->where('end_time', '<', $thresholdTime)
            ->whereDoesntHave('journals', function ($query) {
                $query->whereDate('date', Carbon::today());
            })
            ->get();

        return [
            'unfilledSchedules' => $unfilledSchedules,
        ];
    }
}
