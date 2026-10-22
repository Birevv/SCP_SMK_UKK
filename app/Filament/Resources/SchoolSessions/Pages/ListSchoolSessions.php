<?php

namespace App\Filament\Resources\SchoolSessions\Pages;

use App\Filament\Resources\SchoolSessions\SchoolSessionResource;
use App\Models\SchoolSession;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSchoolSessions extends ListRecords
{
    protected static string $resource = SchoolSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Sesi Manual'),

            Action::make('bulk_configure')
                ->label('Atur Durasi Sesi Masal')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('warning')
                ->modalHeading('Atur Durasi & Generate Sesi Otomatis')
                ->modalDescription('Atur durasi per sesi pelajaran (misal 40 menit normal, atau 30-35 menit pada bulan Ramadhan/khusus). Sistem akan otomatis menghitung jam mulai dan selesai seluruh sesi.')
                ->modalSubmitActionLabel('Terapkan & Generate Sesi')
                ->form([
                    CheckboxList::make('days')
                        ->label('Pilih Hari yang Ingin Diterapkan')
                        ->options([
                            'Senin' => 'Senin',
                            'Selasa' => 'Selasa',
                            'Rabu' => 'Rabu',
                            'Kamis' => 'Kamis',
                            'Jumat' => 'Jumat',
                        ])
                        ->default(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'])
                        ->columns(5)
                        ->required(),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('duration_minutes')
                                ->label('Durasi per Sesi')
                                ->numeric()
                                ->suffix('menit')
                                ->default(40)
                                ->required(),

                            TextInput::make('first_start_time')
                                ->label('Jam Masuk Sesi 1')
                                ->default('07:15')
                                ->required(),

                            TextInput::make('total_sessions_regular')
                                ->label('Total Sesi (Senin-Kamis)')
                                ->numeric()
                                ->default(10)
                                ->required(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('total_sessions_jumat')
                                ->label('Total Sesi (Khusus Hari Jumat)')
                                ->numeric()
                                ->default(8)
                                ->required(),

                            TextInput::make('break1_minutes')
                                ->label('Durasi Istirahat 1 (Setelah Jam ke-3)')
                                ->numeric()
                                ->suffix('menit')
                                ->default(15)
                                ->required(),
                        ]),

                    Grid::make(3)
                        ->schema([
                            TextInput::make('break2_minutes_regular')
                                ->label('Istirahat 2 Dzuhur (Senin-Kamis)')
                                ->numeric()
                                ->suffix('menit')
                                ->default(60)
                                ->required(),

                            TextInput::make('break2_minutes_jumat')
                                ->label('Istirahat Sholat Jumat')
                                ->numeric()
                                ->suffix('menit')
                                ->default(70)
                                ->required(),

                            TextInput::make('break3_minutes_regular')
                                ->label('Istirahat 3 Ashar (Setelah Jam 8)')
                                ->numeric()
                                ->suffix('menit')
                                ->default(20)
                                ->required(),
                        ]),
                ])
                ->action(function (array $data) {
                    $days = $data['days'] ?? [];
                    $duration = (int) ($data['duration_minutes'] ?? 40);
                    $firstStart = $data['first_start_time'] ?? '07:15';
                    $totalRegular = (int) ($data['total_sessions_regular'] ?? 10);
                    $totalJumat = (int) ($data['total_sessions_jumat'] ?? 8);
                    $break1 = (int) ($data['break1_minutes'] ?? 15);
                    $break2Regular = (int) ($data['break2_minutes_regular'] ?? 60);
                    $break2Jumat = (int) ($data['break2_minutes_jumat'] ?? 70);
                    $break3Regular = (int) ($data['break3_minutes_regular'] ?? 20);

                    foreach ($days as $day) {
                        $isJumat = ($day === 'Jumat');
                        $totalSessions = $isJumat ? $totalJumat : $totalRegular;
                        $break2 = $isJumat ? $break2Jumat : $break2Regular;
                        $break3 = $isJumat ? 0 : $break3Regular;

                        SchoolSession::generateSessionsForDay(
                            dayOfWeek: $day,
                            durationMinutes: $duration,
                            firstStartTime: $firstStart,
                            totalSessions: $totalSessions,
                            break1After: 3,
                            break1Minutes: $break1,
                            break2After: 6,
                            break2Minutes: $break2,
                            break3After: 8,
                            break3Minutes: $break3
                        );
                    }

                    Notification::make()
                        ->title('Konfigurasi Sesi Berhasil Disimpan')
                        ->body("Durasi {$duration} menit per sesi berhasil diterapkan untuk hari: ".implode(', ', $days).'.')
                        ->success()
                        ->send();
                }),

            Action::make('reset_default')
                ->label('Reset ke Default')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Kembalikan ke Jadwal Sesi Default?')
                ->modalDescription('Seluruh hari akan di-reset ke standar default: 40 menit per 1 sesi, s/d Jam ke-10 (15:30) untuk Senin-Kamis, dan s/d Jam ke-8 (14:00) untuk Jumat.')
                ->action(function () {
                    SchoolSession::seedDefaults();

                    Notification::make()
                        ->title('Berhasil Di-reset')
                        ->body('Jadwal sesi berhasil dikembalikan ke standar default (40 menit per sesi, Jam ke-10 15:30, Jumat Jam ke-8 14:00).')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        $baseQuery = fn () => static::getResource()::getEloquentQuery();

        return [
            'all' => Tab::make('Semua Hari')
                ->badge(fn () => $baseQuery()->count()),
            'senin' => Tab::make('Senin')
                ->badge(fn () => $baseQuery()->where('day_of_week', 'Senin')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('day_of_week', 'Senin')),
            'selasa' => Tab::make('Selasa')
                ->badge(fn () => $baseQuery()->where('day_of_week', 'Selasa')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('day_of_week', 'Selasa')),
            'rabu' => Tab::make('Rabu')
                ->badge(fn () => $baseQuery()->where('day_of_week', 'Rabu')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('day_of_week', 'Rabu')),
            'kamis' => Tab::make('Kamis')
                ->badge(fn () => $baseQuery()->where('day_of_week', 'Kamis')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('day_of_week', 'Kamis')),
            'jumat' => Tab::make('Jumat')
                ->badge(fn () => $baseQuery()->where('day_of_week', 'Jumat')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('day_of_week', 'Jumat')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'all';
    }
}
