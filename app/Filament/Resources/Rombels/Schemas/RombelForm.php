<?php

namespace App\Filament\Resources\Rombels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RombelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama / Nomor Rombel')
                    ->placeholder('Contoh: 1, 2, 3, 4')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                TextInput::make('number')
                    ->label('Urutan Angka')
                    ->numeric()
                    ->required()
                    ->placeholder('Contoh: 1'),

                TextInput::make('description')
                    ->label('Keterangan')
                    ->placeholder('Contoh: Rombongan Belajar 1')
                    ->maxLength(255),
            ]);
    }
}
