<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
                        'academic_year_id' => AcademicYear::getActiveId(),
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
                        'Lulus' => 'Lulus (Alumni)',
                        'Keluar' => 'Keluar',
                        'DO' => 'Drop Out (DO)',
                    ])
                    ->default('Aktif')
                    ->live()
                    ->required(),

                TextInput::make('graduation_year')
                    ->label('Tahun Kelulusan / Angkatan')
                    ->placeholder('Contoh: 2026')
                    ->maxLength(10)
                    ->visible(fn ($get): bool => $get('status') === 'Lulus'),

                DatePicker::make('graduated_at')
                    ->label('Tanggal Kelulusan')
                    ->visible(fn ($get): bool => $get('status') === 'Lulus'),

                Textarea::make('alumni_notes')
                    ->label('Catatan Alumni / Status Setelah Lulus')
                    ->placeholder('Contoh: Melanjutkan kuliah di Universitas Gadjah Mada / Bekerja di PT XYZ')
                    ->rows(3)
                    ->visible(fn ($get): bool => $get('status') === 'Lulus')
                    ->columnSpanFull(),

                TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->revealable()
                    ->default(fn (string $operation): ?string => $operation === 'create' ? 'password' : null)
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->helperText(fn (string $operation): string => $operation === 'edit'
                        ? 'Kosongkan jika tidak ingin mengubah password'
                        : 'Default password: password (dapat diganti jika diinginkan)'
                    ),
            ]);
    }
}
