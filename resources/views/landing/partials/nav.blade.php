@php
    $logoSrc = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset('logo.png');
@endphp

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
        <nav aria-label="Navigasi utama" class="hidden lg:flex items-center gap-6">
            <a href="#hero" class="text-[14px] font-medium text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Beranda</a>
            <a href="#fitur" class="text-[14px] font-medium text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Fitur</a>
            <a href="#cara-kerja" class="text-[14px] font-medium text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Cara kerja</a>
            <a href="#tentang" class="text-[14px] font-medium text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Tentang kami</a>
            <a href="{{ route('docs') }}" class="text-[14px] font-medium text-ink-secondary hover:text-[#ff2d20] ui-interactive ui-focus rounded px-1 py-1">Dokumentasi</a>
            <a href="{{ route('download') }}" class="text-[14px] font-medium text-ink-secondary hover:text-[#1D4ED8] dark:hover:text-blue-400 ui-interactive ui-focus rounded px-1 py-1">Download APK</a>

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

            {{-- Tombol Utama Masuk Panel (Solid Blue Ink #1D4ED8) --}}
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
            <div class="inline-flex items-center p-1 rounded-xl bg-slate-100 dark:bg-slate-800/90 border border-slate-200 dark:border-slate-700 gap-1.5" role="group" aria-label="Pengaturan tema tampilan">
                <button
                    type="button"
                    data-theme-btn="light"
                    aria-pressed="true"
                    aria-label="Mode Terang (Light)"
                    title="Mode Terang"
                    class="theme-switch-btn inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-medium cursor-pointer select-none ui-interactive ui-focus"
                >
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
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
                    class="theme-switch-btn inline-flex items-center justify-center w-7 h-7 rounded-lg text-xs font-medium cursor-pointer select-none ui-interactive ui-focus"
                >
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                    <span class="sr-only">Dark</span>
                </button>
            </div>

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
                    <a href="#hero" class="px-3 py-2 rounded-lg text-[14px] font-medium text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Beranda</a>
                    <a href="#fitur" class="px-3 py-2 rounded-lg text-[14px] font-medium text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Fitur</a>
                    <a href="#cara-kerja" class="px-3 py-2 rounded-lg text-[14px] font-medium text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Cara kerja</a>
                    <a href="#tentang" class="px-3 py-2 rounded-lg text-[14px] font-medium text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Tentang kami</a>
                    <a href="{{ route('docs') }}" class="px-3 py-2 rounded-lg text-[14px] font-medium text-ink-secondary hover:bg-raised hover:text-[#ff2d20] ui-interactive">Dokumentasi</a>
                    <a href="{{ route('download') }}" class="px-3 py-2 rounded-lg text-[14px] font-medium text-[#1D4ED8] dark:text-blue-400 hover:bg-raised ui-interactive">Download APK</a>
                    <a href="{{ $loginUrl }}" class="mt-1 px-3 py-2 rounded-lg text-[14px] font-semibold text-white bg-[#1D4ED8] hover:bg-blue-800 shadow-xs ui-interactive text-center">Masuk ke sistem</a>
                </div>
            </details>
        </div>
    </div>
</header>
