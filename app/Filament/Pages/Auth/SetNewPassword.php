<?php

namespace App\Filament\Pages\Auth;

use App\Services\PasswordResetOtpService;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;

class SetNewPassword extends BaseResetPassword
{
    public function mount(?string $email = null, #[SensitiveParameter] ?string $token = null): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->token = $token ?? request()->query('token');
        $this->email = $email ?? request()->query('email');

        if (blank($this->email) || blank($this->token)) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Tautan verifikasi tidak lengkap. Silakan lakukan permintaan ulang.')
                ->danger()
                ->send();

            $this->redirect(filament()->getRequestPasswordResetUrl());

            return;
        }

        // Validasi apakah token masih berlaku dan cocok di database
        $isValid = app(PasswordResetOtpService::class)->validateResetToken($this->email, $this->token);

        if (! $isValid) {
            Notification::make()
                ->title('Sesi Kedaluwarsa')
                ->body('Sesi verifikasi OTP Anda tidak valid atau telah kedaluwarsa (berlaku 15 menit). Silakan verifikasi ulang.')
                ->danger()
                ->send();

            $this->redirect(filament()->getRequestPasswordResetUrl());

            return;
        }

        $this->form->fill([
            'email' => $this->email,
        ]);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Atur Kata Sandi Baru - School Class Progress';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Buat Kata Sandi Baru';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Tentukan kata sandi baru yang aman untuk akun Anda.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Alamat Email Terverifikasi')
                    ->disabled()
                    ->dehydrated(true)
                    ->prefixIcon('heroicon-m-envelope'),

                TextInput::make('password')
                    ->label('Kata Sandi Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->prefixIcon('heroicon-m-lock-closed')
                    ->same('passwordConfirmation')
                    ->placeholder('Minimal 8 karakter')
                    ->autofocus(),

                TextInput::make('passwordConfirmation')
                    ->label('Ulangi Kata Sandi Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->prefixIcon('heroicon-m-lock-closed')
                    ->placeholder('Ulangi kata sandi baru')
                    ->dehydrated(false),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('resetPassword')
                ->label('Simpan Kata Sandi Baru')
                ->submit('resetPassword'),
            $this->loginAction,
        ];
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label('Kembali ke Halaman Masuk')
            ->icon('heroicon-m-arrow-left')
            ->url(filament()->getLoginUrl());
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        $data = $this->form->getState();
        $password = $data['password'] ?? '';

        try {
            app(PasswordResetOtpService::class)->resetPasswordWithToken(
                $this->email,
                $this->token,
                $password
            );

            Notification::make()
                ->title('Kata Sandi Berhasil Diperbarui')
                ->body('Kata sandi akun Anda telah berhasil diubah. Silakan masuk menggunakan kata sandi baru.')
                ->success()
                ->send();

            return app(PasswordResetResponse::class);
        } catch (ValidationException $e) {
            $errorMessage = $e->validator->errors()->first() ?? $e->getMessage();

            Notification::make()
                ->title('Gagal Memperbarui Kata Sandi')
                ->body($errorMessage)
                ->danger()
                ->send();

            throw $e;
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Terjadi Kesalahan')
                ->body('Kendala: '.$e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }
}
