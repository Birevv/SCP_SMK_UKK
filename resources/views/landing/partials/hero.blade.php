<section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 min-h-[calc(100vh-4rem)] py-10 sm:py-12 lg:py-20 flex items-center">
    <div class="w-full grid grid-cols-1 lg:grid-cols-12 gap-8 sm:gap-10 lg:gap-12 items-center">
        <div class="lg:col-span-6">
            <p class="text-xs text-ink-muted tabular-nums enter-1">SMK Negeri 1 Bantul · Tahun Ajaran 2026/2027</p>

            <h1 class="hero-headline text-ink mt-4 enter-1">
                Kelola jurnal mengajar, presensi, dan perizinan siswa dalam satu sistem sekolah.
            </h1>

            <p class="text-[16px] sm:text-[18px] leading-[26px] sm:leading-[28px] text-ink-secondary max-w-[36rem] mt-5 enter-2">
                School Class Progress membantu sekolah mengelola jadwal blok, jurnal mengajar, kehadiran siswa, serta pengajuan izin secara terintegrasi melalui panel web dan aplikasi Android.
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-3 enter-3">
                <a
                    href="{{ $loginUrl }}"
                    class="inline-flex items-center justify-center gap-2 h-12 px-5 rounded-[10px] bg-accent text-on-accent text-[15px] font-medium hover:bg-accent-hover ui-interactive ui-focus"
                >
                    <span>Masuk ke sistem</span>
                    <svg class="w-4.5 h-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>

                @if ($apkUrl)
                    <a
                        href="{{ $apkUrl }}"
                        rel="noopener"
                        class="inline-flex items-center justify-center gap-2.5 h-12 px-5 rounded-[10px] bg-raised border border-hairline-strong text-[15px] font-medium text-ink hover:bg-card ui-interactive ui-focus"
                    >
                        <svg class="w-4.5 h-4.5 text-ink-secondary shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        <span>Unduh aplikasi Android</span>
                    </a>
                @else
                    <div
                        aria-disabled="true"
                        class="inline-flex items-center gap-2.5 h-12 px-4 rounded-[10px] bg-raised border border-hairline text-[14px] text-ink-secondary select-none"
                    >
                        <svg class="w-4.5 h-4.5 text-ink-muted shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                        </svg>
                        <span>Aplikasi Android segera hadir</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="lg:col-span-6 enter-2">
            @include('landing.partials.journal-sheet')
        </div>
    </div>
</section>
