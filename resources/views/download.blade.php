@php
    $faviconHref = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset('favicon.ico');
    $logoSrc = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset('logo.png');
    $canonicalUrl = rtrim((string) config('app.url', url('/')), '/').'/download';
    $metaDescription = 'Unduh langsung aplikasi School Class Progress (SCP) untuk Android — SMK Negeri 1 Bantul. Pengelolaan jurnal mengajar, presensi siswa, dan perizinan sekolah dalam satu genggaman.';
    $apkUrl = $apkUrl ?? null;
    $apkVersion = $apkVersion ?? '1.0.1';
    $apkSha256 = $apkSha256 ?? null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Unduh Aplikasi SCP — SMK Negeri 1 Bantul</title>
    <meta name="description" content="{{ $metaDescription }}" />
    <link rel="canonical" href="{{ $canonicalUrl }}" />
    <link rel="icon" href="{{ $faviconHref }}" />

    <meta property="og:type" content="website" />
    <meta property="og:locale" content="id_ID" />
    <meta property="og:title" content="Unduh Aplikasi SCP — SMK Negeri 1 Bantul" />
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

    {{-- NAVBAR DOWNLOAD PAGE --}}
    <header class="sticky top-0 z-40 bg-paper/95 backdrop-blur-md border-b border-hairline transition-colors">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 sm:h-18 flex items-center justify-between gap-6">
            {{-- Sisi Kiri: Identitas Resmi Sekolah --}}
            <a href="{{ route('landing') }}" class="inline-flex items-center gap-3.5 min-w-0 ui-focus rounded-lg group">
                <img
                    src="{{ $logoSrc }}"
                    alt="Logo SMK Negeri 1 Bantul"
                    width="34"
                    height="34"
                    class="w-8.5 h-8.5 object-contain shrink-0"
                />
                <div class="flex flex-col min-w-0">
                    <span class="text-[15px] sm:text-[16px] font-semibold text-ink leading-tight tracking-tight truncate">School Class Progress</span>
                    <span class="text-[12px] text-ink-muted leading-tight truncate">SMK Negeri 1 Bantul</span>
                </div>
            </a>

            {{-- Sisi Kanan: Menu Navigasi Desktop (Rapi, Terkurasi & Minimalis) --}}
            <nav aria-label="Navigasi halaman unduh" class="hidden lg:flex items-center gap-6">
                <a href="{{ route('landing') }}" class="text-[14px] font-medium text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Beranda</a>
                <a href="{{ route('landing') }}#fitur" class="text-[14px] font-medium text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Fitur</a>
                <a href="{{ route('landing') }}#cara-kerja" class="text-[14px] font-medium text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Cara kerja</a>
                <a href="{{ route('landing') }}#tentang" class="text-[14px] font-medium text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Tentang kami</a>
                <a href="{{ route('download') }}" class="text-[14px] font-semibold text-[#1D4ED8] dark:text-blue-400 ui-interactive ui-focus rounded px-1 py-1">Download APK</a>

                <div class="h-4 w-px bg-hairline-strong mx-2" aria-hidden="true"></div>

                {{-- Pengaturan Tema Tampilan (Icon Saja: Light / Dark) --}}
                <div class="inline-flex items-center p-1 rounded-xl bg-slate-100 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 gap-1.5" role="group" aria-label="Pengaturan tema tampilan">
                    <button
                        type="button"
                        data-theme-btn="light"
                        aria-pressed="true"
                        aria-label="Mode Terang (Light)"
                        title="Mode Terang"
                        class="theme-switch-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-medium cursor-pointer select-none ui-interactive ui-focus transition-all"
                    >
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                        </svg>
                        <span class="sr-only">Light</span>
                    </button>
                    <button
                        type="button"
                        data-theme-btn="dark"
                        aria-pressed="false"
                        aria-label="Mode Gelap (Dark)"
                        title="Mode Gelap"
                        class="theme-switch-btn inline-flex items-center justify-center w-8 h-8 rounded-lg text-xs font-medium cursor-pointer select-none ui-interactive ui-focus transition-all"
                    >
                        <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                        </svg>
                        <span class="sr-only">Dark</span>
                    </button>
                </div>

                {{-- Tombol Utama Masuk Panel --}}
                <a
                    href="{{ $loginUrl }}"
                    class="ml-1 inline-flex items-center justify-center gap-2 h-10 px-5 rounded-lg bg-[#1D4ED8] text-white text-[14px] font-medium hover:bg-blue-800 shadow-subtle ui-interactive ui-focus"
                >
                    <span>Masuk Panel</span>
                    <span class="sr-only">Masuk ke sistem</span>
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </nav>

            {{-- Mobile Controls --}}
            <div class="flex items-center gap-2 lg:hidden shrink-0">
                <a
                    href="{{ $loginUrl }}"
                    class="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-[#1D4ED8] text-white text-[13px] font-medium hover:bg-blue-800 ui-interactive ui-focus"
                >Masuk</a>

                <details class="relative">
                    <summary
                        aria-label="Buka menu navigasi"
                        class="inline-flex items-center justify-center h-8 px-2.5 rounded-lg bg-white dark:bg-slate-800 text-slate-800 dark:text-slate-100 border border-slate-300 dark:border-slate-600 shadow-2xs hover:bg-slate-50 dark:hover:bg-slate-700 text-[13px] font-medium cursor-pointer select-none ui-interactive ui-focus"
                    >
                        <span>Menu</span>
                    </summary>
                    <div class="absolute right-0 mt-2 w-52 rounded-xl bg-card border border-hairline-strong shadow-sheet p-2 flex flex-col gap-1 z-50">
                        <a href="{{ route('landing') }}" class="px-3 py-2 rounded-lg text-[14px] font-medium text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Beranda</a>
                        <a href="{{ route('landing') }}#fitur" class="px-3 py-2 rounded-lg text-[14px] font-medium text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Fitur</a>
                        <a href="{{ route('landing') }}#cara-kerja" class="px-3 py-2 rounded-lg text-[14px] font-medium text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Cara kerja</a>
                        <a href="{{ route('landing') }}#tentang" class="px-3 py-2 rounded-lg text-[14px] font-medium text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Tentang kami</a>
                        <a href="{{ route('download') }}" class="px-3 py-2 rounded-lg text-[14px] font-semibold text-[#1D4ED8] dark:text-blue-400 hover:bg-raised ui-interactive">Download APK</a>
                        <a href="{{ $loginUrl }}" class="mt-1 px-3 py-2 rounded-lg text-[14px] font-semibold text-white bg-[#1D4ED8] hover:bg-blue-800 shadow-xs ui-interactive text-center">Masuk ke sistem</a>
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main id="konten-utama" class="flex-1">

        {{-- SECTION 1: DOWNLOAD HERO (Mirip Referensi Gambar 1) --}}
        <section class="relative overflow-hidden py-14 sm:py-20 lg:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-14 items-center">

                    {{-- Sisi Kiri: Editorial Copy & Download CTA --}}
                    <div class="lg:col-span-6 xl:col-span-7 flex flex-col items-start enter-1">
                        
                        {{-- Pill Versi Rilis --}}
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/25 text-xs font-semibold tracking-wide">
                            <svg class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                            </svg>
                            <span>Rilis Resmi Android • Versi {{ $apkVersion }}</span>
                        </div>

                        {{-- Headline --}}
                        <h1 class="text-3xl sm:text-4xl lg:text-[46px] xl:text-[52px] font-bold text-ink leading-[1.12] tracking-tight mt-5">
                            Unduh Langsung <br class="hidden sm:inline" />
                            <span class="text-[#1D4ED8] dark:text-blue-400">Aplikasi SCP</span>
                        </h1>

                        {{-- Deskripsi --}}
                        <p class="text-[16px] sm:text-[17px] text-ink-secondary leading-relaxed mt-4 max-w-136">
                            Instal aplikasi School Class Progress secara langsung di perangkat Android Anda untuk memudahkan pencatatan jurnal mengajar, presensi siswa, dan perizinan sekolah dalam satu alur kerja terintegrasi.
                        </p>

                        {{-- Action Buttons --}}
                        <div class="mt-8 flex flex-wrap items-center gap-3.5">
                            @if ($apkUrl)
                                <a
                                    href="{{ route('download.apk') }}"
                                    rel="noopener"
                                    class="inline-flex items-center gap-2.5 h-12.5 px-6 rounded-xl bg-slate-900 hover:bg-slate-800 text-white border border-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 dark:border-white text-[15px] font-semibold shadow-subtle ui-interactive ui-focus group transition-all"
                                >
                                    <svg class="w-4 h-4 text-amber-400 dark:text-amber-600 group-hover:translate-y-0.5 transition-transform shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                                    </svg>
                                    <span>Download SCP v{{ $apkVersion }} (.apk)</span>
                                </a>
                            @else
                                <div
                                    class="inline-flex items-center gap-2.5 h-12.5 px-6 rounded-xl bg-slate-900 text-white border border-slate-800 dark:bg-white dark:text-slate-900 dark:border-white text-[15px] font-semibold shadow-subtle cursor-default select-none opacity-95 transition-all"
                                    aria-disabled="true"
                                    title="Aplikasi Android sedang dalam tahap persiapan"
                                >
                                    <svg class="w-4 h-4 text-amber-400 dark:text-amber-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                                    </svg>
                                    <span>Download SCP v{{ $apkVersion }} (.apk)</span>
                                    <span class="ml-1.5 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-400/20 text-amber-300 border border-amber-400/30 dark:bg-amber-100 dark:text-amber-800 dark:border-amber-300">
                                        Segera Hadir
                                    </span>
                                </div>
                            @endif

                            <a
                                href="#changelog"
                                class="inline-flex items-center justify-center h-12.5 px-5 rounded-xl bg-white text-slate-800 border border-slate-300 shadow-xs hover:bg-slate-50 hover:border-slate-400 dark:bg-slate-800 dark:text-slate-100 dark:border-slate-600 dark:hover:bg-slate-700 text-[14px] font-medium ui-interactive ui-focus"
                            >
                                <span>Lihat Changelog</span>
                            </a>
                        </div>

                        {{-- Metadata Specs Row (Versi, Tanggal Rilis, Minimal Sistem) --}}
                        <div class="mt-8 pt-6 border-t border-hairline w-full grid grid-cols-3 gap-4 max-w-lg text-left">
                            <div>
                                <span class="text-xs text-ink-muted block">Versi Aplikasi</span>
                                <span class="text-sm font-semibold text-ink mt-0.5 block tabular-nums">v{{ $apkVersion }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-ink-muted block">Tanggal Rilis</span>
                                <span class="text-sm font-semibold text-ink mt-0.5 block">11 Nov 2026</span>
                            </div>
                            <div>
                                <span class="text-xs text-ink-muted block">Minimal Sistem</span>
                                <span class="text-sm font-semibold text-ink mt-0.5 block">Android 8.0+</span>
                            </div>
                        </div>



                    </div>

                    {{-- Sisi Kanan: Visual Smartphone Mockup SCP (Mirip AQU Dual Phone Mockup) --}}
                    <div class="lg:col-span-6 xl:col-span-5 relative flex items-center justify-center enter-2">
                        
                        {{-- Aura Glow Emas Lembut di Belakang Smartphone --}}
                        <div class="absolute w-72 h-72 sm:w-80 sm:h-80 bg-amber-400/20 dark:bg-amber-500/15 rounded-full blur-3xl pointer-events-none -z-10" aria-hidden="true"></div>

                        {{-- Container Mockup Dua HP Berdampingan / Berlayer --}}
                        <div class="relative flex items-center justify-center gap-3 sm:gap-4 w-full max-w-95 sm:max-w-107.5">
                            
                            {{-- Phone 1: Dashboard Jurnal & Jadwal Guru (Tampak Depan Agak Miring) --}}
                            <div class="relative w-47.5 sm:w-53.75 rounded-4xl p-2 bg-slate-900 border-2 border-slate-700/80 shadow-2xl -rotate-2 hover:rotate-0 transition-transform duration-300">
                                {{-- Speaker Notch --}}
                                <div class="w-12 h-3.5 bg-slate-950 rounded-full mx-auto mb-2 flex items-center justify-center">
                                    <div class="w-2 h-2 rounded-full bg-slate-800 mr-2"></div>
                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-800"></div>
                                </div>

                                {{-- Screen Content Mockup --}}
                                <div class="rounded-3xl bg-white p-3 space-y-2.5 text-slate-900 overflow-hidden border border-slate-200">
                                    {{-- Mini App Header --}}
                                    <div class="flex items-center justify-between pb-1.5 border-b border-slate-200">
                                        <div>
                                            <p class="text-[9px] text-slate-500 leading-tight">Selamat Bertugas</p>
                                            <p class="text-[11px] font-bold text-slate-900 leading-tight">Guru Pengampu</p>
                                        </div>
                                        <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-blue-100 text-[#1D4ED8]">
                                            Putaran 1
                                        </span>
                                    </div>

                                    {{-- Mini Card Sesi Mengajar --}}
                                    <div class="p-2 rounded-xl bg-blue-50/70 border border-blue-100">
                                        <p class="text-[8px] text-[#1D4ED8] font-semibold uppercase">Jadwal Aktif Hari Ini</p>
                                        <p class="text-[10px] font-bold text-slate-900 leading-snug mt-0.5">Pemrograman Web & PBO</p>
                                        <p class="text-[8px] text-slate-500 mt-0.5">XI RPL 2 · 08.30–10.00</p>
                                    </div>

                                    {{-- Mini Presensi Badges --}}
                                    <div class="p-2 rounded-xl bg-slate-50 border border-slate-200">
                                        <div class="flex items-center justify-between text-[8px] font-medium text-slate-500 mb-1.5">
                                            <span>Kehadiran Siswa</span>
                                            <span class="text-emerald-700 font-bold">96.8%</span>
                                        </div>
                                        <div class="grid grid-cols-4 gap-1 text-center text-[8px]">
                                            <div class="p-1 rounded bg-blue-600 text-white font-bold">H: 31</div>
                                            <div class="p-1 rounded bg-slate-200 text-slate-600">S: 0</div>
                                            <div class="p-1 rounded bg-amber-100 text-amber-800 font-bold">I: 1</div>
                                            <div class="p-1 rounded bg-slate-200 text-slate-600">A: 0</div>
                                        </div>
                                    </div>

                                    {{-- Quick Action Button --}}
                                    <div class="pt-1">
                                        <div class="w-full py-1.5 rounded-lg bg-[#1D4ED8] text-white text-[9px] font-semibold text-center shadow-xs">
                                            Isi Jurnal Tatap Muka
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Phone 2: Verifikasi & Rekap Sukses (Tampak Belakang / Sedikit Bergeser) --}}
                            <div class="relative w-45 sm:w-51.25 rounded-4xl p-2 bg-slate-900 border-2 border-slate-700/80 shadow-2xl rotate-3 hover:rotate-0 transition-transform duration-300 -mt-6 sm:-mt-8">
                                {{-- Speaker Notch --}}
                                <div class="w-12 h-3.5 bg-slate-950 rounded-full mx-auto mb-2 flex items-center justify-center">
                                    <div class="w-2 h-2 rounded-full bg-slate-800 mr-2"></div>
                                    <div class="w-1.5 h-1.5 rounded-full bg-slate-800"></div>
                                </div>

                                {{-- Screen Content Mockup --}}
                                <div class="rounded-3xl bg-white p-3 space-y-2 text-slate-900 overflow-hidden border border-slate-200">
                                    {{-- Success Status Pill --}}
                                    <div class="text-center pt-1 pb-1.5">
                                        <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center mb-1">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                        </div>
                                        <p class="text-[10px] font-bold text-slate-900 leading-none">Jurnal Terkirim</p>
                                        <p class="text-[8px] text-slate-500 mt-0.5">Tersinkron ke server sekolah</p>
                                    </div>

                                    {{-- Summary List --}}
                                    <div class="space-y-1.5 text-[8px] border-t border-slate-200 pt-2">
                                        <div class="flex items-center justify-between text-slate-600">
                                            <span>Materi Pokok:</span>
                                            <span class="font-medium text-slate-900 truncate max-w-20">OOP Enkapsulasi</span>
                                        </div>
                                        <div class="flex items-center justify-between text-slate-600">
                                            <span>Foto KBM:</span>
                                            <span class="font-medium text-emerald-700">Terunggah ✓</span>
                                        </div>
                                        <div class="flex items-center justify-between text-slate-600">
                                            <span>Surat Izin:</span>
                                            <span class="font-medium text-amber-700">1 Terverifikasi</span>
                                        </div>
                                    </div>

                                    <div class="pt-1.5 border-t border-slate-200 text-center">
                                        <span class="text-[8px] text-slate-500">Validasi: Waka Kurikulum</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </section>

        {{-- SECTION 2: PANDUAN INSTALL APLIKASI (Mirip Referensi Gambar 2) --}}
        <section id="panduan" class="py-16 sm:py-20 bg-amber-500/5 border-y border-amber-500/15">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
                    
                    {{-- Sisi Kiri: Judul & Pengantar --}}
                    <div class="lg:col-span-4 flex flex-col items-start">
                        <span class="text-xs font-semibold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-2">Instalasi Praktis</span>
                        <h2 class="text-2xl sm:text-3xl font-bold text-ink tracking-tight leading-snug">
                            Panduan Install <br class="hidden sm:inline" />
                            Aplikasi
                        </h2>
                        <p class="text-sm text-ink-secondary leading-relaxed mt-3 max-w-sm">
                            Ikuti langkah mudah berikut untuk mengunduh dan memasang aplikasi School Class Progress secara langsung di perangkat Android Anda.
                        </p>
                    </div>

                    {{-- Sisi Kanan: 3 Langkah Berurutan (Cards) --}}
                    <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-5">
                        
                        {{-- Langkah 1: Unduh File APK --}}
                        <div class="rounded-2xl bg-card border border-hairline-strong p-6 shadow-xs flex flex-col items-start transition-transform hover:-translate-y-0.5">
                            <div class="w-11 h-11 rounded-xl bg-amber-500/15 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0 mb-5">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-ink leading-tight">Unduh File APK</h3>
                            <p class="text-xs text-ink-secondary leading-relaxed mt-2.5">
                                Tekan tombol Download APK di atas dan tunggu hingga proses pengunduhan file instalasi selesai di perangkat Anda.
                            </p>
                        </div>

                        {{-- Langkah 2: Izinkan Instalasi --}}
                        <div class="rounded-2xl bg-card border border-hairline-strong p-6 shadow-xs flex flex-col items-start transition-transform hover:-translate-y-0.5">
                            <div class="w-11 h-11 rounded-xl bg-amber-500/15 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0 mb-5">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-ink leading-tight">Izinkan Instalasi</h3>
                            <p class="text-xs text-ink-secondary leading-relaxed mt-2.5">
                                Buka file APK yang telah diunduh dan aktifkan izin pemasangan aplikasi dari sumber browser atau file manager Anda.
                            </p>
                        </div>

                        {{-- Langkah 3: Pasang & Masuk --}}
                        <div class="rounded-2xl bg-card border border-hairline-strong p-6 shadow-xs flex flex-col items-start transition-transform hover:-translate-y-0.5">
                            <div class="w-11 h-11 rounded-xl bg-amber-500/15 text-amber-700 dark:text-amber-400 flex items-center justify-center shrink-0 mb-5">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-ink leading-tight">Pasang &amp; Masuk</h3>
                            <p class="text-xs text-ink-secondary leading-relaxed mt-2.5">
                                Selesaikan proses pemasangan aplikasi, lalu buka School Class Progress dan masuk ke akun Anda untuk mulai mengelola kelas.
                            </p>
                        </div>

                    </div>

                </div>
            </div>
        </section>

        {{-- SECTION 3: RIWAYAT PEMBARUAN / CHANGELOG (Mirip Referensi Gambar 3) --}}
        <section id="changelog" class="py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                
                {{-- Heading Section --}}
                <div class="text-center max-w-2xl mx-auto">
                    <h2 class="text-3xl sm:text-4xl font-bold text-ink tracking-tight">
                        Riwayat Pembaruan
                    </h2>
                    <p class="text-sm sm:text-base text-ink-secondary mt-3">
                        Daftar pembaruan fitur, peningkatan performa, dan perbaikan pada setiap rilis aplikasi School Class Progress.
                    </p>
                </div>

                {{-- Changelog Cards Container --}}
                <div class="max-w-4xl mx-auto mt-10 space-y-6">

                    {{-- Versi 1.0.1 (Versi Terbaru) --}}
                    <div class="rounded-2xl bg-card border border-hairline-strong p-6 sm:p-9 shadow-sheet">
                        
                        {{-- Header Rilis --}}
                        <div class="pb-5 border-b border-hairline flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg sm:text-xl font-bold text-ink">Changelog SCP — Versi {{ $apkVersion }}</h3>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/15 text-amber-700 dark:text-amber-400 border border-amber-500/30">
                                    Versi Terbaru
                                </span>
                            </div>
                            <span class="text-xs text-ink-muted tabular-nums">Tanggal Rilis: 11 November 2026</span>
                        </div>

                        {{-- Tujuan Pembaruan --}}
                        <div class="pt-6">
                            <h4 class="text-xs font-bold text-ink uppercase tracking-wider">Tujuan Pembaruan:</h4>
                            <p class="text-sm text-ink-secondary leading-relaxed mt-2">
                                Meningkatkan akurasi dan integritas data pada pengisian jurnal tatap muka oleh guru, mewajibkan dokumentasi foto kegiatan belajar mengajar secara langsung melalui kamera, serta mencegah pencatatan ganda pada jadwal mengajar yang sama.
                            </p>
                        </div>

                        {{-- Daftar Pembaruan --}}
                        <div class="pt-6">
                            <h4 class="text-xs font-bold text-ink uppercase tracking-wider mb-3">Pembaruan &amp; Perbaikan:</h4>
                            <ul class="space-y-3 text-sm text-ink-secondary">
                                <li class="flex items-start gap-3">
                                    <span class="w-5 h-5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </span>
                                    <span><strong>Validasi Status &amp; Anti-Duplikasi Jurnal:</strong> Jadwal mengajar yang telah diisi kini ditandai dengan badge status "Sudah Diisi", dan tombol simpan dinonaktifkan otomatis agar tidak terjadi entri ganda pada jadwal yang sama.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="w-5 h-5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </span>
                                    <span><strong>Prioritas Pemilihan Jadwal Hari Ini:</strong> Saat membuka formulir jurnal, aplikasi secara cerdas memprioritaskan jadwal yang belum diisi agar guru dapat langsung bekerja tanpa harus mencari jadwal secara manual.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="w-5 h-5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </span>
                                    <span><strong>Validasi Wajib Kamera Realtime:</strong> Dokumentasi foto kegiatan belajar mengajar (KBM) wajib diambil secara langsung dari kamera perangkat guru sebelum formulir jurnal dapat dikirimkan.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="w-5 h-5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </span>
                                    <span><strong>Integrasi Multipart API &amp; HTTP Parser:</strong> Penambahan dependensi pustaka parser multipart untuk meningkatkan keandalan pengunggahan berkas foto dokumentasi ke server sekolah.</span>
                                </li>
                                <li class="flex items-start gap-3">
                                    <span class="w-5 h-5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0 mt-0.5">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                    </span>
                                    <span><strong>Penyempurnaan Navigasi Guru &amp; Wali Murid:</strong> Perbaikan pada dialog pemilih jadwal tatap muka serta optimalisasi pemantauan rekapitulasi kehadiran siswa pada dashboard wali.</span>
                                </li>
                            </ul>
                        </div>

                    </div>

                    {{-- Versi 1.0.0 (Rilis Sebelumnya) --}}
                    <div class="rounded-2xl bg-card border border-hairline p-6 sm:p-8 opacity-90 transition-opacity hover:opacity-100">
                        <div class="pb-4 border-b border-hairline flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <h3 class="text-base sm:text-lg font-bold text-ink">Changelog SCP — Versi 1.0.0</h3>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                    Rilis Perdana
                                </span>
                            </div>
                            <span class="text-xs text-ink-muted tabular-nums">11 November 2026</span>
                        </div>

                        <div class="pt-4">
                            <ul class="space-y-2 text-xs sm:text-sm text-ink-secondary">
                                <li class="flex items-start gap-2.5">
                                    <span class="text-ink-muted mt-0.5">•</span>
                                    <span>Rilis perdana aplikasi mobile School Class Progress (SCP) untuk platform Android.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="text-ink-muted mt-0.5">•</span>
                                    <span>Modul pengisian jurnal tatap muka otomatis berdasarkan jadwal blok aktif (Putaran 1 &amp; Putaran 2).</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="text-ink-muted mt-0.5">•</span>
                                    <span>Pencatatan presensi siswa per jam mengajar dengan status Hadir, Sakit, Izin, dan Alpa.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="text-ink-muted mt-0.5">•</span>
                                    <span>Sinkronisasi otomatis surat izin/sakit siswa yang disetujui pihak sekolah.</span>
                                </li>
                                <li class="flex items-start gap-2.5">
                                    <span class="text-ink-muted mt-0.5">•</span>
                                    <span>Dashboard pemantauan jadwal dan rekap kehadiran bagi siswa dan wali murid.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>

            </div>
        </section>

    </main>

    {{-- SECTION 4: FOOTER (Mirip Referensi Gambar 4) --}}
    <footer class="bg-[#1E293B] dark:bg-[#09090b] text-white border-t border-slate-800 dark:border-zinc-800 pt-16 pb-12 transition-colors">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-10 lg:gap-8 pb-12 border-b border-slate-700/80 dark:border-zinc-800">
                
                {{-- Kolom 1: Logo & Deskripsi (Col 4) --}}
                <div class="lg:col-span-4 flex flex-col items-start">
                    <a href="{{ route('landing') }}" class="inline-flex items-center gap-3 ui-focus rounded-lg group">
                        <img
                            src="{{ $logoSrc }}"
                            alt="Logo SMK Negeri 1 Bantul"
                            width="36"
                            height="36"
                            class="w-9 h-9 object-contain shrink-0"
                        />
                        <div class="flex flex-col">
                            <span class="text-base font-semibold text-white tracking-tight leading-tight">School Class Progress</span>
                            <span class="text-xs text-slate-400 leading-tight">SMK Negeri 1 Bantul</span>
                        </div>
                    </a>

                    <p class="text-sm text-slate-400 leading-relaxed mt-4 max-w-sm">
                        Menghadirkan pengalaman presensi dan jurnal mengajar yang lebih mudah melalui ekosistem informasi sekolah yang cepat, aman, dan terpadu.
                    </p>

                    <div class="mt-6 flex items-center gap-3">
                        <a
                            href="https://www.instagram.com/smkn1bantul/"
                            target="_blank"
                            rel="noopener"
                            aria-label="Instagram SMK Negeri 1 Bantul"
                            class="w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-colors"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="20" height="20" x="2" y="2" rx="5" ry="5"/>
                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/>
                                <line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>
                            </svg>
                        </a>
                        <a
                            href="https://smkn1bantul.sch.id"
                            target="_blank"
                            rel="noopener"
                            aria-label="Website Resmi SMK Negeri 1 Bantul"
                            class="w-9 h-9 rounded-full bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white flex items-center justify-center transition-colors"
                        >
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/>
                                <path d="M2 12h20"/>
                            </svg>
                        </a>
                    </div>
                </div>

                {{-- Kolom 2: SCP Navigation (Col 3) --}}
                <div class="lg:col-span-3">
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3.5">SCP</h3>
                    <ul class="space-y-2.5 text-sm">
                        <li>
                            <a href="{{ route('landing') }}" class="text-slate-400 hover:text-white transition-colors">Beranda</a>
                        </li>
                        <li>
                            <a href="{{ route('landing') }}#cara-kerja" class="text-slate-400 hover:text-white transition-colors">Cara Kerja</a>
                        </li>
                        <li>
                            <a href="{{ route('landing') }}#preview-fitur" class="text-slate-400 hover:text-white transition-colors">Antarmuka</a>
                        </li>
                        <li>
                            <a href="{{ route('landing') }}#tentang" class="text-slate-400 hover:text-white transition-colors">Tentang Kami</a>
                        </li>
                        <li>
                            <a href="{{ route('download') }}" class="text-white font-medium hover:text-blue-300 transition-colors">Download APK</a>
                        </li>
                    </ul>
                </div>

                {{-- Kolom 3: Legal (Col 2) --}}
                <div class="lg:col-span-2">
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3.5">Legal</h3>
                    <ul class="space-y-2.5 text-sm">
                        <li>
                            <a href="{{ route('landing') }}#faq" class="text-slate-400 hover:text-white transition-colors">Syarat & Ketentuan</a>
                        </li>
                        <li>
                            <a href="{{ route('landing') }}#faq" class="text-slate-400 hover:text-white transition-colors">Kebijakan Privasi</a>
                        </li>
                    </ul>
                </div>

                {{-- Kolom 4: Hubungi Kami (Col 3) --}}
                <div class="lg:col-span-3">
                    <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider mb-3.5">Hubungi Kami</h3>
                    <div class="text-sm text-slate-400 space-y-2.5 leading-relaxed">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="20" height="16" x="2" y="4" rx="2"/>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                            </svg>
                            <span class="text-slate-300">smkn1bantul@yahoo.com</span>
                        </div>
                        <p class="text-xs text-slate-500 pt-1">
                            SMK Negeri 1 Bantul · Jl. Parangtritis Km 11, Sabdodadi, Bantul, D.I. Yogyakarta 55715
                        </p>
                        <div class="pt-2">
                            <a
                                href="{{ $loginUrl }}"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-400 hover:text-blue-300 transition-colors"
                            >
                                <span>Masuk ke Panel Operasional</span>
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Copyright Bawah --}}
            <div class="pt-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4 text-xs text-slate-400">
                <p>
                    &copy; 2026 School Class Progress — SMK Negeri 1 Bantul. All Rights Reserved.
                </p>
                <p class="tabular-nums">
                    Tahun Ajaran 2026/2027
                </p>
            </div>

        </div>
    </footer>

</body>
</html>
