<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_landing_page_renders_core_content_and_panel_login_link(): void
    {
        $response = $this->get('/');

        $loginUrl = Filament::getPanel('admin')->getLoginUrl();

        $response->assertOk();
        $response->assertSee('School Class Progress');
        $response->assertSee('SMK Negeri 1 Bantul');
        $response->assertSee('Kelola jurnal mengajar, presensi, dan perizinan siswa dalam satu sistem sekolah.');
        $response->assertSee('School Class Progress membantu sekolah mengelola jadwal blok, jurnal mengajar, kehadiran siswa, serta pengajuan izin secara terintegrasi melalui panel web dan aplikasi Android.');
        $response->assertSee('Masuk ke sistem');
        $response->assertSee('href="'.$loginUrl.'"', false);
        $response->assertSee('Putaran 1');
        $response->assertSee('Ilustrasi tampilan sistem');
        $response->assertSee('Siswa 01');
        $response->assertSee('School Class Progress menerapkan pembagian hak akses berdasarkan peran pengguna.');
        $response->assertSee('Apa itu School Class Progress?');
        $response->assertSee('Siapa yang harus dihubungi apabila mengalami kendala akses?');
        $response->assertSee('data-theme-btn="light"', false);
        $response->assertSee('data-theme-btn="dark"', false);
    }

    public function test_apk_status_shows_coming_soon_notice_when_url_is_empty(): void
    {
        config(['scp.mobile_apk_url' => null]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Aplikasi Android segera hadir');
        $response->assertSee('Aplikasi sedang dalam tahap persiapan sebelum dibagikan kepada pengguna sekolah.');
        $response->assertSee('aria-disabled="true"', false);
        $response->assertDontSee('Unduh APK');
    }

    public function test_apk_button_becomes_active_link_when_valid_https_url_is_configured(): void
    {
        $apkUrl = 'https://releases.smkn1bantul.sch.id/scp-mobile-v1.0.0.apk';

        config([
            'scp.mobile_apk_url' => $apkUrl,
            'scp.mobile_apk_version' => '1.0.0',
            'scp.mobile_apk_sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('href="'.$apkUrl.'"', false);
        $response->assertSee('Versi 1.0.0');
        $response->assertSee('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855');
        $response->assertDontSee('Aplikasi Android segera hadir');
    }

    public function test_apk_button_rejects_non_https_or_unsafe_schemes(): void
    {
        foreach (['http://example.com/app.apk', 'javascript:alert(1)', 'ftp://example.com/app.apk', 'not-a-url'] as $unsafeUrl) {
            config(['scp.mobile_apk_url' => $unsafeUrl]);

            $response = $this->get('/');

            $response->assertOk();
            $response->assertSee('Aplikasi Android segera hadir');
            $response->assertDontSee('href="'.$unsafeUrl.'"', false);
        }
    }

    public function test_landing_page_is_stateless_and_includes_security_and_cache_headers(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertEmpty($response->headers->getCookies(), 'Landing page should not set session or XSRF cookies.');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=300', $cacheControl);
        $this->assertNotEmpty($response->headers->get('ETag'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));

        $etag = (string) $response->headers->get('ETag');
        $cachedResponse = $this->withHeaders(['If-None-Match' => $etag])->get('/');
        $cachedResponse->assertStatus(304);
    }

    public function test_panel_at_app_path_redirects_guest_to_panel_login(): void
    {
        $response = $this->get('/app');

        $response->assertRedirect('/app/login');
    }

    public function test_legacy_auth_urls_redirect_permanently_with_query_string_preserved(): void
    {
        $loginRedirect = $this->get('/login?intended=1');
        $loginRedirect->assertStatus(301);
        $loginRedirect->assertRedirect('/app/login?intended=1');

        $otpRedirect = $this->get('/password-reset/verify-otp?email=guru.test%40smk.sch.id');
        $otpRedirect->assertStatus(301);
        $otpRedirect->assertRedirect('/app/password-reset/verify-otp?email=guru.test%40smk.sch.id');
    }

    public function test_protected_web_route_redirects_guest_to_login_route_without_error(): void
    {
        $response = $this->get('/attendance-report/print');

        $response->assertRedirect(route('login'));
    }

    public function test_landing_page_protects_privacy_and_does_not_leak_personal_data_or_external_cdns(): void
    {
        $response = $this->get('/');

        $html = $response->getContent();

        $this->assertStringNotContainsString('fonts.bunny.net', $html);
        $this->assertStringNotContainsString('fonts.googleapis.com', $html);
        $this->assertStringNotContainsString('walikelas1', $html);
        $this->assertStringNotContainsString('gurubk', $html);
        $this->assertStringNotContainsString('Aditya Pratama', $html);
        $this->assertStringNotContainsString('Budi Santoso', $html);
        $this->assertStringNotContainsString('240118', $html);
    }
}
