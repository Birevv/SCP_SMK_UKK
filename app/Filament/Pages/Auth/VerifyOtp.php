<?php

namespace App\Filament\Pages\Auth;

use App\Services\PasswordResetOtpService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class VerifyOtp extends BaseRequestPasswordReset
{
    use WithRateLimiting;

    public ?string $email = '';

    public function mount(?string $email = null): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->email = $email ?? request()->query('email');

        if (blank($this->email)) {
            $this->redirect(filament()->getRequestPasswordResetUrl());

            return;
        }

        $this->form->fill();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Verifikasi OTP - School Class Progress';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Verifikasi OTP';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Periksa email Anda untuk mendapatkan kode OTP.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('otp')
                    ->label('Masukkan Kode OTP')
                    ->required()
                    ->length(6)
                    ->numeric()
                    ->placeholder('Contoh: 123456')
                    ->prefixIcon('heroicon-m-key')
                    ->autofocus(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('request')
            ->footer([
                Actions::make([$this->getRequestFormAction()])
                    ->alignment(Alignment::Center)
                    ->fullWidth(true)
                    ->key('form-actions-submit'),
                Actions::make([$this->getResendOtpAction()])
                    ->alignment(Alignment::Center)
                    ->key('form-actions-resend'),
                Actions::make([$this->loginAction])
                    ->alignment(Alignment::Center)
                    ->key('form-actions-login'),
            ]);
    }

    protected function getRequestFormAction(): Action
    {
        return Action::make('request')
            ->label('Verifikasi')
            ->color('primary')
            ->icon('heroicon-m-check-circle')
            ->submit('request');
    }

    public function getResendOtpAction(): Action
    {
        return Action::make('resendOtp')
            ->label('Kirim Ulang Kode OTP')
            ->link()
            ->color('primary')
            ->action(fn () => $this->resendOtp());
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->color('gray')
            ->label('Kembali ke Halaman Masuk')
            ->icon('heroicon-m-arrow-left')
            ->url(filament()->getLoginUrl());
    }

    public function request(): void
    {
        $data = $this->form->getState();
        $otp = trim($data['otp'] ?? '');

        if (blank($otp) || strlen($otp) !== 6) {
            Notification::make()
                ->title('Kode OTP Tidak Lengkap')
                ->body('Kode OTP harus terdiri dari 6 digit angka.')
                ->warning()
                ->send();

            return;
        }

        try {
            $resetToken = app(PasswordResetOtpService::class)->verifyOtp($this->email, $otp);

            Notification::make()
                ->title('Kode OTP Benar')
                ->body('Verifikasi berhasil. Silakan buat kata sandi baru Anda.')
                ->success()
                ->send();

            // Redirect ke halaman baru (SetNewPassword) dengan signed URL
            $signedUrl = URL::temporarySignedRoute(
                'filament.admin.auth.password-reset.reset',
                now()->addMinutes(15),
                [
                    'email' => $this->email,
                    'token' => $resetToken,
                ]
            );

            $this->redirect($signedUrl);
        } catch (ValidationException $e) {
            $errorMessage = $e->validator->errors()->first('otp') ?? $e->getMessage();

            Notification::make()
                ->title('Verifikasi OTP Gagal')
                ->body($errorMessage)
                ->danger()
                ->send();

            throw $e;
        }
    }

    public function resendOtp(): void
    {
        if (blank($this->email)) {
            return;
        }

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title('Terlalu Banyak Permintaan')
                ->body("Silakan tunggu {$exception->secondsUntilAvailable} detik sebelum meminta kembali.")
                ->danger()
                ->send();

            return;
        }

        try {
            app(PasswordResetOtpService::class)->sendOtp($this->email);

            Notification::make()
                ->title('Kode OTP Baru Dikirim')
                ->body("Kode OTP baru telah dikirimkan ke {$this->email}.")
                ->success()
                ->send();
        } catch (ValidationException $e) {
            $errorMessage = $e->validator->errors()->first('email') ?? $e->getMessage();

            Notification::make()
                ->title('Gagal Mengirim Ulang OTP')
                ->body($errorMessage)
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal Menghubungi Server Email')
                ->body('Terjadi kendala saat mengirim email: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
