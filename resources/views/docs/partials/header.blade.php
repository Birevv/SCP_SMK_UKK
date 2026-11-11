@php
    $logoSrc = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset('logo.png');
@endphp

<header class="sticky top-0 z-50 bg-white/95 dark:bg-[#09090b]/95 backdrop-blur-md border-b border-slate-200 dark:border-zinc-800 text-slate-800 dark:text-slate-100 transition-colors">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        
        {{-- Sisi Kiri: Logo & Judul Dokumentasi --}}
        <div class="flex items-center gap-3 min-w-0">
            <button
                type="button"
                id="docs-mobile-menu-btn"
                class="lg:hidden p-2 -ml-1 rounded-md text-slate-500 hover:text-slate-800 hover:bg-slate-100 dark:text-zinc-400 dark:hover:text-white dark:hover:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-[#1D4ED8]"
                aria-label="Buka menu navigasi dokumentasi"
            >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>

            <a href="{{ route('docs') }}" class="flex items-center gap-3 focus:outline-none focus:ring-2 focus:ring-[#1D4ED8] rounded-md p-1 group">
                <img
                    src="{{ $logoSrc }}"
                    alt="Logo SMK Negeri 1 Bantul"
                    width="32"
                    height="32"
                    class="w-8 h-8 object-contain shrink-0"
                />
                <span class="text-base sm:text-lg font-bold tracking-tight text-slate-900 dark:text-white group-hover:text-[#1D4ED8] dark:group-hover:text-blue-400 transition-colors">
                    SCP Docs
                </span>
                <span class="text-xs text-slate-400 dark:text-zinc-500 hidden sm:inline">
                    / SMK N 1 Bantul
                </span>
            </a>
        </div>

        {{-- Sisi Tengah: Tombol Pencarian Cepat --}}
        <div class="flex-1 max-w-md mx-2">
            <button
                type="button"
                data-docs-search-trigger
                class="w-full flex items-center justify-between gap-2 px-3.5 py-2 rounded-lg bg-slate-100 dark:bg-zinc-900 hover:bg-slate-200/70 dark:hover:bg-zinc-800 border border-slate-200 dark:border-zinc-800 text-slate-500 dark:text-zinc-400 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-[#1D4ED8]"
                aria-label="Cari dokumentasi (Ctrl + K)"
            >
                <div class="flex items-center gap-2.5 truncate">
                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <span class="truncate">Cari dokumentasi SCP...</span>
                </div>
                <kbd class="hidden sm:inline-block px-2 py-0.5 text-xs font-mono text-slate-500 bg-white dark:bg-zinc-800 rounded border border-slate-200 dark:border-zinc-700">
                    Ctrl K
                </kbd>
            </button>
        </div>

        {{-- Sisi Kanan: Tautan Cepat & Aksi Masuk --}}
        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
            <a
                href="{{ route('landing') }}"
                class="hidden md:inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900 dark:text-zinc-300 dark:hover:text-white px-2.5 py-1.5 transition-colors"
            >
                Beranda
            </a>

            <a
                href="{{ route('download') }}"
                class="hidden md:inline-flex items-center text-sm font-medium text-slate-600 hover:text-[#1D4ED8] dark:text-zinc-300 dark:hover:text-blue-400 px-2.5 py-1.5 transition-colors"
            >
                Unduh APK
            </a>

            {{-- Pengatur Tema Terang / Gelap --}}
            <div class="inline-flex items-center p-0.5 rounded-lg bg-slate-100 dark:bg-zinc-900 border border-slate-200 dark:border-zinc-800" role="group" aria-label="Pengaturan tema tampilan">
                <button
                    type="button"
                    data-theme-btn="light"
                    aria-pressed="true"
                    title="Mode Terang"
                    class="theme-switch-btn inline-flex items-center justify-center w-7 h-7 rounded text-xs text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="4"></circle>
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"></path>
                    </svg>
                    <span class="sr-only">Light</span>
                </button>
                <button
                    type="button"
                    data-theme-btn="dark"
                    aria-pressed="false"
                    title="Mode Gelap"
                    class="theme-switch-btn inline-flex items-center justify-center w-7 h-7 rounded text-xs text-slate-600 dark:text-zinc-400 hover:text-slate-900 dark:hover:text-white transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <span class="sr-only">Dark</span>
                </button>
            </div>

            {{-- Tombol Buka Panel --}}
            <a
                href="{{ $loginUrl }}"
                class="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-[#1D4ED8] hover:bg-blue-800 text-white text-sm font-semibold shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                Masuk Panel
            </a>
        </div>
    </div>
</header>
