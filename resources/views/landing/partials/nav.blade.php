@php
    $logoSrc = file_exists(public_path('images/logo_smk.png'))
        ? asset('images/logo_smk.png')
        : asset('logo.png');
@endphp

<header class="sticky top-0 z-30 bg-paper/95 border-b border-hairline">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
        <a href="{{ route('landing') }}" class="inline-flex items-center gap-2.5 min-w-0 ui-focus rounded-lg">
            <img
                src="{{ $logoSrc }}"
                alt="Logo SMK Negeri 1 Bantul"
                width="32"
                height="32"
                class="w-8 h-8 object-contain shrink-0"
            />
            <div class="flex items-center gap-2 min-w-0">
                <span class="text-[15px] sm:text-[16px] font-medium text-ink leading-none truncate">School Class Progress</span>
                <span class="hidden lg:inline text-ink-muted text-[13px] leading-none" aria-hidden="true">·</span>
                <span class="hidden lg:inline text-[13px] text-ink-muted leading-none">SMK Negeri 1 Bantul</span>
            </div>
        </a>

        <nav aria-label="Navigasi utama" class="hidden md:flex items-center gap-5">
            <a href="#alur" class="text-[14px] text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Alur</a>
            <a href="#peran" class="text-[14px] text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Peran</a>
            <a href="#aplikasi" class="text-[14px] text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">Aplikasi</a>
            <a href="#faq" class="text-[14px] text-ink-secondary hover:text-ink ui-interactive ui-focus rounded px-1 py-1">FAQ</a>

            <div class="inline-flex items-center h-9 p-1 rounded-[10px] bg-raised border border-hairline-strong" role="group" aria-label="Pengaturan tema tampilan">
                <button
                    type="button"
                    data-theme-btn="light"
                    aria-pressed="true"
                    class="theme-switch-btn inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg text-xs font-medium cursor-pointer select-none ui-interactive ui-focus"
                >
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                    <span>Light</span>
                </button>
                <button
                    type="button"
                    data-theme-btn="dark"
                    aria-pressed="false"
                    class="theme-switch-btn inline-flex items-center gap-1.5 h-7 px-2.5 rounded-lg text-xs font-medium cursor-pointer select-none ui-interactive ui-focus"
                >
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                    <span>Dark</span>
                </button>
            </div>

            <a
                href="{{ $loginUrl }}"
                class="inline-flex items-center justify-center h-9 px-4 rounded-[10px] bg-ink text-card text-[14px] font-medium hover:bg-ink-secondary ui-interactive ui-focus"
            >Masuk</a>
        </nav>

        <div class="flex items-center gap-2 md:hidden shrink-0">
            <div class="inline-flex items-center h-9 p-1 rounded-[10px] bg-raised border border-hairline-strong" role="group" aria-label="Pengaturan tema tampilan">
                <button
                    type="button"
                    data-theme-btn="light"
                    aria-pressed="true"
                    aria-label="Mode Light"
                    class="theme-switch-btn inline-flex items-center gap-1 h-7 px-2 rounded-lg text-xs font-medium cursor-pointer select-none ui-interactive ui-focus"
                >
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                    <span class="hidden sm:inline">Light</span>
                </button>
                <button
                    type="button"
                    data-theme-btn="dark"
                    aria-pressed="false"
                    aria-label="Mode Dark"
                    class="theme-switch-btn inline-flex items-center gap-1 h-7 px-2 rounded-lg text-xs font-medium cursor-pointer select-none ui-interactive ui-focus"
                >
                    <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                    <span class="hidden sm:inline">Dark</span>
                </button>
            </div>

            <a
                href="{{ $loginUrl }}"
                class="inline-flex items-center justify-center h-9 px-3 rounded-[10px] bg-ink text-card text-[14px] font-medium hover:bg-ink-secondary ui-interactive ui-focus"
            >Masuk</a>

            <details class="relative">
                <summary
                    aria-label="Buka menu navigasi"
                    class="inline-flex items-center justify-center h-9 px-2.5 rounded-[10px] bg-raised border border-hairline-strong text-[14px] text-ink cursor-pointer select-none ui-interactive ui-focus"
                >
                    <span>Menu</span>
                </summary>
                <div class="absolute right-0 mt-2 w-48 rounded-xl bg-card border border-hairline-strong shadow-sheet p-2 flex flex-col gap-1">
                    <a href="#alur" class="px-3 py-2 rounded-lg text-[14px] text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Alur</a>
                    <a href="#peran" class="px-3 py-2 rounded-lg text-[14px] text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Peran</a>
                    <a href="#aplikasi" class="px-3 py-2 rounded-lg text-[14px] text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">Aplikasi</a>
                    <a href="#faq" class="px-3 py-2 rounded-lg text-[14px] text-ink-secondary hover:bg-raised hover:text-ink ui-interactive">FAQ</a>
                </div>
            </details>
        </div>
    </div>
</header>
