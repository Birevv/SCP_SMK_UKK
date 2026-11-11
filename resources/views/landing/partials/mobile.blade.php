<section id="download" class="py-20 sm:py-24 relative">
    {{-- Backward compatibility anchor for #aplikasi --}}
    <span id="aplikasi" class="sr-only" aria-hidden="true"></span>

    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {{-- Single Strong CTA Section Card --}}
        <div class="rounded-2xl bg-card border border-hairline-strong p-8 sm:p-12 lg:p-16 shadow-sheet">
            <div class="max-w-3xl mx-auto text-center">
                <span class="text-xs font-semibold tracking-wider text-[#1D4ED8] dark:text-blue-400 mb-3 block">
                    AKSES SISTEM SEKOLAH
                </span>

                <h2 class="text-2xl sm:text-3xl lg:text-[40px] font-bold text-ink leading-tight tracking-tight">
                    Pengelolaan Akademik Dimulai dari Sistem yang Terintegrasi.
                </h2>

                <p class="text-[16px] sm:text-[17px] text-ink-secondary leading-relaxed mt-4 max-w-2xl mx-auto">
                    Gunakan School Class Progress untuk mendukung aktivitas akademik sekolah. Kelola jadwal, pengisian jurnal mengajar, pemantauan kehadiran, dan perizinan siswa melalui satu alur kerja terpadu.
                </p>

                {{-- Action Buttons: Masuk Panel + Opsi Android --}}
                <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
                    {{-- CTA Utama: Masuk Panel --}}
                    <a
                        href="{{ $loginUrl }}"
                        class="inline-flex items-center justify-center gap-2.5 h-12 px-7 rounded-lg bg-[#1D4ED8] text-white text-[15px] font-medium hover:bg-blue-800 shadow-subtle ui-interactive ui-focus"
                    >
                        <span>Masuk Panel</span>
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>

                    {{-- Opsi Android (Menuju Halaman Download APK) --}}
                    @if ($apkUrl)
                        <div class="inline-flex flex-col sm:flex-row items-center gap-3">
                            <a
                                href="{{ route('download') }}"
                                class="inline-flex items-center justify-center gap-2.5 h-12 px-6 rounded-lg bg-white text-slate-800 border border-slate-300 shadow-xs hover:bg-slate-50 hover:border-slate-400 dark:bg-slate-800 dark:text-slate-100 dark:border-slate-600 dark:hover:bg-slate-700 text-[15px] font-medium ui-interactive ui-focus"
                            >
                                <svg class="w-4.5 h-4.5 text-[#1D4ED8] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                <span>Unduh APK</span>
                            </a>
                            @if ($apkVersion)
                                <span class="text-xs text-ink-muted tabular-nums">Versi {{ $apkVersion }}</span>
                            @endif
                        </div>
                    @else
                        <div class="inline-flex items-center gap-2 h-12 px-5 rounded-lg bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 shadow-2xs" aria-disabled="true">
                            <svg class="w-4 h-4 text-slate-500 dark:text-slate-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                            </svg>
                            <span>Aplikasi Android segera hadir</span>
                        </div>
                    @endif
                </div>



                @if (! $apkUrl)
                    <p class="text-xs text-ink-muted mt-3">
                        Aplikasi sedang dalam tahap persiapan sebelum dibagikan kepada pengguna sekolah.
                    </p>
                @endif

                <div class="mt-8 pt-6 border-t border-hairline text-xs text-ink-muted">
                    <span>Sistem Informasi Terpadu · SMK Negeri 1 Bantul</span>
                </div>
            </div>
        </div>

    </div>
</section>
