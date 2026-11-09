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

    @php
        try {
            $viteAssets = app(\Illuminate\Foundation\Vite::class)(['resources/css/landing.css', 'resources/js/app.js']);
        } catch (\Illuminate\Foundation\ViteException $e) {
            try {
                $viteAssets = app(\Illuminate\Foundation\Vite::class)(['resources/css/app.css', 'resources/js/app.js']);
            } catch (\Throwable $t) {
                $viteAssets = '';
            }
        }
    @endphp
    {!! $viteAssets !!}
</head>
<body class="min-h-screen flex flex-col bg-paper text-ink antialiased selection:bg-accent-tint selection:text-[#1D4ED8]">
    {{-- SECTION A: NAVBAR --}}
    @include('landing.partials.nav', ['loginUrl' => $loginUrl])

    <main id="konten-utama" class="flex-1">
        {{-- SECTION B: HERO SECTION --}}
        @include('landing.partials.hero', [
            'loginUrl' => $loginUrl,
            'apkUrl' => $apkUrl,
            'apkVersion' => $apkVersion,
            'apkSha256' => $apkSha256,
        ])

        {{-- SECTION C: FEATURE HIGHLIGHT --}}
        @include('landing.partials.features')

        {{-- SECTION D: CARA KERJA SCP --}}
        @include('landing.partials.how-it-works')

        {{-- SECTION E: PREVIEW FITUR APLIKASI --}}
        @include('landing.partials.feature-preview')

        {{-- SECTION F: ALUR JADWAL BLOK DUA MINGGU --}}
        @include('landing.partials.cycle-schedule')

        {{-- SECTION G: TENTANG SCP --}}
        @include('landing.partials.about')

        {{-- SECTION H: FAQ --}}
        @include('landing.partials.faq', ['loginUrl' => $loginUrl])
    </main>

    {{-- SECTION I: FOOTER --}}
    @include('landing.partials.footer', [
        'loginUrl' => $loginUrl,
        'apkUrl' => $apkUrl,
        'apkVersion' => $apkVersion,
        'apkSha256' => $apkSha256,
    ])
</body>
</html>
