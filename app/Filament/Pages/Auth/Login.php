<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class Login extends BaseLogin
{
    public function getTitle(): string|Htmlable
    {
        return 'Masuk - School Class Progress';
    }

    public function getHeading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('filament-panels::auth/pages/login.multi_factor.heading');
        }

        return 'Masuk ke Sistem School Class Progress';
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (filled($this->userUndertakingMultiFactorAuthentication)) {
            return __('filament-panels::auth/pages/login.multi_factor.subheading');
        }

        return 'Masukkan Username dan Password Untuk Masuk ke Sistem';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getLoginFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getLoginFormComponent(): Component
    {
        return TextInput::make('login')
            ->label('Username')
            ->placeholder('Masukkan Username')
            ->required()
            ->autocomplete('username')
            ->autofocus();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(#[SensitiveParameter] array $data): array
    {
        $login = trim($data['login'] ?? '');

        return [
            'password' => $data['password'],
            fn ($query) => $query->where('identity_number', $login)
                ->orWhere('username', $login),
        ];
    }

    protected function throwFailureValidationException(): never
    {
        $loginData = $this->form->getRawState();
        $login = trim((string) ($loginData['login'] ?? ''));
        $password = (string) ($loginData['password'] ?? '');

        if ($login !== '') {
            $user = User::where('identity_number', $login)
                ->orWhere('username', $login)
                ->first();

            if ($user && Hash::check($password, $user->password)) {
                if ($user->status === 'Lulus') {
                    Notification::make()
                        ->title('Akun Alumni Nonaktif')
                        ->body('Akun Anda berstatus Alumni (Sudah Lulus). Hak akses ke portal siswa telah dinonaktifkan.')
                        ->warning()
                        ->send();

                    throw ValidationException::withMessages([
                        'data.login' => 'Akun Anda berstatus Alumni (Sudah Lulus). Hak akses ke portal telah dinonaktifkan.',
                    ]);
                }

                if (in_array($user->status, ['Keluar', 'DO'])) {
                    Notification::make()
                        ->title('Akun Nonaktif')
                        ->body("Akun Anda berstatus {$user->status}. Hak akses ke portal telah dinonaktifkan.")
                        ->danger()
                        ->send();

                    throw ValidationException::withMessages([
                        'data.login' => "Akun Anda berstatus {$user->status}. Hak akses ke portal telah dinonaktifkan.",
                    ]);
                }
            }
        }

        Notification::make()
            ->title('Login Gagal')
            ->body('Username/NISN atau password yang Anda masukkan salah. Silakan coba lagi.')
            ->danger()
            ->send();

        throw ValidationException::withMessages([
            'data.login' => 'Username/NISN atau password salah.',
        ]);
    }
}
