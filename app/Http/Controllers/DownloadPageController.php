<?php

namespace App\Http\Controllers;

use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class DownloadPageController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $panel = Filament::getPanel('admin');
        $loginUrl = $panel->getLoginUrl();

        $apkUrl = $this->resolveValidatedApkUrl(config('scp.mobile_apk_url'));
        $apkVersion = is_string(config('scp.mobile_apk_version')) && trim(config('scp.mobile_apk_version')) !== ''
            ? trim(config('scp.mobile_apk_version'))
            : '1.0.0';
        $apkSha256 = is_string(config('scp.mobile_apk_sha256')) && trim(config('scp.mobile_apk_sha256')) !== ''
            ? trim(config('scp.mobile_apk_sha256'))
            : null;

        $html = view('download', [
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
