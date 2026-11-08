@php
    $faviconHref = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset('favicon.ico');
    $canonicalUrl = rtrim((string) config('app.url', url('/')), '/').'/';
    $metaDescription = 'School Class Progress membantu SMK Negeri 1 Bantul mengelola jadwal blok, jurnal mengajar, kehadiran siswa, serta pengajuan izin secara terintegrasi melalui panel web dan aplikasi Android.';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>School Class Progress — SMK Negeri 1 Bantul</title>
    <meta name="description" content="{{ $metaDescription }}" />
    <link rel="canonical" href="{{ $canonicalUrl }}" />
    <link rel="icon" href="{{ $faviconHref }}" />

    <meta property="og:type" content="website" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:title" content="School Class Progress — SMK Negeri 1 Bantul" />
    <meta property="og:description" content="{{ $metaDescription }}" />
    <meta property="og:url" content="{{ $canonicalUrl }}" />

    @vite(['resources/css/landing.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-paper text-ink antialiased">
    @include('landing.partials.nav', ['loginUrl' => $loginUrl])

    <main id="konten-utama" class="flex-1">
        @include('landing.partials.hero', [
            'loginUrl' => $loginUrl,
            'apkUrl' => $apkUrl,
        ])

        @include('landing.partials.alur')

        @include('landing.partials.peran')

        @include('landing.partials.aplikasi', [
            'apkUrl' => $apkUrl,
            'apkVersion' => $apkVersion,
            'apkSha256' => $apkSha256,
        ])

        @include('landing.partials.faq')
    </main>

    @include('landing.partials.footer', ['loginUrl' => $loginUrl])
</body>
</html>
