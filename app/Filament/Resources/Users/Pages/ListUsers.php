<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua Role'),
            'waka' => Tab::make('Waka Kurikulum')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Waka Kurikulum'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Waka Kurikulum'))),
            'guru' => Tab::make('Guru')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Guru'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Guru'))),
            'wali_kelas' => Tab::make('Wali Kelas')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Wali Kelas'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Wali Kelas'))),
            'bk' => Tab::make('Guru BK')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Guru BK'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Guru BK'))),
            'siswa' => Tab::make('Siswa')
                ->badge(fn () => User::whereHas('role', fn ($q) => $q->where('name', 'Siswa'))->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Siswa'))),
        ];
    }
}
