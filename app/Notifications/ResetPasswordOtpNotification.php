<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordOtpNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $otp,
        public int $validityMinutes = 5
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->full_name ?? $notifiable->username ?? 'Pengguna';

        return (new MailMessage)
            ->subject('Kode OTP Reset Password - School Class Progress')
            ->greeting("Halo, {$name}!")
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda pada sistem School Class Progress (SCP).')
            ->line('Gunakan 6-digit kode OTP di bawah ini untuk mereset kata sandi Anda:')
            ->line("# {$this->otp}")
            ->line("Kode OTP ini hanya berlaku selama {$this->validityMinutes} menit.")
            ->line('Demi keamanan, jangan berikan kode ini kepada siapa pun.')
            ->line('Jika Anda tidak merasa meminta reset password, abaikan pesan ini. Kata sandi akun Anda tetap aman.')
            ->salutation('Salam hormat, Tim Pengembang SCP SMK');
    }
}
