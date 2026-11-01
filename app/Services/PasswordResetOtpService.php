<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ResetPasswordOtpNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordResetOtpService
{
    public const OTP_EXPIRY_MINUTES = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Generate dan kirim kode OTP ke email pengguna.
     *
     * @throws ValidationException
     */
    public function sendOtp(string $email): void
    {
        $normalizedEmail = strtolower(trim($email));

        if (empty($normalizedEmail) || ! filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'email' => 'Format alamat email tidak valid.',
            ]);
        }

        $user = User::where('email', $normalizedEmail)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Alamat email tidak terdaftar dalam sistem.',
            ]);
        }

        // Cek cooldown jika baru saja mengirim OTP (< 60 detik)
        $existing = DB::table('password_reset_tokens')->where('email', $normalizedEmail)->first();
        if ($existing && $existing->created_at) {
            $secondsSinceLast = Carbon::parse($existing->created_at)->diffInSeconds(now());
            if ($secondsSinceLast < self::RESEND_COOLDOWN_SECONDS) {
                $secondsWait = self::RESEND_COOLDOWN_SECONDS - $secondsSinceLast;
                throw ValidationException::withMessages([
                    'email' => "Harap tunggu {$secondsWait} detik sebelum meminta kode OTP baru.",
                ]);
            }
        }

        // Generate 6-digit angka acak aman
        $otp = (string) random_int(100000, 999999);

        // Simpan OTP dalam bentuk hash di tabel password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $normalizedEmail],
            [
                'token' => Hash::make($otp),
                'created_at' => now(),
            ]
        );

        // Kirim email notifikasi ke user
        $user->notify(new ResetPasswordOtpNotification($otp, self::OTP_EXPIRY_MINUTES));
    }

    /**
     * Verifikasi kode OTP dan simpan password baru.
     *
     * @throws ValidationException
     */
    public function resetPassword(string $email, string $otp, string $newPassword): void
    {
        $normalizedEmail = strtolower(trim($email));
        $cleanOtp = trim($otp);

        if (empty($cleanOtp)) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP wajib diisi.',
            ]);
        }

        $record = DB::table('password_reset_tokens')->where('email', $normalizedEmail)->first();

        if (! $record || ! $record->created_at) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP belum pernah diminta atau telah kedaluwarsa.',
            ]);
        }

        // Cek kedaluwarsa OTP (> 5 menit)
        $isExpired = Carbon::parse($record->created_at)->addMinutes(self::OTP_EXPIRY_MINUTES)->isPast();
        if ($isExpired) {
            DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP telah kedaluwarsa. Silakan minta kode OTP baru.',
            ]);
        }

        // Cek kecocokan OTP dengan hash
        if (! Hash::check($cleanOtp, $record->token)) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP yang Anda masukkan salah. Periksa kembali email Anda.',
            ]);
        }

        $user = User::where('email', $normalizedEmail)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Pengguna dengan email ini tidak ditemukan.',
            ]);
        }

        // Update password baru
        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        // Hapus token/OTP setelah berhasil digunakan (single-use)
        DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();
    }

    /**
     * Verifikasi kode OTP dan generate reset token jika valid.
     *
     * @throws ValidationException
     */
    public function verifyOtp(string $email, string $otp): string
    {
        $normalizedEmail = strtolower(trim($email));
        $cleanOtp = trim($otp);

        if (empty($cleanOtp)) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP wajib diisi.',
            ]);
        }

        $record = DB::table('password_reset_tokens')->where('email', $normalizedEmail)->first();

        if (! $record || ! $record->created_at) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP belum pernah diminta atau telah kedaluwarsa.',
            ]);
        }

        // Cek kedaluwarsa OTP (> 5 menit)
        $isExpired = Carbon::parse($record->created_at)->addMinutes(self::OTP_EXPIRY_MINUTES)->isPast();
        if ($isExpired) {
            DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP telah kedaluwarsa. Silakan minta kode OTP baru.',
            ]);
        }

        // Cek kecocokan OTP
        if (! Hash::check($cleanOtp, $record->token)) {
            throw ValidationException::withMessages([
                'otp' => 'Kode OTP yang Anda masukkan salah. Periksa kembali email Anda.',
            ]);
        }

        // Buat token reset aman
        $resetToken = \Illuminate\Support\Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $normalizedEmail],
            [
                'token' => Hash::make($resetToken),
                'created_at' => now(),
            ]
        );

        return $resetToken;
    }

    /**
     * Cek apakah token reset password valid dan belum kedaluwarsa (15 menit).
     */
    public function validateResetToken(string $email, string $token): bool
    {
        $normalizedEmail = strtolower(trim($email));
        $record = DB::table('password_reset_tokens')->where('email', $normalizedEmail)->first();

        if (! $record || ! $record->created_at) {
            return false;
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            return false;
        }

        return Hash::check($token, $record->token);
    }

    /**
     * Simpan password baru menggunakan reset token yang sudah terverifikasi.
     *
     * @throws ValidationException
     */
    public function resetPasswordWithToken(string $email, string $token, string $newPassword): void
    {
        $normalizedEmail = strtolower(trim($email));

        if (! $this->validateResetToken($normalizedEmail, $token)) {
            throw ValidationException::withMessages([
                'email' => 'Sesi reset password tidak valid atau telah kedaluwarsa. Silakan lakukan proses verifikasi ulang.',
            ]);
        }

        $user = User::where('email', $normalizedEmail)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Pengguna dengan email ini tidak ditemukan.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $normalizedEmail)->delete();
    }
}
