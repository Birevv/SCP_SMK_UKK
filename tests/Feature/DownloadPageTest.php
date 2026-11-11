<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DownloadPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_download_page_renders_hero_guide_and_changelog(): void
    {
        $response = $this->get('/download');

        $loginUrl = Filament::getPanel('admin')->getLoginUrl();

        $response->assertOk();
        $response->assertSee('School Class Progress');
        $response->assertSee('SMK Negeri 1 Bantul');
        $response->assertSee('Unduh Langsung');
        $response->assertSee('Aplikasi SCP');
        $response->assertSee('Download SCP v1.0.0 (.apk)');
        $response->assertSee('Lihat Changelog');
        $response->assertSee('Versi Aplikasi');
        $response->assertSee('Tanggal Rilis');
        $response->assertSee('Minimal Sistem');
        $response->assertSee('Panduan Install');
        $response->assertSee('Unduh File APK');
        $response->assertSee('Izinkan Instalasi');
        $response->assertSee('Pasang & Masuk');
        $response->assertSee('Riwayat Pembaruan');
        $response->assertSee('Changelog SCP — Versi 1.0.0');
        $response->assertSee('Versi Terbaru');
        $response->assertSee('href="'.$loginUrl.'"', false);
    }

    public function test_download_page_renders_active_download_link_when_apk_url_configured(): void
    {
        $apkUrl = 'https://releases.smkn1bantul.sch.id/scp-mobile-v1.0.0.apk';

        config([
            'scp.mobile_apk_url' => $apkUrl,
            'scp.mobile_apk_version' => '1.0.0',
            'scp.mobile_apk_sha256' => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
        ]);

        $response = $this->get('/download');

        $response->assertOk();
        $response->assertSee('href="'.route('download.apk').'"', false);
        $response->assertDontSee('e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855');
    }

    public function test_download_page_is_stateless_and_has_security_headers(): void
    {
        $response = $this->get('/download');

        $response->assertOk();
        $this->assertEmpty($response->headers->getCookies(), 'Download page should not set session or XSRF cookies.');

        $cacheControl = (string) $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=300', $cacheControl);
        $this->assertNotEmpty($response->headers->get('ETag'));

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("default-src 'self'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_direct_download_endpoint_redirects_to_configured_apk_url(): void
    {
        $apkUrl = 'https://github.com/Birevv/scp_mobile/releases/latest/download/app-release.apk';

        config([
            'scp.mobile_apk_url' => $apkUrl,
        ]);

        $response = $this->get('/download/apk');

        $response->assertRedirect($apkUrl);
    }
}
