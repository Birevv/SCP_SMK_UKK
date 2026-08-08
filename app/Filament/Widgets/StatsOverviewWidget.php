<?php

namespace App\Filament\Widgets;

use App\Models\Classroom;
use App\Models\Room;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

use Illuminate\Support\Facades\Auth;

class StatsOverviewWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return Auth::user()?->role?->name === 'Waka Kurikulum';
    }
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pengguna', User::count())
                ->description('Total Pengguna Aktif')
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),
            Stat::make('Total Ruangan', Room::count())
                ->description('Total Ruangan Aktif')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('success'),
            Stat::make('Total Kelas', Classroom::count())
                ->description('Total Kelas Aktif')
                ->descriptionIcon('heroicon-o-academic-cap')
                ->color('success'),
        ];
    }
}
