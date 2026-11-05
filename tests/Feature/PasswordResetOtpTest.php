<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\OtpPasswordReset;
use App\Filament\Pages\Auth\VerifyOtp;
use App\Models\User;
use App\Notifications\ResetPasswordOtpNotification;
use App\Services\PasswordResetOtpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'guru.test@smk.sch.id',
            'full_name' => 'Guru Penguji UKK',
            'username' => 'gurutest',
            'password' => Hash::make('password_lama_123'),
        ], $attributes));
    }

    public function test_user_can_access_filament_otp_reset_password_page(): void
    {
        $response = $this->get(route('filament.admin.auth.password-reset.request'));

        $response->assertSuccessful();
        $response->assertSee('Lupa Kata Sandi');
        $response->assertSee('Kirim Kode OTP');
    }

    public function test_user_can_access_verify_otp_page(): void
    {
        $response = $this->get(route('filament.admin.auth.password-reset.verify-otp', ['email' => 'guru.test@smk.sch.id']));

        $response->assertSuccessful();
        $response->assertSee('Verifikasi OTP');
        $response->assertSee('Periksa email Anda untuk mendapatkan kode OTP.');
        $response->assertSee('Masukkan Kode OTP');
        $response->assertSee('Verifikasi');
        $response->assertSee('Kirim Ulang Kode OTP');
    }

    public function test_submitting_email_redirects_to_verify_otp_page(): void
    {
        Notification::fake();
        $user = $this->createUser();

        Livewire::test(OtpPasswordReset::class)
            ->set('data.email', $user->email)
            ->call('request')
            ->assertRedirect(route('filament.admin.auth.password-reset.verify-otp', ['email' => $user->email]));
    }

    public function test_submitting_otp_on_verify_page_redirects_to_new_password_page(): void
    {
        $user = $this->createUser();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make('123456'), 'created_at' => now()]
        );

        Livewire::test(VerifyOtp::class, ['email' => $user->email])
            ->set('data.otp', '123456')
            ->call('request')
            ->assertRedirect();
    }

    public function test_send_otp_fails_if_email_is_not_registered(): void
    {
        $service = app(PasswordResetOtpService::class);

        $this->expectException(ValidationException::class);
        $service->sendOtp('email_tidak_ada@smk.sch.id');
    }

    public function test_send_otp_stores_token_and_sends_notification(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $service = app(PasswordResetOtpService::class);

        $service->sendOtp($user->email);

        // Pastikan token tersimpan di DB
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);

        // Pastikan notifikasi dikirimkan ke user
        Notification::assertSentTo($user, ResetPasswordOtpNotification::class, function ($notification) {
            return strlen($notification->otp) === 6 && is_numeric($notification->otp);
        });
    }

    public function test_send_otp_has_cooldown_rate_limit(): void
    {
        Notification::fake();

        $user = $this->createUser();
        $service = app(PasswordResetOtpService::class);

        // Panggilan pertama berhasil
        $service->sendOtp($user->email);

        // Panggilan kedua berturut-turut harus terkena cooldown
        $this->expectException(ValidationException::class);
        $service->sendOtp($user->email);
    }

    public function test_verify_otp_fails_with_invalid_otp(): void
    {
        $user = $this->createUser();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make('123456'), 'created_at' => now()]
        );

        $service = app(PasswordResetOtpService::class);

        $this->expectException(ValidationException::class);
        $service->verifyOtp($user->email, '999999');
    }

    public function test_verify_otp_fails_if_otp_is_expired(): void
    {
        $user = $this->createUser();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make('123456'), 'created_at' => now()->subMinutes(6)]
        );

        $service = app(PasswordResetOtpService::class);

        $this->expectException(ValidationException::class);
        $service->verifyOtp($user->email, '123456');
    }

    public function test_verify_otp_generates_token_and_allows_access_to_new_password_page(): void
    {
        $user = $this->createUser();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make('123456'), 'created_at' => now()]
        );

        $service = app(PasswordResetOtpService::class);
        $resetToken = $service->verifyOtp($user->email, '123456');

        $this->assertNotEmpty($resetToken);

        // Akses halaman baru SetNewPassword menggunakan signed URL
        $signedUrl = URL::temporarySignedRoute(
            'filament.admin.auth.password-reset.reset',
            now()->addMinutes(15),
            [
                'email' => $user->email,
                'token' => $resetToken,
            ]
        );

        $response = $this->get($signedUrl);
        $response->assertSuccessful();
        $response->assertSee('Buat Kata Sandi Baru');
        $response->assertSee('Alamat Email Terverifikasi');
        $response->assertSee('Kata Sandi Baru');
        $response->assertSee('Simpan Kata Sandi Baru');
    }

    public function test_reset_password_with_token_succeeds_and_deletes_record(): void
    {
        $user = $this->createUser();

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make('123456'), 'created_at' => now()]
        );

        $service = app(PasswordResetOtpService::class);
        $resetToken = $service->verifyOtp($user->email, '123456');

        $service->resetPasswordWithToken($user->email, $resetToken, 'password_baru_789');

        // Pastikan password user berhasil diperbarui
        $user->refresh();
        $this->assertTrue(Hash::check('password_baru_789', $user->password));

        // Pastikan token langsung terhapus (single-use)
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_api_endpoints_send_otp_and_reset_password(): void
    {
        Notification::fake();

        $user = $this->createUser(['email' => 'siswa.api@smk.sch.id']);

        // 1. API Send OTP
        $responseSend = $this->postJson('/api/forgot-password/send-otp', [
            'email' => 'siswa.api@smk.sch.id',
        ]);

        $responseSend->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        // Ambil OTP yang digenerate dari notification
        $capturedOtp = null;
        Notification::assertSentTo($user, ResetPasswordOtpNotification::class, function ($notification) use (&$capturedOtp) {
            $capturedOtp = $notification->otp;

            return true;
        });

        $this->assertNotNull($capturedOtp);

        // 2. API Reset Password dengan OTP yang didapat
        $responseReset = $this->postJson('/api/forgot-password/reset', [
            'email' => 'siswa.api@smk.sch.id',
            'otp' => $capturedOtp,
            'password' => 'password_baru_api_456',
            'password_confirmation' => 'password_baru_api_456',
        ]);

        $responseReset->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('password_baru_api_456', $user->password));
    }
}
