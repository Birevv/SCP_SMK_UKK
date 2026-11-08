<?php

namespace App\Filament\Resources\SchoolSessions\Schemas;

use App\Models\SchoolSession;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SchoolSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('day_of_week')
                    ->label('Hari')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                    ])
                    ->required()
                    ->native(false),

                TextInput::make('session_number')
                    ->label('Nomor Sesi (Jam Ke-)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(20)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if ($state && empty($get('label'))) {
                            $set('label', "Jam ke - {$state}");
                        }
                    }),

                TextInput::make('label')
                    ->label('Label Sesi')
                    ->placeholder('Misal: Jam ke - 1')
                    ->helperText('Nama tampilan jam pelajaran di formulir jadwal.')
                    ->required(),

                TextInput::make('duration_minutes')
                    ->label('Durasi Sesi')
                    ->numeric()
                    ->suffix('menit')
                    ->default(40)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $startTime = $get('start_time');
                        if ($startTime && $state) {
                            try {
                                $start = SchoolSession::parseTimeCarbon((string) $startTime);
                                $set('end_time', $start->addMinutes((int) $state)->format('H:i'));
                            } catch (\Throwable $e) {
                                // ignore parse error
                            }
                        }
                    }),

                TextInput::make('start_time')
                    ->label('Jam Mulai')
                    ->placeholder('HH:mm (misal: 07:15 atau 07.15)')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        $duration = (int) ($get('duration_minutes') ?: 40);
                        if ($state && $duration > 0) {
                            try {
                                $start = SchoolSession::parseTimeCarbon((string) $state);
                                $set('end_time', $start->addMinutes($duration)->format('H:i'));
                            } catch (\Throwable $e) {
                                // ignore parse error
                            }
                        }
                    }),

                TextInput::make('end_time')
                    ->label('Jam Selesai')
                    ->placeholder('HH:mm (misal: 07:55 atau 07.55)')
                    ->required(),
            ]);
    }
}
