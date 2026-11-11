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

    'mobile_apk_url' => env('MOBILE_APK_URL', 'https://github.com/Birevv/SCP_SMK_UKK/releases/latest/download/scp-mobile-v1.0.1.apk'),
    'mobile_apk_version' => env('MOBILE_APK_VERSION', '1.0.1'),
    'mobile_apk_sha256' => env('MOBILE_APK_SHA256', '8e55f4e6f673dca7cf948edd4d62b0bf21d48f26be6f49bfcc830387d6e8610a'),

];
