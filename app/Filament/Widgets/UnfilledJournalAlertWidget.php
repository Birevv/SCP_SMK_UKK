<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class UnfilledJournalAlertWidget extends Widget
{
    protected string $view = 'filament.widgets.unfilled-journal-alert-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    public static function canView(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();
        if ($user?->hasRole('Super Admin') || $user?->role?->name === 'Super Admin'
            || $user?->hasRole('Waka Kurikulum') || $user?->role?->name === 'Waka Kurikulum') {
            return true;
        }

        return $user?->hasRole('Guru') || $user?->role?->name === 'Guru';
    }

    public function getViewData(): array
    {
        $todayInIndonesian = match (Carbon::now()->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            default => null,
        };

        if (! $todayInIndonesian) {
            return [
                'unfilledSchedules' => collect(),
            ];
        }

        $thresholdTime = Carbon::now()->subMinutes(45)->format('H:i:s');
        $activeCycle = SystemConfiguration::current()->active_cycle;

        $query = Schedule::with(['classroom', 'room', 'subject', 'teacher'])
            ->where('day_of_week', $todayInIndonesian)
            ->where('cycle_type', $activeCycle)
            ->where('end_time', '<', $thresholdTime)
            ->whereDoesntHave('journals', function ($query) {
                $query->whereDate('date', Carbon::today());
            });

        /** @var User|null $user */
        $user = Auth::user();
        $isSchoolAdmin = $user?->hasRole('Super Admin')
            || $user?->hasRole('Waka Kurikulum')
            || in_array($user?->role?->name, ['Super Admin', 'Waka Kurikulum']);

        if (! $isSchoolAdmin && $user) {
            $query->where('teacher_id', $user->id);
        }

        return [
            'unfilledSchedules' => $query->get(),
        ];
    }
}
