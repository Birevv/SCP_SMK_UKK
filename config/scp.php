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

    'mobile_apk_url' => env('MOBILE_APK_URL', 'https://github.com/Birevv/SCP_SMK_UKK/releases/download/v1.0.2/scp-mobile-v1.0.2.apk'),
    'mobile_apk_version' => env('MOBILE_APK_VERSION', '1.0.2'),
    'mobile_apk_sha256' => env('MOBILE_APK_SHA256', '13fc07220d446ee3397937c7c509564bfe237b767b3ff805f7cb9dfaf75ac840'),

];
