<?php

namespace App\Filament\Resources\AcademicYears\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AcademicYearForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Tahun Ajaran (Semester)')
                    ->required()
                    ->placeholder('Contoh: 2026/2027 Ganjil')
                    ->maxLength(255),

                DatePicker::make('start_date')
                    ->label('Tanggal Mulai Semester')
                    ->required(),

                DatePicker::make('end_date')
                    ->label('Tanggal Selesai Semester')
                    ->required(),

                Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->helperText('Jika diaktifkan, tahun ajaran lain otomatis nonaktif dan kolom Tahun Ajaran di tabel Jadwal serta data terkait otomatis disesuaikan.')
                    ->default(false),
            ]);
    }
}
