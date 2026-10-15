<?php

namespace App\Filament\Profile;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as PagesEditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * @property-read Schema $form
 */
class EditProfile extends PagesEditProfile
{
    protected Width|string|null $maxWidth = Width::SixExtraLarge;

    public static function isSimple(): bool
    {
        return false;
    }

    protected function getRedirectUrl(): ?string
    {
        return '/admin';
    }

    public static function getLabel(): string
    {
        return 'Profil Pengguna';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // 1. Kartu Profil & Data Diri (Read-Only Hero Header)
                View::make('filament.profile.profile-card')
                    ->columnSpanFull(),

                // 2. Grid Dua Kolom: Form Ubah Profil (Kiri) & Form Ganti Password (Kanan)
                Grid::make([
                    'default' => 1,
                    'lg' => 2,
                ])
                    ->schema([
                        // Form Ubah Profil
                        Section::make('Ubah Informasi Profil')
                            ->description('Perbarui foto profil, nama lengkap, email, dan nomor telepon aktif Anda.')
                            ->icon('heroicon-o-user-circle')
                            ->schema([
                                FileUpload::make('profile_photo')
                                    ->label('Foto Profil (Avatar)')
                                    ->avatar()
                                    ->image()
                                    ->directory('profile_photos')
                                    ->disk('public')
                                    ->maxSize(3072)
                                    ->alignCenter()
                                    ->columnSpanFull(),

                                TextInput::make('full_name')
                                    ->label('Nama Lengkap')
                                    ->prefixIcon('heroicon-m-user')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('email')
                                    ->label('Alamat Email')
                                    ->prefixIcon('heroicon-m-envelope')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->columnSpanFull(),

                                TextInput::make('phone')
                                    ->label('Nomor Telepon / HP')
                                    ->prefixIcon('heroicon-m-phone')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('Contoh: 081234567890')
                                    ->columnSpanFull(),
                            ]),

                        // Form Ganti Password
                        Section::make('Keamanan & Ganti Password')
                            ->description('Kelola kata sandi akun Anda. Kosongkan jika tidak ingin mengubah kata sandi saat ini.')
                            ->icon('heroicon-o-lock-closed')
                            ->schema([
                                TextInput::make('currentPassword')
                                    ->label('Password Lama (Saat Ini)')
                                    ->prefixIcon('heroicon-m-key')
                                    ->password()
                                    ->revealable()
                                    ->currentPassword()
                                    ->required(fn (Get $get): bool => filled($get('password')))
                                    ->dehydrated(false)
                                    ->columnSpanFull(),

                                TextInput::make('password')
                                    ->label('Password Baru')
                                    ->prefixIcon('heroicon-m-lock-closed')
                                    ->password()
                                    ->revealable()
                                    ->same('passwordConfirmation')
                                    ->rule(Password::default())
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                    ->columnSpanFull(),

                                TextInput::make('passwordConfirmation')
                                    ->label('Konfirmasi Password Baru')
                                    ->prefixIcon('heroicon-m-check-badge')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (Get $get): bool => filled($get('password')))
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Ubah Profil')
            ->icon('heroicon-m-check');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Kembali ke Dashboard')
            ->icon('heroicon-m-arrow-left')
            ->url('/admin');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
            $this->getLogoutFormAction(),
        ];
    }

    protected function getLogoutFormAction(): Action
    {
        return Action::make('logout')
            ->label('Keluar (Logout)')
            ->color('danger')
            ->icon('heroicon-o-arrow-left-on-rectangle')
            ->requiresConfirmation()
            ->modalHeading('Konfirmasi Keluar')
            ->modalDescription('Apakah Anda yakin ingin keluar dari sistem? Sesi aktif Anda akan diakhiri.')
            ->modalSubmitActionLabel('Ya, Keluar')
            ->action(function () {
                auth()->logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();

                return redirect('/admin/login');
            });
    }
}
