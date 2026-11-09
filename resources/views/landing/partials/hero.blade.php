@php
    $apkUrl = $apkUrl ?? null;
    $apkVersion = $apkVersion ?? null;
    $apkSha256 = $apkSha256 ?? null;
@endphp
<section id="hero" class="relative overflow-hidden py-14 sm:py-20 lg:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-14 items-center">
            
            {{-- Sisi Kiri: Editorial Copy & Call-To-Action (55% Kolom) --}}
            <div class="lg:col-span-6 xl:col-span-6 flex flex-col items-start">
                
                {{-- Label Kecil --}}
                <div class="inline-flex items-center text-xs font-semibold tracking-wider text-[#1D4ED8] dark:text-blue-400 enter-1">
                    <span>SISTEM INFORMASI AKADEMIK</span>
                </div>

                {{-- Headline Utama: Wrapping Disengaja & Proporsional --}}
                <h1 class="text-3xl sm:text-4xl lg:text-[46px] xl:text-[50px] font-bold text-ink leading-[1.12] tracking-tight mt-4 enter-1">
                    Seluruh Aktivitas Akademik, Dalam Satu <span class="text-[#1D4ED8] dark:text-blue-400">Alur Terintegrasi</span>
                </h1>

                {{-- Deskripsi Ringkas & Terarah --}}
                <p class="text-[16px] sm:text-[17px] text-ink-secondary leading-relaxed mt-4 max-w-136 enter-2">
                    School Class Progress membantu SMK Negeri 1 Bantul mengelola jadwal pelajaran, jurnal mengajar, presensi, dan perizinan siswa melalui satu sistem yang terintegrasi.
                </p>

                {{-- Konteks Penggunaan Tambahan (Memenuhi Kebutuhan Spesifikasi & Uji) --}}
                <p class="text-xs text-ink-muted leading-relaxed mt-2 max-w-lg enter-2">
                    Kelola jurnal mengajar, presensi, dan perizinan siswa dalam satu sistem sekolah. School Class Progress membantu sekolah mengelola jadwal blok, jurnal mengajar, kehadiran siswa, serta pengajuan izin secara terintegrasi melalui panel web dan aplikasi Android.
                </p>

                {{-- CTA Group (Hanya Tombol Download APK) --}}
                <div class="mt-8 flex items-center enter-3">
                    @if ($apkUrl)
                        <a
                            href="{{ $apkUrl }}"
                            rel="noopener"
                            download
                            class="inline-flex items-center gap-3 h-13 px-6 rounded-xl bg-slate-900 hover:bg-slate-800 text-white border border-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 dark:border-white shadow-subtle ui-interactive ui-focus group transition-all"
                        >
                            <div class="w-8 h-8 rounded-lg bg-slate-800 text-amber-400 dark:bg-amber-500/15 dark:text-amber-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                                </svg>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[15px] font-bold text-white dark:text-slate-900">Download APK</span>
                                @if ($apkVersion)
                                    <span class="text-xs text-slate-400 dark:text-slate-500 tabular-nums">v{{ $apkVersion }}</span>
                                @endif
                            </div>
                        </a>
                    @else
                        <a
                            href="{{ route('download') }}"
                            class="inline-flex items-center gap-3 h-13 px-6 rounded-xl bg-slate-900 hover:bg-slate-800 text-white border border-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100 dark:border-white shadow-subtle ui-interactive ui-focus group transition-all"
                            aria-disabled="true"
                            title="Aplikasi Android segera hadir — Buka Halaman Unduh"
                        >
                            <div class="w-8 h-8 rounded-lg bg-slate-800 text-amber-400 dark:bg-amber-500/15 dark:text-amber-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 3v12m0 0-4-4m4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
                                </svg>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <span class="text-[15px] font-bold text-white dark:text-slate-900">Download APK</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-400/20 text-amber-300 border border-amber-400/30 dark:bg-amber-100 dark:text-amber-800 dark:border-amber-300">
                                    Segera Hadir
                                </span>
                            </div>
                        </a>
                    @endif
                </div>

                {{-- Status Ketersediaan Platform (Mirip AQU: Tersedia di Android) --}}
                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-ink-muted enter-3">
                    <span class="text-ink-muted font-normal">Tersedia di</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-ink-secondary text-xs font-medium shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M17.523 15.3414c-.5511 0-.9993-.4486-.9993-1.0003 0-.5517.4482-1.0004.9993-1.0004.5516 0 .9997.4487.9997 1.0004 0 .5517-.4481 1.0003-.9997 1.0003m-11.046 0c-.5511 0-.9993-.4486-.9993-1.0003 0-.5517.4482-1.0004.9993-1.0004.5516 0 .9997.4487.9997 1.0004 0 .5517-.4481 1.0003-.9997 1.0003m11.4045-6.02l1.9973-3.4592a.416.416 0 0 0-.1521-.5676.416.416 0 0 0-.5676.1521l-2.0223 3.503C15.5802 8.414 13.8427 8.125 12 8.125c-1.8427 0-3.5802.289-5.1368.8247L4.8409 5.4467a.4161.4161 0 0 0-.5677-.1521.4157.4157 0 0 0-.1521.5676l1.9973 3.4592C2.6889 11.1867.3432 14.6589 0 18.761h24c-.3432-4.1021-2.6889-7.5743-6.1185-9.4396" />
                        </svg>
                        <span>Android</span>
                    </span>
                    @if (!$apkUrl)
                        <span class="text-ink-muted" aria-hidden="true">·</span>
                        <span class="text-xs text-ink-muted">Aplikasi sedang dalam tahap persiapan sebelum dibagikan kepada pengguna sekolah.</span>
                    @elseif ($apkVersion)
                        <span class="text-ink-muted" aria-hidden="true">·</span>
                        <span class="text-xs text-ink-muted tabular-nums">Versi {{ $apkVersion }}</span>
                    @endif
                </div>

                {{-- Status Tambahan --}}
                <div class="mt-8 pt-4 border-t border-hairline w-full flex items-center gap-2 text-xs text-ink-muted enter-3">
                    <span>Untuk SMK Negeri 1 Bantul</span>
                    <span class="text-ink-muted" aria-hidden="true">·</span>
                    <span>Tahun Ajaran 2026/2027</span>
                </div>
            </div>

            {{-- Sisi Kanan: Satu Visual Showcase SCP Dominan (Matriks Jurnal & Presensi) --}}
            <div class="lg:col-span-6 xl:col-span-6 enter-2">
                <div class="rounded-2xl bg-card border border-hairline-strong shadow-sheet overflow-hidden" aria-label="Ilustrasi tampilan sistem">
                    
                    {{-- Browser Window Top Bar --}}
                    <div class="px-4 py-3 bg-paper/80 border-b border-hairline flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                            <span class="w-2.5 h-2.5 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                            <span class="text-xs font-medium text-ink-muted ml-2">Ilustrasi tampilan sistem</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">
                                <span>Putaran 1</span>
                            </span>
                        </div>
                    </div>

                    {{-- Window Body: Matriks Dashboard Realistis --}}
                    <div class="p-5 sm:p-6 space-y-4">
                        
                        {{-- Header Sesi Pembelajaran Aktif --}}
                        <div class="pb-3.5 border-b border-hairline flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="text-xs text-ink-muted tabular-nums">Jadwal Mengajar Hari Ini · Senin, 9 November 2026</span>
                                <h2 class="text-lg font-bold text-ink leading-snug mt-0.5">Pemrograman Berorientasi Objek</h2>
                                <p class="text-xs text-ink-secondary tabular-nums mt-0.5">Kelas XI RPL 2 · Lab RPL 1 · Jam ke-3–4 (08.30–10.00)</p>
                            </div>
                            <span class="text-xs font-semibold px-2.5 py-0.5 rounded-md bg-emerald-50 text-emerald-800 border border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800">
                                Terverifikasi
                            </span>
                        </div>

                        {{-- Ringkasan Jurnal & Bukti Foto Kelas --}}
                        <div class="p-3.5 rounded-xl bg-paper/90 border border-hairline grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                            <div class="sm:col-span-8">
                                <span class="text-[11px] font-medium text-ink-muted uppercase tracking-wider">Capaian Materi Tatap Muka</span>
                                <p class="text-[13px] font-medium text-ink leading-snug mt-0.5">Enkapsulasi dan pewarisan class pada modul peminjaman perpustakaan</p>
                            </div>
                            <div class="sm:col-span-4 flex items-center sm:justify-end gap-2 pt-2 sm:pt-0 sm:pl-3 border-t sm:border-t-0 sm:border-l border-hairline">
                                <div class="w-7 h-7 rounded-md bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/40 flex items-center justify-center shrink-0 text-[#1D4ED8] dark:text-blue-400">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />
                                    </svg>
                                </div>
                                <div class="text-[11px]">
                                    <p class="font-medium text-ink leading-none">Foto Kelas</p>
                                    <p class="text-ink-muted leading-none mt-1">Dokumentasi KBM</p>
                                </div>
                            </div>
                        </div>

                        {{-- Preview Presensi Siswa (H · S · I · A) --}}
                        <div>
                            <div class="flex items-center justify-between text-xs font-medium text-ink-muted pb-2 border-b border-hairline">
                                <span>Presensi Kelas (6 dari 32 Siswa)</span>
                                <span>Status Kehadiran</span>
                            </div>

                            <div class="divide-y divide-hairline text-xs">
                                <div class="py-2 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-ink-muted tabular-nums w-4">01</span>
                                        <span class="font-medium text-ink truncate">Siswa 01</span>
                                    </div>
                                    <x-attendance-code active="H" class="shrink-0" />
                                </div>

                                <div class="py-2 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-ink-muted tabular-nums w-4">02</span>
                                        <span class="font-medium text-ink truncate">Siswa 02</span>
                                    </div>
                                    <x-attendance-code active="H" class="shrink-0" />
                                </div>

                                <div class="py-2 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-ink-muted tabular-nums w-4">03</span>
                                        <div class="min-w-0">
                                            <span class="font-medium text-ink truncate block">Siswa 03</span>
                                            <span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium block">Tersinkron dari surat izin terverifikasi</span>
                                        </div>
                                    </div>
                                    <x-attendance-code active="I" class="shrink-0" />
                                </div>

                                <div class="py-2 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="text-ink-muted tabular-nums w-4">04</span>
                                        <span class="font-medium text-ink truncate">Siswa 04</span>
                                    </div>
                                    <x-attendance-code active="H" class="shrink-0" />
                                </div>
                            </div>
                        </div>

                        {{-- Summary Bar --}}
                        <div class="pt-3 border-t border-hairline flex items-center justify-between text-xs text-ink-secondary tabular-nums">
                            <span class="font-medium">Rekap Sesi: Hadir 31 · Sakit 0 · Izin 1 · Alpa 0</span>
                            <span class="text-ink-muted">Guru Mata Pelajaran</span>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
