<?php

namespace App\Filament\Pages\Auth;

use App\Services\PasswordResetOtpService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class OtpPasswordReset extends BaseRequestPasswordReset
{
    use WithRateLimiting;

    public function getTitle(): string|Htmlable
    {
        return 'Lupa Kata Sandi - School Class Progress';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Lupa Kata Sandi';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Masukkan alamat email Anda untuk menerima kode verifikasi OTP.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Alamat Email Terdaftar')
                    ->email()
                    ->required()
                    ->autocomplete('email')
                    ->autofocus()
                    ->placeholder('nama@smk.sch.id')
                    ->prefixIcon('heroicon-m-envelope'),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getRequestFormAction(),
            $this->loginAction,
        ];
    }

    protected function getRequestFormAction(): Action
    {
        return Action::make('request')
            ->label('Kirim Kode OTP')
            ->icon('heroicon-m-paper-airplane')
            ->submit('request');
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label('Kembali ke Halaman Masuk')
            ->icon('heroicon-m-arrow-left')
            ->url(filament()->getLoginUrl());
    }

    public function request(): void
    {
        $data = $this->form->getState();
        $email = strtolower(trim($data['email'] ?? ''));

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Terlalu Banyak Permintaan')
                ->body("Silakan tunggu {$exception->secondsUntilAvailable} detik sebelum mencoba kembali.")
                ->danger()
                ->send();

            return;
        }

        try {
            app(PasswordResetOtpService::class)->sendOtp($email);

            Notification::make()
                ->title('Kode OTP Berhasil Dikirim')
                ->body("Kode OTP 6-digit telah dikirim ke {$email}. Mengalihkan ke halaman verifikasi...")
                ->success()
                ->send();

            // Mengalihkan (Redirect) ke halaman Verifikasi OTP
            $this->redirect(route('filament.admin.auth.password-reset.verify-otp', ['email' => $email]));
        } catch (ValidationException $e) {
            $errorMessage = $e->validator->errors()->first('email') ?? $e->getMessage();

            Notification::make()
                ->title('Gagal Mengirim OTP')
                ->body($errorMessage)
                ->danger()
                ->send();

            throw $e;
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Menghubungi Server Email')
                ->body('Terjadi kendala saat mengirim email: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
