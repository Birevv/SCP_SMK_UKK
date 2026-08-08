<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Classroom;
use App\Models\Role;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('role_id')
                    ->label('Role Akses')
                    ->options(Role::pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive(),

                Select::make('classroom_id')
                    ->label('Kelas')
                    ->options(Classroom::pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),

                Select::make('parent_id')
                    ->label('Orang Tua (untuk siswa)')
                    ->relationship('parent', 'full_name', fn (Builder $query) =>
                        $query->whereHas('role', fn ($q) => $q->where('name', 'Orang Tua'))
                    )
                    ->searchable()
                    ->preload()
                    ->nullable()
                    ->helperText('Pilih akun Orang Tua yang terhubung dengan siswa ini'),

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

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->helperText(fn (string $operation): string =>
                        $operation === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : ''
                    ),
            ]);
    }
}
