<section id="system-modules" data-category="Modul & Fitur Teknis" class="scroll-mt-24 space-y-8">

    {{-- Breadcrumb & Title --}}
    <div>
        <nav class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mb-2.5" aria-label="Breadcrumb">
            <span>Dokumentasi</span>
            <span class="text-slate-300 dark:text-zinc-600">/</span>
            <span class="text-[#1D4ED8] dark:text-blue-400 font-semibold">Modul & Fitur Teknis</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Modul & Fitur Teknis Sistem
        </h1>
        <p class="mt-3 text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
            Spesifikasi implementasi modul kamera realtime, kalender akademik, ekspor berkas, kenaikan kelas, dan import FET.
        </p>
    </div>

    {{-- Fitur 1: Kamera Realtime --}}
    <div id="fitur-kamera" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>1. Kamera Realtime Webcam (Autentikasi Foto KBM)</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-3 px-4">Tahap</th>
                        <th class="py-3 px-4">Teknologi</th>
                        <th class="py-3 px-4">Perilaku</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Stream Video</td>
                        <td class="py-3 px-4 font-mono text-[#1D4ED8] dark:text-blue-400 font-medium">navigator.mediaDevices.getUserMedia()</td>
                        <td class="py-3 px-4">Mengakses sensor kamera hp atau webcam perangkat laptop secara realtime.</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Capture Frame</td>
                        <td class="py-3 px-4 font-mono text-[#1D4ED8] dark:text-blue-400 font-medium">HTML5 &lt;canvas&gt;</td>
                        <td class="py-3 px-4">Menangkap frame gambar seketika, mengompresi ke JPEG dengan kompresi 0.85.</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Upload Asinkron</td>
                        <td class="py-3 px-4 font-mono text-[#1D4ED8] dark:text-blue-400 font-medium">POST /upload-journal-photo</td>
                        <td class="py-3 px-4">Mengunggah blob ke storage disk tanpa me-refresh form Filament.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Fitur 2: Kalender Akademik --}}
    <div id="fitur-kaldik" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>2. Kalender Akademik & Agenda Kegiatan</span>
        </h2>
        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] p-4 sm:p-5 text-sm sm:text-base space-y-1.5">
            <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                Resource <strong class="text-slate-900 dark:text-white">AcademicEvent</strong> mencatat hari libur nasional, jeda tengah semester, dan pekan ujian. Jadwal KBM harian otomatis menyaring hari non-efektif.
            </p>
        </div>
    </div>

    {{-- Fitur 3: Ekspor & Cetak Laporan --}}
    <div id="fitur-laporan" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>3. Cetak PDF & Ekspor Lembar Kerja Excel (.xlsx)</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm sm:text-base">
            <div class="p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] space-y-2">
                <div class="flex items-center justify-between font-bold text-slate-900 dark:text-white">
                    <span class="text-base sm:text-lg">Cetak PDF Laporan Presensi</span>
                    <span class="font-mono text-xs sm:text-sm text-[#1D4ED8] dark:text-blue-400">GET /attendance-report/print</span>
                </div>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">Dokumen formal kop SMK Negeri 1 Bantul, persentase kehadiran per siswa, dan tanda tangan digital Kepala Sekolah/BK.</p>
            </div>
            <div class="p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] space-y-2">
                <div class="flex items-center justify-between font-bold text-slate-900 dark:text-white">
                    <span class="text-base sm:text-lg">Ekspor Spreadsheet Excel (.xlsx)</span>
                    <span class="font-mono text-xs sm:text-sm text-emerald-600 dark:text-emerald-400">GET /attendance-report/export-excel</span>
                </div>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">File raw data kehadiran untuk pengolahan nilai rapor semesteran dan arsip kesiswaan.</p>
            </div>
        </div>
    </div>

    {{-- Fitur 4: Kenaikan Kelas --}}
    <div id="fitur-kenaikan-kelas" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>4. Manajemen Kenaikan Kelas (Student Promotion)</span>
        </h2>
        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] p-4 sm:p-5 text-sm sm:text-base space-y-2">
            <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                Fitur di halaman <strong class="text-slate-900 dark:text-white">StudentPromotion</strong> memindahkan rombel siswa secara massal (contoh: seluruh kelas X RPL 1 naik ke XI RPL 1) dalam satu transaksi basis data aman (<code class="text-[#1D4ED8] dark:text-blue-400 font-mono font-medium">DB::transaction</code>).
            </p>
        </div>
    </div>

    {{-- Fitur 5: Import Jadwal Cerdas --}}
    <div id="fitur-import-jadwal" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>5. Import Jadwal Cerdas (FET & PDF Parsing)</span>
        </h2>
        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] p-4 sm:p-5 text-sm sm:text-base space-y-2">
            <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                Mendukung upload berkas hasil generator jadwal <strong class="text-slate-900 dark:text-white">FET (Free Timetabling Software)</strong> atau dokumen CSV/PDF untuk otomatis dipetakan ke tabel schedules tanpa input satu per satu.
            </p>
        </div>
    </div>

</section>
