<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('role_id')
                    ->label('Role Akses')
                    ->options(function () {
                        $query = Role::query();
                        /** @var User|null $user */
                        $user = Auth::user();

                        if (! $user?->hasRole('Super Admin') && $user?->role?->name !== 'Super Admin') {
                            $query->where('name', '!=', 'Super Admin');
                        }

                        return $query->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required()
                    ->reactive(),

                Select::make('classrooms')
                    ->relationship('classrooms', 'name')
                    ->multiple()
                    ->label('Kelas')
                    ->searchable()
                    ->preload()
                    ->pivotData(fn () => [
                        'academic_year_id' => AcademicYear::where('is_active', true)->first()?->id ?? AcademicYear::first()?->id,
                        'status' => 'Aktif',
                    ]),

                TextInput::make('full_name')
                    ->label('Nama Lengkap')
                    ->required(),

                TextInput::make('identity_number')
                    ->label('NISN / NIP (Nomor Identitas)')
                    ->helperText('NISN untuk Siswa, NIP untuk Guru/Staf. Digunakan untuk login.')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('username')
                    ->label('Username')
                    ->required()
                    ->unique(ignoreRecord: true),

                TextInput::make('email')
                    ->label('Email (Untuk Reset Password)')
                    ->helperText('Opsional. Digunakan apabila ingin melakukan reset password melalui email.')
                    ->nullable()
                    ->email()
                    ->unique(ignoreRecord: true),

                TextInput::make('phone')
                    ->label('Nomor Telepon')
                    ->tel()
                    ->maxLength(255),

                Select::make('status')
                    ->label('Status Siswa/Guru')
                    ->options([
                        'Aktif' => 'Aktif',
                        'Lulus' => 'Lulus',
                        'Keluar' => 'Keluar',
                        'DO' => 'Drop Out (DO)',
                    ])
                    ->default('Aktif')
                    ->required(),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->helperText(fn (string $operation): string => $operation === 'edit' ? 'Kosongkan jika tidak ingin mengubah password' : ''
                    ),
            ]);
    }
}
