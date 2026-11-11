@php
    $faviconHref = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset('favicon.ico');
    $canonicalUrl = rtrim((string) config('app.url', url('/')), '/').'/docs';
    $metaDescription = 'Dokumentasi teknis, operasional SOP, dan evaluasi proyek School Class Progress (SCP) SMK Negeri 1 Bantul berbasis Laravel & Filament v4.';
@endphp
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dokumentasi Resmi : School Class Progress (SCP) SMK</title>
    <meta name="description" content="{{ $metaDescription }}" />
    <link rel="canonical" href="{{ $canonicalUrl }}" />
    <link rel="icon" href="{{ $faviconHref }}" />

    {{-- Open Graph / SEO --}}
    <meta property="og:type" content="website" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:title" content="Dokumentasi Resmi : School Class Progress (SCP) SMK" />
    <meta property="og:description" content="{{ $metaDescription }}" />
    <meta property="og:url" content="{{ $canonicalUrl }}" />

    @php
        try {
            $viteAssets = app(\Illuminate\Foundation\Vite::class)([
                'resources/css/docs.css',
                'resources/js/app.js',
                'resources/js/docs.js'
            ]);
        } catch (\Illuminate\Foundation\ViteException $e) {
            try {
                $viteAssets = app(\Illuminate\Foundation\Vite::class)([
                    'resources/css/landing.css',
                    'resources/js/app.js'
                ]);
            } catch (\Throwable $t) {
                $viteAssets = '';
            }
        }
    @endphp
    {!! $viteAssets !!}
</head>
<body class="min-h-screen bg-[#fafafa] dark:bg-[#09090b] text-[#171717] dark:text-[#fafafa] antialiased selection:bg-[#eff6ff] dark:selection:bg-blue-900 selection:text-[#1D4ED8] dark:selection:text-blue-200">

    {{-- 1. HEADER UTAMA --}}
    @include('docs.partials.header', [
        'loginUrl' => $loginUrl,
        'appVersion' => $appVersion,
    ])

    <div class="max-w-[1500px] mx-auto flex min-h-[calc(100vh-3.5rem)]">
        
        {{-- 2. NAVIGASI SIDEBAR KIRI --}}
        @include('docs.partials.sidebar', [
            'activeSection' => $activeSection,
            'sections' => $sections,
        ])

        {{-- 3. AREA KONTEN UTAMA DOKUMENTASI --}}
        <main class="flex-1 w-full lg:pl-72 xl:pr-72 px-4 sm:px-8 py-8 lg:py-10 min-w-0">
            <div id="docs-content" class="max-w-4xl mx-auto space-y-16 text-base">
                
                {{-- BAGIAN 1: PROLOGUE --}}
                @include('docs.partials.sections.prologue')

                {{-- BAGIAN 2: GETTING STARTED --}}
                @include('docs.partials.sections.getting-started')

                {{-- BAGIAN 3: 5 LOGIKA BISNIS UTAMA --}}
                @include('docs.partials.sections.business-logic')

                {{-- BAGIAN 4: DOKUMENTASI OPERASIONAL (SOP) --}}
                @include('docs.partials.sections.operational-sop')

                {{-- BAGIAN 5: MODUL & FITUR TEKNIS --}}
                @include('docs.partials.sections.system-modules')

                {{-- BAGIAN 6: REST API & MOBILE --}}
                @include('docs.partials.sections.rest-api')

                {{-- BAGIAN 7: EVALUASI PROYEK & PENGUJIAN --}}
                @include('docs.partials.sections.project-evaluation')

                {{-- FOOTER SEDERHANA --}}
                <footer class="pt-8 border-t border-slate-200 dark:border-zinc-800 text-xs text-slate-500 dark:text-zinc-400 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p>© {{ date('Y') }} School Class Progress. SMK Negeri 1 Bantul.</p>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('landing') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Beranda</a>
                        <a href="{{ route('download') }}" class="hover:text-slate-900 dark:hover:text-white transition-colors">Download APK</a>
                        <a href="{{ $loginUrl }}" class="text-[#1D4ED8] dark:text-blue-400 hover:underline font-medium">Buka Panel</a>
                    </div>
                </footer>
            </div>
        </main>

        {{-- 4. DAFTAR ISI KANAN --}}
        @include('docs.partials.toc')

    </div>

    {{-- 5. MODAL PENCARIAN CEPAT --}}
    @include('docs.partials.search-modal')

</body>
</html>
