<?php

namespace App\Filament\Resources\Journals\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AttendanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('student_id')
                    ->relationship('student', 'full_name')
                    ->label('Siswa')
                    ->disabled()
                    ->required(),

                Select::make('status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Sakit' => 'Sakit',
                        'Izin' => 'Izin',
                        'Alpa' => 'Alpa',
                    ])
                    ->required()
                    ->label('Status Kehadiran'),

                TextInput::make('notes')
                    ->maxLength(255)
                    ->label('Catatan'),
            ]);
    }
}
