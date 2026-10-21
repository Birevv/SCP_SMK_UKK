<?php

namespace App\Filament\Resources\Classrooms\Schemas;

use App\Models\ClassLevel;
use App\Models\Major;
use App\Models\Rombel;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClassroomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('class_level_id')
                    ->label('Tingkat Kelas')
                    ->relationship('classLevel', 'name')
                    ->preload()
                    ->searchable()
                    ->placeholder('Pilih Tingkat (X, XI, XII, Lulus)'),

                Select::make('major_id')
                    ->label('Jurusan')
                    ->relationship('major', 'code')
                    ->getOptionLabelFromRecordUsing(fn (Major $record) => "{$record->code} - {$record->name}")
                    ->preload()
                    ->searchable()
                    ->placeholder('Pilih Jurusan (RPL, AKL, TKJ, dll)'),

                Select::make('rombel_id')
                    ->label('Nomor Rombel')
                    ->relationship('rombel', 'name')
                    ->preload()
                    ->placeholder('Pilih Rombel (1, 2, 3, 4)'),

                TextInput::make('name')
                    ->label('Nama Lengkap Kelas')
                    ->helperText('Otomatis dihasilkan dari Tingkat + Jurusan + Rombel jika dikosongkan (contoh: XII RPL 1)')
                    ->placeholder('Contoh: XII RPL 1')
                    ->maxLength(255),
            ]);
    }
}
