<?php

namespace App\Filament\Resources\ClassLevels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ClassLevelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Tingkat')
                    ->placeholder('Contoh: X, XI, XII, Lulus')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                TextInput::make('level')
                    ->label('Urutan / Level Angka')
                    ->numeric()
                    ->placeholder('Contoh: 10, 11, 12')
                    ->nullable(),

                TextInput::make('description')
                    ->label('Keterangan')
                    ->placeholder('Contoh: Kelas 10')
                    ->maxLength(255),
            ]);
    }
}
