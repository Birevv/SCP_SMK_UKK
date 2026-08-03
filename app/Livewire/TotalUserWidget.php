<?php

namespace App\Livewire;

use App\Models\Classroom;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Console\Attributes\Description;

class TotalUserWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Pengguna', User::count())
                ->description('Total Pengguna Aktif')
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),
            
            Stat::make('Total Kelas', Classroom::count())
                ->description('Total Kelas Aktif')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('primary'),
            
            

        ];
    }
}
