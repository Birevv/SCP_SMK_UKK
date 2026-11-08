<?php

namespace App\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class LandingController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $panel = Filament::getPanel('admin');
        $loginUrl = $panel->getLoginUrl();

        $apkUrl = $this->resolveValidatedApkUrl(config('scp.mobile_apk_url'));
        $apkVersion = is_string(config('scp.mobile_apk_version')) && trim(config('scp.mobile_apk_version')) !== ''
            ? trim(config('scp.mobile_apk_version'))
            : null;
        $apkSha256 = is_string(config('scp.mobile_apk_sha256')) && trim(config('scp.mobile_apk_sha256')) !== ''
            ? trim(config('scp.mobile_apk_sha256'))
            : null;

        $html = view('landing', [
            'loginUrl' => $loginUrl,
            'apkUrl' => $apkUrl,
            'apkVersion' => $apkVersion,
            'apkSha256' => $apkSha256,
        ])->render();

        $etag = '"'.substr(sha1($html), 0, 16).'"';

        $response = new Response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
            'ETag' => $etag,
        ]);

        $response->isNotModified($request);

        return $response;
    }

    public function redirectToLogin(Request $request): RedirectResponse
    {
        $query = $request->getQueryString();
        $target = '/app/login'.($query !== null && $query !== '' ? '?'.$query : '');

        return redirect($target, 301);
    }

    public function redirectToPasswordReset(Request $request, string $any): RedirectResponse
    {
        $query = $request->getQueryString();
        $cleanPath = ltrim($any, '/');
        $target = '/app/password-reset/'.$cleanPath.($query !== null && $query !== '' ? '?'.$query : '');

        return redirect($target, 301);
    }

    private function resolveValidatedApkUrl(mixed $candidate): ?string
    {
        if (! is_string($candidate)) {
            return null;
        }

        $trimmed = trim($candidate);

        if ($trimmed === '' || ! Str::startsWith($trimmed, 'https://')) {
            return null;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return $trimmed;
    }
}
