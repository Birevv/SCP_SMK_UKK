<section id="aplikasi" class="border-t border-hairline py-14 sm:py-16 lg:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 sm:gap-10 lg:gap-12 items-center">
            <div class="lg:col-span-6">
                <p class="text-xs text-ink-muted">Aplikasi perangkat bergerak</p>
                <h2 class="section-title text-ink mt-2">
                    Aplikasi Android untuk operasional kelas dan pemantauan kehadiran
                </h2>
                <p class="text-[15px] text-ink-secondary leading-6 mt-4">
                    Aplikasi Android terintegrasi dengan sistem utama sehingga siswa, orang tua, guru, wali kelas, dan guru BK dapat menjalankan aktivitas harian melalui perangkat bergerak sesuai hak akses masing-masing.
                </p>

                <ul class="mt-6 space-y-3">
                    <li class="flex items-start gap-2.5 text-[14px] text-ink-secondary leading-6">
                        <svg class="w-4.5 h-4.5 text-ink-muted shrink-0 mt-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        <span>Jadwal pelajaran harian sesuai putaran pembelajaran.</span>
                    </li>
                    <li class="flex items-start gap-2.5 text-[14px] text-ink-secondary leading-6">
                        <svg class="w-4.5 h-4.5 text-ink-muted shrink-0 mt-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                        </svg>
                        <span>Ringkasan dan riwayat kehadiran bulanan.</span>
                    </li>
                    <li class="flex items-start gap-2.5 text-[14px] text-ink-secondary leading-6">
                        <svg class="w-4.5 h-4.5 text-ink-muted shrink-0 mt-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                        <span>Pengajuan surat izin atau sakit.</span>
                    </li>
                    <li class="flex items-start gap-2.5 text-[14px] text-ink-secondary leading-6">
                        <svg class="w-4.5 h-4.5 text-ink-muted shrink-0 mt-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        <span>Pengisian jurnal dan presensi oleh guru.</span>
                    </li>
                    <li class="flex items-start gap-2.5 text-[14px] text-ink-secondary leading-6">
                        <svg class="w-4.5 h-4.5 text-ink-muted shrink-0 mt-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span>Verifikasi perizinan sesuai kewenangan.</span>
                    </li>
                </ul>

                <div class="mt-8">
                    @if ($apkUrl)
                        <div class="flex flex-wrap items-center gap-3">
                            <a
                                href="{{ $apkUrl }}"
                                rel="noopener"
                                class="inline-flex items-center justify-center gap-2.5 h-12 px-5 rounded-[10px] bg-accent text-on-accent text-[15px] font-medium hover:bg-accent-hover ui-interactive ui-focus"
                            >
                                <svg class="w-4.5 h-4.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                <span>Unduh APK</span>
                            </a>
                            @if ($apkVersion)
                                <span class="text-xs text-ink-secondary tabular-nums">Versi {{ $apkVersion }}</span>
                            @endif
                        </div>
                        @if ($apkSha256)
                            <p class="text-xs text-ink-muted tabular-nums mt-3 break-all">SHA-256: {{ $apkSha256 }}</p>
                        @endif
                    @else
                        <div class="rounded-xl bg-raised border border-hairline-strong p-4 max-w-md" aria-disabled="true">
                            <div class="flex items-center gap-2.5">
                                <svg class="w-4.5 h-4.5 text-ink-muted shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                </svg>
                                <p class="text-[14px] font-medium text-ink">Aplikasi Android segera hadir</p>
                            </div>
                            <p class="text-xs text-ink-secondary leading-5 mt-1.5 pl-7">
                                Aplikasi sedang dalam tahap persiapan sebelum dibagikan kepada pengguna sekolah.
                            </p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="lg:col-span-6 flex justify-center lg:justify-end">
                <div class="w-full max-w-[24rem] rounded-[20px] bg-card border border-hairline-strong shadow-sheet p-4 sm:p-5" aria-label="Ilustrasi tampilan aplikasi mobile">
                    <div class="flex items-center justify-between pb-3 border-b border-hairline text-xs text-ink-muted">
                        <span>Ilustrasi tampilan sistem</span>
                        <span>SCP Mobile</span>
                    </div>

                    <div class="mt-4 flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[15px] font-medium text-ink">Siswa 01</p>
                            <p class="text-xs text-ink-muted mt-0.5">Kelas XI RPL 2 · Senin, 9 November 2026</p>
                        </div>
                        <span class="inline-flex items-center h-6 px-2 rounded border border-accent-border bg-accent-tint text-xs font-medium text-accent-ink shrink-0">
                            Putaran 1
                        </span>
                    </div>

                    <div class="mt-4 p-3 rounded-lg bg-paper border border-hairline">
                        <div class="flex items-center justify-between text-xs text-ink-muted tabular-nums">
                            <span>Ringkasan kehadiran bulan ini</span>
                            <span>Status (H · S · I · A)</span>
                        </div>
                        <div class="mt-2.5 flex items-center justify-between gap-2">
                            <x-attendance-code code="H" count="18" />
                            <x-attendance-code code="S" count="0" />
                            <x-attendance-code code="I" count="1" />
                            <x-attendance-code code="A" count="0" />
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-xs text-ink-muted">Jadwal pelajaran · Senin</p>
                        <ul class="mt-2 divide-y divide-hairline border-t border-b border-hairline">
                            <li class="py-2.5 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[13px] font-medium text-ink truncate">Matematika Kejuruan</p>
                                    <p class="text-xs text-ink-muted">Ruang Teori 4 · Jam ke-1–2</p>
                                </div>
                                <span class="text-xs text-ink-secondary tabular-nums shrink-0">07.00–08.30</span>
                            </li>
                            <li class="py-2.5 flex items-center justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="text-[13px] font-medium text-ink truncate">Pemrograman Berorientasi Objek</p>
                                    <p class="text-xs text-ink-muted">Lab RPL 1 · Jam ke-3–4 · Guru Mata Pelajaran</p>
                                </div>
                                <span class="text-xs text-accent-ink font-medium tabular-nums shrink-0">08.30–10.00</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-4 p-3 rounded-[10px] bg-paper border border-hairline flex items-center justify-between text-xs text-ink-secondary">
                        <span>Menu pengajuan surat izin atau sakit</span>
                        <span class="text-ink-muted">Tersedia di aplikasi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
