<section id="preview-fitur" class="bg-paper border-t border-hairline py-20 sm:py-24 lg:py-28 relative">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        
        {{-- Section Header --}}
        <div class="max-w-3xl mb-12 lg:mb-16">
            <span class="text-xs font-semibold tracking-wider text-[#1D4ED8] dark:text-blue-400 mb-3 block">
                ANTARMUKA APLIKASI
            </span>
            <h2 class="text-2xl sm:text-3xl lg:text-[38px] font-semibold text-ink leading-tight tracking-tight">
                Semua Aktivitas Akademik Lebih Terorganisir.
            </h2>
            <p class="text-[16px] text-ink-secondary leading-relaxed mt-4">
                Tampilan antarmuka operasional yang dirancang untuk kecepatan entri data, validasi otomatis pencegah bentrok, dan rekonsiliasi presensi kelas.
            </p>
        </div>

        {{-- SHOWCASE UTAMA (LEBAR DOMINAN): Tabel Jadwal Pelajaran Blok Filament --}}
        <div class="rounded-2xl bg-card border border-hairline-strong shadow-sheet overflow-hidden mb-8">
            {{-- Header Toolbar Tabel --}}
            <div class="p-5 sm:p-6 bg-card border-b border-hairline flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#1D4ED8] dark:text-blue-400">Jadwal Pelajaran Operasional</span>
                    </div>
                    <h3 class="text-lg font-semibold text-ink mt-1">Matriks Jadwal Mingguan Sesuai Putaran Aktif</h3>
                </div>
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800">
                        <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        <span>0% Bentrok (Ruangan, Rombel, Guru)</span>
                    </span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">
                        Putaran 1 Aktif
                    </span>
                </div>
            </div>

            {{-- Tabel Filament Jadwal --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-paper/70 border-b border-hairline text-ink-muted text-xs">
                            <th class="py-3 px-5 font-semibold">Hari</th>
                            <th class="py-3 px-4 font-semibold">Jam Pelajaran</th>
                            <th class="py-3 px-4 font-semibold">Kelas</th>
                            <th class="py-3 px-4 font-semibold">Ruangan</th>
                            <th class="py-3 px-5 font-semibold">Mata Pelajaran</th>
                            <th class="py-3 px-5 font-semibold text-right">Siklus</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline text-ink text-xs sm:text-sm">
                        <tr class="hover:bg-raised/30 transition-colors">
                            <td class="py-4 px-5 font-semibold text-[#1D4ED8] dark:text-blue-400">Senin</td>
                            <td class="py-4 px-4 tabular-nums text-ink-secondary">07.00 – 08.30 WIB</td>
                            <td class="py-4 px-4 font-medium text-ink">X RPL 1</td>
                            <td class="py-4 px-4 text-ink-secondary">Lab RPL 1</td>
                            <td class="py-4 px-5 font-medium">Dasar-Dasar Kejuruan RPL</td>
                            <td class="py-4 px-5 text-right">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">Putaran 1</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-raised/30 transition-colors">
                            <td class="py-4 px-5 font-semibold text-[#1D4ED8] dark:text-blue-400">Senin</td>
                            <td class="py-4 px-4 tabular-nums text-ink-secondary">08.30 – 10.00 WIB</td>
                            <td class="py-4 px-4 font-medium text-ink">XI RPL 2</td>
                            <td class="py-4 px-4 text-ink-secondary">Lab RPL 2</td>
                            <td class="py-4 px-5 font-medium">Pemrograman Berorientasi Objek</td>
                            <td class="py-4 px-5 text-right">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">Putaran 1</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-raised/30 transition-colors">
                            <td class="py-4 px-5 font-semibold text-[#1D4ED8] dark:text-blue-400">Selasa</td>
                            <td class="py-4 px-4 tabular-nums text-ink-secondary">08.30 – 11.30 WIB</td>
                            <td class="py-4 px-4 font-medium text-ink">XII RPL 1</td>
                            <td class="py-4 px-4 text-ink-secondary">Lab RPL 3</td>
                            <td class="py-4 px-5 font-medium">Pemrograman Web dan Perangkat Bergerak</td>
                            <td class="py-4 px-5 text-right">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">Putaran 1</span>
                            </td>
                        </tr>
                        <tr class="hover:bg-raised/30 transition-colors">
                            <td class="py-4 px-5 font-semibold text-[#1D4ED8] dark:text-blue-400">Rabu</td>
                            <td class="py-4 px-4 tabular-nums text-ink-secondary">07.00 – 10.00 WIB</td>
                            <td class="py-4 px-4 font-medium text-ink">XI RPL 1</td>
                            <td class="py-4 px-4 text-ink-secondary">Lab RPL 1</td>
                            <td class="py-4 px-5 font-medium">Basis Data & Relasional Database</td>
                            <td class="py-4 px-5 text-right">
                                <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">Putaran 1</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Footer Toolbar Tabel --}}
            <div class="p-4 px-5 bg-paper/50 border-t border-hairline flex flex-wrap items-center justify-between gap-3 text-xs text-ink-muted">
                <span>Filter aktif: Putaran 1 · Ditampilkan 4 dari 28 jadwal mingguan</span>
                <span>Otomatis memfilter form jurnal guru saat input tatap muka</span>
            </div>
        </div>

        {{-- BAGIAN PENDAMPING: 2 Kolom Asimetris (Jurnal Mengajar & Presensi / Rekapitulasi) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            {{-- KIRI: Preview Jurnal Mengajar & Bukti Foto Kelas (Col 6) --}}
            <div class="lg:col-span-6 rounded-2xl bg-card border border-hairline-strong shadow-subtle p-6 sm:p-7">
                <div class="flex items-center justify-between pb-4 mb-5 border-b border-hairline">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#1D4ED8] dark:text-blue-400">Modul Jurnal Guru</span>
                        <h3 class="text-lg font-semibold text-ink mt-0.5">Entri Jurnal & Dokumentasi Foto</h3>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-amber-50 text-amber-800 border border-amber-300 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800">
                        Foto Wajib Diunggah
                    </span>
                </div>

                <div class="space-y-4 text-sm">
                    <div class="p-4 rounded-xl bg-paper border border-hairline">
                        <span class="text-xs font-semibold text-ink-muted uppercase tracking-wider">Materi Pokok Pembelajaran</span>
                        <p class="text-sm font-medium text-ink leading-relaxed mt-1">
                            Penerapan konsep enkapsulasi, accessor, dan mutator pada pembuatan entitas peminjaman buku perpustakaan.
                        </p>
                    </div>

                    <div class="p-4 rounded-xl bg-paper border border-hairline flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-950/40 border border-blue-200/60 dark:border-blue-900/40 flex items-center justify-center shrink-0 text-[#1D4ED8] dark:text-blue-400">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-ink truncate">Dokumentasi_Kelas_XI_RPL2.jpg</p>
                                <p class="text-xs text-ink-muted mt-0.5">Foto kegiatan belajar di lab · Terverifikasi sistem</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 shrink-0">Tersimpan</span>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-hairline flex items-center justify-between text-xs text-ink-muted">
                    <span>Pengingat keterlambatan: Muncul jika lewat >30 menit</span>
                    <span class="font-medium text-ink-secondary">Validasi Waka</span>
                </div>
            </div>

            {{-- KANAN: Preview Presensi Siswa & Laporan Rekapitulasi Siap Cetak (Col 6) --}}
            <div class="lg:col-span-6 rounded-2xl bg-card border border-hairline-strong shadow-subtle p-6 sm:p-7">
                <div class="flex items-center justify-between pb-4 mb-5 border-b border-hairline">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-[#1D4ED8] dark:text-blue-400">Modul Presensi & Rekap</span>
                        <h3 class="text-lg font-semibold text-ink mt-0.5">Penanda Buku Absen & Format Cetak A4</h3>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-blue-50 text-[#1D4ED8] border border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800">
                        Sinkron Izin Aktif
                    </span>
                </div>

                {{-- Tabel Presensi Siswa --}}
                <div class="space-y-2 mb-5">
                    <div class="py-2.5 px-3.5 rounded-lg bg-paper border border-hairline flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-ink-muted tabular-nums w-4">01</span>
                            <span class="font-medium text-ink truncate">Siswa 01</span>
                        </div>
                        <x-attendance-code active="H" class="shrink-0" />
                    </div>

                    <div class="py-2.5 px-3.5 rounded-lg bg-paper border border-hairline flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-ink-muted tabular-nums w-4">02</span>
                            <span class="font-medium text-ink truncate">Siswa 02</span>
                        </div>
                        <x-attendance-code active="H" class="shrink-0" />
                    </div>

                    <div class="py-2.5 px-3.5 rounded-lg bg-paper border border-hairline flex items-center justify-between gap-3 text-xs">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="text-ink-muted tabular-nums w-4">03</span>
                            <div class="min-w-0">
                                <span class="font-medium text-ink truncate block">Siswa 03</span>
                                <span class="text-[10px] text-amber-700 dark:text-amber-400 font-medium block">Surat dokter terverifikasi otomatis</span>
                            </div>
                        </div>
                        <x-attendance-code active="S" class="shrink-0" />
                    </div>
                </div>

                {{-- Rekapitulasi Persentase & Cetak A4 --}}
                <div class="p-4 rounded-xl bg-paper border border-hairline">
                    <div class="grid grid-cols-4 gap-2 mb-3 text-center text-xs">
                        <div>
                            <span class="text-ink-muted block text-[11px]">Hadir</span>
                            <span class="font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">94.4%</span>
                        </div>
                        <div>
                            <span class="text-ink-muted block text-[11px]">Sakit</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400 tabular-nums">2.8%</span>
                        </div>
                        <div>
                            <span class="text-ink-muted block text-[11px]">Izin</span>
                            <span class="font-bold text-amber-600 dark:text-amber-400 tabular-nums">2.8%</span>
                        </div>
                        <div>
                            <span class="text-ink-muted block text-[11px]">Alpa</span>
                            <span class="font-bold text-red-600 dark:text-red-400 tabular-nums">0%</span>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-hairline flex items-center justify-between text-xs">
                        <span class="text-ink-muted">Tingkat Kehadiran: <strong class="text-ink font-semibold">97.2%</strong></span>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-white text-[#1D4ED8] border border-blue-200 shadow-2xs font-semibold text-xs dark:bg-slate-800 dark:text-blue-300 dark:border-blue-900">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24-1.056-.37-2.16-.37-3.294 0-5.523 4.477-10 10-10 .768 0 1.512.086 2.228.25M17.25 18a4.5 4.5 0 0 1-4.5 4.5H6.75A4.5 4.5 0 0 1 2.25 18c0-1.874 1.15-3.48 2.78-4.148M12 9v9m0 0 3-3m-3 3-3-3" />
                            </svg>
                            <span>Siap Cetak Format A4</span>
                        </span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-hairline flex items-center justify-between text-xs text-ink-muted">
                    <span>Laporan rekapitulasi presensi bulanan</span>
                    <span class="font-medium text-ink-secondary">Wali Kelas & Waka</span>
                </div>
            </div>

        </div>
    </div>
</section>
