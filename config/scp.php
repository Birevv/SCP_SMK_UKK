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

    'mobile_apk_url' => env('MOBILE_APK_URL'),
    'mobile_apk_version' => env('MOBILE_APK_VERSION'),
    'mobile_apk_sha256' => env('MOBILE_APK_SHA256'),

];
