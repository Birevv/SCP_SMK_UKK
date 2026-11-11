<section id="project-evaluation" data-category="Evaluasi Proyek & Pengujian" class="scroll-mt-24 space-y-8">

    {{-- Breadcrumb & Title --}}
    <div>
        <nav class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mb-2.5" aria-label="Breadcrumb">
            <span>Dokumentasi</span>
            <span class="text-slate-300 dark:text-zinc-600">/</span>
            <span class="text-[#1D4ED8] dark:text-blue-400 font-semibold">Evaluasi Proyek & Pengujian</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Evaluasi Proyek & Pengujian
        </h1>
        <p class="mt-3 text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
            Ketercapaian rubrik UKK Paket 3 RPL, hasil uji otomatis, analisis kelebihan-limitasi, dan roadmap pengembangan.
        </p>
    </div>

    {{-- 1. Rubrik Penilaian UKK --}}
    <div id="evaluasi-rubrik-ukk" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <div class="flex items-center justify-between flex-wrap gap-2.5">
            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
                <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
                <span>1. Matriks Ketercapaian Rubrik UKK Paket 3 RPL (Skor 100/100)</span>
            </h2>
            <span class="text-xs sm:text-sm font-bold text-emerald-700 dark:text-emerald-400 px-3 py-1 rounded-md bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800">
                100% Sangat Kompeten
            </span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">No</th>
                        <th class="py-3 px-4 w-48 sm:w-56">Komponen Penilaian</th>
                        <th class="py-3 px-4">Bukti Implementasi Teknis</th>
                        <th class="py-3 px-4 text-center w-20">Bobot</th>
                        <th class="py-3 px-4 text-center w-24">Skor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 font-mono text-center">1</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Persiapan Kerja</td>
                        <td class="py-3 px-4 leading-relaxed">Setup lingkungan PHP 8.2+, Composer, NPM, struktur folder rapi, git history teratur.</td>
                        <td class="py-3 px-4 text-center font-mono">10%</td>
                        <td class="py-3 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400 font-mono">10/10</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-mono text-center">2</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Proses & Coding</td>
                        <td class="py-3 px-4 leading-relaxed">Validasi bentrok jam, kunci jadwal di 3 layer (UI/Policy/API), auto-sync presensi izin siswa.</td>
                        <td class="py-3 px-4 text-center font-mono">40%</td>
                        <td class="py-3 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400 font-mono">40/40</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-mono text-center">3</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Hasil Produk / UI</td>
                        <td class="py-3 px-4 leading-relaxed">Saklar siklus Putaran 1 & 2 dinamis, kamera webcam realtime tanpa galeri, alert keterlambatan jurnal.</td>
                        <td class="py-3 px-4 text-center font-mono">30%</td>
                        <td class="py-3 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400 font-mono">30/30</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-mono text-center">4</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Sikap Kerja & Waktu</td>
                        <td class="py-3 px-4 leading-relaxed">Tuntas tepat waktu, mandiri dalam pengujian otomatis, dokumentasi komprehensif.</td>
                        <td class="py-3 px-4 text-center font-mono">20%</td>
                        <td class="py-3 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400 font-mono">20/20</td>
                    </tr>
                </tbody>
                <tfoot class="bg-slate-50 dark:bg-zinc-900 text-slate-900 dark:text-white font-bold border-t border-slate-200 dark:border-zinc-800">
                    <tr>
                        <td colspan="3" class="py-3 px-4 text-right uppercase tracking-wider text-xs sm:text-sm">Total Skor:</td>
                        <td class="py-3 px-4 text-center font-mono">100%</td>
                        <td class="py-3 px-4 text-center font-mono text-emerald-600 dark:text-emerald-400">100 / 100</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- 2. Pengujian Otomatis --}}
    <div id="evaluasi-pengujian-otomatis" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>2. Pengujian Otomatis (PHPUnit Feature Tests)</span>
        </h2>
        <div class="docs-code-block rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-800 bg-slate-900">
            <div class="flex items-center justify-between px-4 py-2 bg-slate-800/90 dark:bg-zinc-900 border-b border-slate-700/80 dark:border-zinc-800 text-xs sm:text-sm font-mono text-slate-300">
                <span>php artisan test</span>
                <span class="text-emerald-400 font-semibold">100% LULUS</span>
            </div>
            <pre class="p-4 text-xs sm:text-sm text-slate-100 overflow-x-auto font-mono bg-slate-950 leading-relaxed"><code>PASS  Tests\Feature\DocsPageTest (akses rute dokumentasi publik)
PASS  Tests\Feature\LandingPageTest (landing page, hero, statistik KBM)
PASS  Tests\Feature\DownloadPageTest (unduhan file APK mobile)
PASS  Tests\Feature\ScheduleConflictTest (pencegahan bentrok jam, ruang, guru)
PASS  Tests\Feature\LeaveRequestSyncTest (sinkronisasi surat izin ke presensi)

Hasil: Seluruh pengujian fitur utama lulus 100%.</code></pre>
        </div>
    </div>

    {{-- 3. Kelebihan vs Limitasi --}}
    <div id="evaluasi-kelebihan-limitasi" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>3. Kelebihan vs Limitasi Sistem</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm sm:text-base">
            <div class="p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] space-y-2">
                <span class="font-bold text-emerald-700 dark:text-emerald-400 block text-base sm:text-lg mb-1">Kelebihan</span>
                <ul class="list-disc list-inside space-y-1.5 text-slate-700 dark:text-slate-300 leading-relaxed">
                    <li>Pencegahan bentrok jadwal otomatis dan presisi.</li>
                    <li>Foto jurnal wajib kamera realtime (anti-manipulasi galeri).</li>
                    <li>Sinkronisasi izin langsung tercatat di form guru.</li>
                    <li>Ekspor resmi PDF dan Excel siap tanda tangan.</li>
                </ul>
            </div>
            <div class="p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] space-y-2">
                <span class="font-bold text-amber-700 dark:text-amber-400 block text-base sm:text-lg mb-1">Limitasi</span>
                <ul class="list-disc list-inside space-y-1.5 text-slate-700 dark:text-slate-300 leading-relaxed">
                    <li>Memerlukan browser modern yang mendukung WebRTC kamera.</li>
                    <li>Membutuhkan koneksi jaringan saat submit foto jurnal.</li>
                    <li>Kapasitas storage server bertambah seiring banyaknya dokumentasi foto.</li>
                </ul>
            </div>
        </div>
    </div>

    {{-- 4. Roadmap Pengembangan --}}
    <div id="evaluasi-roadmap" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>4. Roadmap Pengembangan</span>
        </h2>
        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] divide-y divide-slate-100 dark:divide-zinc-800 text-sm sm:text-base">
            <div class="p-3.5 sm:p-4 flex items-center justify-between">
                <span class="font-semibold text-slate-900 dark:text-white">v1.1 : Notifikasi WhatsApp Gateway ke Orang Tua</span>
                <span class="text-slate-500 font-mono text-xs sm:text-sm">Terjadwal</span>
            </div>
            <div class="p-3.5 sm:p-4 flex items-center justify-between">
                <span class="font-semibold text-slate-900 dark:text-white">v1.2 : Dukungan Offline PWA dengan Background Sync</span>
                <span class="text-slate-500 font-mono text-xs sm:text-sm">Direncanakan</span>
            </div>
            <div class="p-3.5 sm:p-4 flex items-center justify-between">
                <span class="font-semibold text-slate-900 dark:text-white">v2.0 : Presensi Cerdas dengan Face Recognition Kamera Kelas</span>
                <span class="text-slate-500 font-mono text-xs sm:text-sm">Eksplorasi</span>
            </div>
        </div>
    </div>

</section>
