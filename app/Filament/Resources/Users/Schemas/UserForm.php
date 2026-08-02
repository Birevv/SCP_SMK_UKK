<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Classroom;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('classroom_id')
                    ->label('Kelas')
                    ->options( Classroom::pluck('name', 'id'))
                    ->searchable()
                    ->required(),

                TextInput::make('full_name')
                    ->label('Nama Lengkap')
                    ->required(),

                TextInput::make('username')
                    ->label('Username')
                    ->required(),

                TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->email(),
            ]);
    }
}
