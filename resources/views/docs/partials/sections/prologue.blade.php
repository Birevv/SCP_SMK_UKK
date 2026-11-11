<section id="prologue" data-category="Prologue" class="scroll-mt-24 space-y-8">

    {{-- Breadcrumb & Title --}}
    <div>
        <nav class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mb-2.5" aria-label="Breadcrumb">
            <span>Dokumentasi</span>
            <span class="text-slate-300 dark:text-zinc-600">/</span>
            <span class="text-[#1D4ED8] dark:text-blue-400 font-semibold">Prologue</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Prologue
        </h1>
        <p class="mt-3 text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
            Ringkasan sistem, spesifikasi UKK Paket 3, arsitektur teknologi, dan akun akses pengujian <strong class="text-slate-900 dark:text-white">School Class Progress (SCP)</strong> SMK Negeri 1 Bantul.
        </p>
    </div>

    {{-- Sub 1: Tentang SCP SMK --}}
    <div id="tentang-scp" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Tentang SCP SMK</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="p-4 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] space-y-1.5">
                <span class="text-xs font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-wider block">Fokus Utama</span>
                <strong class="text-base text-slate-900 dark:text-white block">Kurikulum Blok & TEFA</strong>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">Pengelolaan jadwal KBM dua mingguan (Putaran 1 & Putaran 2) tanpa risiko bentrok.</p>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] space-y-1.5">
                <span class="text-xs font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-wider block">Autentikasi KBM</span>
                <strong class="text-base text-slate-900 dark:text-white block">Jurnal + Kamera Realtime</strong>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">Pencatatan materi ajar dengan bukti foto langsung dari webcam/kamera hp saat jam pelajaran.</p>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] space-y-1.5">
                <span class="text-xs font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-wider block">Otomasi Presensi</span>
                <strong class="text-base text-slate-900 dark:text-white block">Sinkronisasi Surat Izin</strong>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed">Surat izin siswa yang disetujui wali kelas/BK langsung tercatat otomatis di jurnal guru.</p>
            </div>
        </div>
    </div>

    {{-- Sub 2: Kualifikasi UKK 2026 --}}
    <div id="kualifikasi-ukk" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Kualifikasi UKK Paket 3 RPL (Tahun Ajaran 2026/2027)</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base">
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-zinc-900 w-52 sm:w-60">Satuan Pendidikan</td>
                        <td class="py-3 px-4">SMK Negeri 1 Bantul (Jurusan Rekayasa Perangkat Lunak)</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-zinc-900">Kode & Paket Soal</td>
                        <td class="py-3 px-4">Paket 3 RPL (Sistem Informasi Manajemen Jadwal, Jurnal, dan Presensi Siswa)</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-zinc-900">5 Logika Wajib</td>
                        <td class="py-3 px-4 font-medium text-slate-900 dark:text-white">
                            1. Validasi Bentrok Jadwal &bull; 2. Saklar Putaran 1/2 &bull; 3. Lock Schedule Waka &bull; 4. Auto-sync Izin Siswa &bull; 5. Peringatan Jurnal Terlambat
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sub 3: Arsitektur & Tech Stack --}}
    <div id="arsitektur-sistem" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Arsitektur & Tech Stack</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-3 px-4">Layer</th>
                        <th class="py-3 px-4">Teknologi</th>
                        <th class="py-3 px-4">Peran dalam Sistem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Backend</td>
                        <td class="py-3 px-4 font-mono text-slate-800 dark:text-slate-200">Laravel 12 (PHP 8.2+)</td>
                        <td class="py-3 px-4">Routing, Eloquent ORM, Gate Policy, Middleware Keamanan</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Admin Panel</td>
                        <td class="py-3 px-4 font-mono text-slate-800 dark:text-slate-200">Filament v4 (TALL Stack)</td>
                        <td class="py-3 px-4">CRUD tabel, dashboard widgets, modal form, filter data</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Database</td>
                        <td class="py-3 px-4 font-mono text-slate-800 dark:text-slate-200">MySQL 8.0 / SQLite</td>
                        <td class="py-3 px-4">Penyimpanan relasional berintegritas tinggi dengan foreign keys</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Styling & Asset</td>
                        <td class="py-3 px-4 font-mono text-slate-800 dark:text-slate-200">Tailwind CSS v4 + Vite</td>
                        <td class="py-3 px-4">Desain ringan, utilitas modern, kompilasi cepat</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">API & Auth</td>
                        <td class="py-3 px-4 font-mono text-slate-800 dark:text-slate-200">Laravel Sanctum</td>
                        <td class="py-3 px-4">Bearer token stateless untuk komunikasi klien mobile</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sub 4: Akun Akses Default --}}
    <div id="akun-akses" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Akun Akses Default (RBAC 5 Roles)</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-3 px-4">Role</th>
                        <th class="py-3 px-4">Username</th>
                        <th class="py-3 px-4">Password</th>
                        <th class="py-3 px-4">Wewenang Utama</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300 font-mono">
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">
                        <td class="py-3 px-4 font-sans font-semibold text-slate-900 dark:text-white">Waka Kurikulum</td>
                        <td class="py-3 px-4 font-medium text-[#1D4ED8] dark:text-blue-400">waka</td>
                        <td class="py-3 px-4 text-slate-500">password</td>
                        <td class="py-3 px-4 font-sans text-slate-700 dark:text-slate-300">Konfigurasi sistem, kunci jadwal, saklar putaran, rekap sekolah</td>
                    </tr>
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">
                        <td class="py-3 px-4 font-sans font-semibold text-slate-900 dark:text-white">Guru Mapel</td>
                        <td class="py-3 px-4 font-medium text-[#1D4ED8] dark:text-blue-400">guru1</td>
                        <td class="py-3 px-4 text-slate-500">password</td>
                        <td class="py-3 px-4 font-sans text-slate-700 dark:text-slate-300">Input jadwal mandiri, isi jurnal, foto webcam, absensi kelas</td>
                    </tr>
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">
                        <td class="py-3 px-4 font-sans font-semibold text-slate-900 dark:text-white">Wali Kelas</td>
                        <td class="py-3 px-4 font-medium text-[#1D4ED8] dark:text-blue-400">walikelas1</td>
                        <td class="py-3 px-4 text-slate-500">password</td>
                        <td class="py-3 px-4 font-sans text-slate-700 dark:text-slate-300">Verifikasi surat izin siswa, rekap presensi kelas binaan</td>
                    </tr>
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">
                        <td class="py-3 px-4 font-sans font-semibold text-slate-900 dark:text-white">Guru BK</td>
                        <td class="py-3 px-4 font-medium text-[#1D4ED8] dark:text-blue-400">gurubk</td>
                        <td class="py-3 px-4 text-slate-500">password</td>
                        <td class="py-3 px-4 font-sans text-slate-700 dark:text-slate-300">Persetujuan izin berjenjang, monitoring absensi lintas rombel</td>
                    </tr>
                    <tr class="hover:bg-slate-50/70 dark:hover:bg-zinc-800/40">
                        <td class="py-3 px-4 font-sans font-semibold text-slate-900 dark:text-white">Siswa / Ortu</td>
                        <td class="py-3 px-4 font-medium text-[#1D4ED8] dark:text-blue-400">siswa1</td>
                        <td class="py-3 px-4 text-slate-500">password</td>
                        <td class="py-3 px-4 font-sans text-slate-700 dark:text-slate-300">Cek jadwal harian, ajukan surat izin sakit/keperluan + upload bukti</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sub 5: Release Notes --}}
    <div id="release-notes" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Release Notes &amp; Changelog</span>
        </h2>

        {{-- Versi 1.0.1 (Mobile Update) --}}
        <div class="rounded-xl border border-blue-200 dark:border-blue-900/60 bg-blue-50/30 dark:bg-blue-950/20 p-4 sm:p-5 text-sm sm:text-base space-y-2.5">
            <div class="flex items-center justify-between text-slate-900 dark:text-white font-semibold">
                <span class="text-base sm:text-lg">Versi 1.0.1 (Mobile App Update)</span>
                <span class="text-blue-700 dark:text-blue-400 font-mono text-xs sm:text-sm bg-blue-100 dark:bg-blue-900/60 px-2.5 py-1 rounded-md border border-blue-200 dark:border-blue-800">Mobile Patch</span>
            </div>
            <ul class="text-slate-600 dark:text-slate-300 list-disc list-inside space-y-1.5 leading-relaxed">
                <li>Validasi status pengisian jurnal mengajar tatap muka dan pencegahan entri ganda per jadwal harian.</li>
                <li>Pemilihan cerdas otomatis jadwal mengajar yang belum diisi saat membuka formulir jurnal.</li>
                <li>Validasi wajib dokumentasi foto kegiatan belajar mengajar (KBM) realtime via kamera perangkat.</li>
                <li>Integrasi modul upload multipart HTTP parser untuk keandalan transmisi bukti foto kelas ke server.</li>
            </ul>
        </div>

        {{-- Versi 1.0.0 (Core UKK Edition) --}}
        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] p-4 sm:p-5 text-sm sm:text-base space-y-2.5">
            <div class="flex items-center justify-between text-slate-900 dark:text-white font-semibold">
                <span class="text-base sm:text-lg">Versi 1.0.0 (Siap Uji Kompetensi Keahlian)</span>
                <span class="text-emerald-600 dark:text-emerald-400 font-mono text-xs sm:text-sm bg-emerald-50 dark:bg-emerald-950/60 px-2.5 py-1 rounded-md border border-emerald-200 dark:border-emerald-800">Production Ready</span>
            </div>
            <ul class="text-slate-600 dark:text-slate-300 list-disc list-inside space-y-1.5 leading-relaxed">
                <li>100% ketercapaian rubrik UKK Paket 3 RPL (5 logika bisnis inti terimplementasi).</li>
                <li>Pengambilan foto jurnal berbasis webcam realtime via HTML5 canvas-to-blob.</li>
                <li>Endpoint REST API Sanctum untuk klien mobile Android.</li>
                <li>Fitur ekspor laporan presensi resmi format PDF dan spreadsheet Excel.</li>
            </ul>
        </div>
    </div>

</section>
