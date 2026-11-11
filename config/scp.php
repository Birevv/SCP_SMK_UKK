<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mobile Application (Flutter APK) Distribution
    |--------------------------------------------------------------------------
    |
    | When mobile_apk_url is empty or not an HTTPS URL, the landing page
    | renders the download buttons in a disabled "Segera hadir" state.
    | Set MOBILE_APK_URL to a valid https:// URL in .env to activate it.
    |
    */

    'mobile_apk_url' => env('MOBILE_APK_URL', 'https://github.com/Birevv/scp_mobile/releases/latest/download/app-release.apk'),
    'mobile_apk_version' => env('MOBILE_APK_VERSION', '1.0.0'),
    'mobile_apk_sha256' => env('MOBILE_APK_SHA256', 'ce7ed87e406f26a9b9feec40431bf25e15449dfc91d3f32c6a840ebbc9b20e83'),

];
