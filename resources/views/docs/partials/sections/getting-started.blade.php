<section id="getting-started" data-category="Getting Started" class="scroll-mt-24 space-y-8">

    {{-- Breadcrumb & Title --}}
    <div>
        <nav class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mb-2.5" aria-label="Breadcrumb">
            <span>Dokumentasi</span>
            <span class="text-slate-300 dark:text-zinc-600">/</span>
            <span class="text-[#1D4ED8] dark:text-blue-400 font-semibold">Getting Started</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Getting Started
        </h1>
        <p class="mt-3 text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
            Panduan instalasi lokal, dependensi, konfigurasi <code class="text-[#1D4ED8] dark:text-blue-400 font-semibold">.env</code>, dan struktur proyek School Class Progress (SCP).
        </p>
    </div>

    {{-- Sub 1: Persyaratan Sistem --}}
    <div id="persyaratan-sistem" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Persyaratan Sistem</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-3 px-4">Komponen</th>
                        <th class="py-3 px-4">Versi Minimum</th>
                        <th class="py-3 px-4">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">PHP Runtime</td>
                        <td class="py-3 px-4 font-mono text-[#1D4ED8] dark:text-blue-400 font-medium">8.2+ (disarankan 8.3)</td>
                        <td class="py-3 px-4">Ekstensi aktif: pdo, pdo_mysql, mbstring, gd, exif, curl, zip</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Composer</td>
                        <td class="py-3 px-4 font-mono text-[#1D4ED8] dark:text-blue-400 font-medium">2.7+</td>
                        <td class="py-3 px-4">Manajer paket dependensi PHP</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Node.js & NPM</td>
                        <td class="py-3 px-4 font-mono text-[#1D4ED8] dark:text-blue-400 font-medium">Node 20+ / NPM 10+</td>
                        <td class="py-3 px-4">Kompilasi Tailwind v4 & asset bundling Vite</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Database</td>
                        <td class="py-3 px-4 font-mono text-[#1D4ED8] dark:text-blue-400 font-medium">MySQL 8.0+ / SQLite 3</td>
                        <td class="py-3 px-4">Mendukung foreign key cascade dan JSON column</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sub 2: Instalasi Lokal --}}
    <div id="instalasi-lokal" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Instalasi & Setup Cepat</span>
        </h2>
        <div class="docs-code-block rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-800 bg-slate-900">
            <div class="flex items-center justify-between px-4 py-2 bg-slate-800/90 dark:bg-zinc-900 border-b border-slate-700/80 dark:border-zinc-800 text-xs sm:text-sm font-mono text-slate-300">
                <span>Langkah Instalasi Lengkap (PowerShell / Bash)</span>
                <span class="text-blue-400">Bash</span>
            </div>
            <pre class="p-4 text-xs sm:text-sm text-slate-100 overflow-x-auto font-mono bg-slate-950 leading-relaxed"><code># 1. Unduh pustaka PHP & JavaScript
composer install
npm install

# 2. Salin berkas environment & buat app key
cp .env.example .env
php artisan key:generate

# 3. Jalankan migrasi tabel beserta akun bawaan
php artisan migrate:fresh --seed

# 4. Hubungkan penyimpanan berkas publik (foto jurnal & lampiran izin)
php artisan storage:link

# 5. Jalankan server lokal & aset frontend
php artisan serve
npm run dev</code></pre>
        </div>
    </div>

    {{-- Sub 3: Konfigurasi Environment --}}
    <div id="konfigurasi-env" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Konfigurasi Environment (.env)</span>
        </h2>
        <div class="docs-code-block rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-800 bg-slate-900">
            <div class="flex items-center justify-between px-4 py-2 bg-slate-800/90 dark:bg-zinc-900 border-b border-slate-700/80 dark:border-zinc-800 text-xs sm:text-sm font-mono text-slate-300">
                <span>Variabel Inti .env</span>
                <span class="text-slate-400">Config</span>
            </div>
            <pre class="p-4 text-xs sm:text-sm text-slate-100 overflow-x-auto font-mono bg-slate-950 leading-relaxed"><code>APP_NAME="School Class Progress"
APP_ENV=local
APP_URL=http://localhost:8000

# Basis Data MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scp_smk
DB_USERNAME=root
DB_PASSWORD=

# Penyimpanan & Sesi
FILESYSTEM_DISK=public
SESSION_DRIVER=database</code></pre>
        </div>
    </div>

    {{-- Sub 4: Struktur Direktori --}}
    <div id="struktur-direktori" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Struktur Direktori Proyek</span>
        </h2>
        <div class="p-4 rounded-xl bg-white dark:bg-[#12141c] border border-slate-200 dark:border-zinc-800 font-mono text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-loose">
            <div><span class="text-[#1D4ED8] dark:text-blue-400 font-bold">app/</span></div>
            <div class="pl-4">├── <span class="text-amber-600 dark:text-amber-400 font-semibold">Filament/</span> <span class="text-slate-500 font-sans">Resource UI Filament v4 (Schedules, Journals, LeaveRequests, Users)</span></div>
            <div class="pl-4">├── <span class="text-amber-600 dark:text-amber-400 font-semibold">Http/Controllers/</span></div>
            <div class="pl-8">│   ├── <span class="text-cyan-600 dark:text-cyan-400">Api/</span> <span class="text-slate-500 font-sans">REST API Controllers (Auth, Student, Teacher, Homeroom)</span></div>
            <div class="pl-8">│   └── <span class="text-slate-600 dark:text-slate-400 font-sans">DocsController, LandingController, AttendanceReportPrintController</span></div>
            <div class="pl-4">├── <span class="text-amber-600 dark:text-amber-400 font-semibold">Models/</span> <span class="text-slate-500 font-sans">Schedule, Journal, Attendance, LeaveRequest, SystemConfiguration</span></div>
            <div class="pl-4">└── <span class="text-amber-600 dark:text-amber-400 font-semibold">Policies/</span> <span class="text-slate-500 font-sans">SchedulePolicy, JournalPolicy, LeaveRequestPolicy (RBAC Gate)</span></div>
            <div><span class="text-[#1D4ED8] dark:text-blue-400 font-bold">database/</span> <span class="text-slate-500 font-sans">migrations/ & seeders/ (Data awal akun 5 role, rombel, jadwal)</span></div>
            <div><span class="text-[#1D4ED8] dark:text-blue-400 font-bold">public/js/filament/</span> <span class="text-slate-500 font-sans">realtime-photo-capture.js (Komponen kamera webcam)</span></div>
            <div><span class="text-[#1D4ED8] dark:text-blue-400 font-bold">resources/views/</span> <span class="text-slate-500 font-sans">docs/ (Halaman docs ini) & landing/ (Landing page & download)</span></div>
        </div>
    </div>

    {{-- Sub 5: Deployment --}}
    <div id="deployment" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Deployment & Produksi</span>
        </h2>
        <div class="docs-code-block rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-800 bg-slate-900">
            <div class="flex items-center justify-between px-4 py-2 bg-slate-800/90 dark:bg-zinc-900 border-b border-slate-700/80 dark:border-zinc-800 text-xs sm:text-sm font-mono text-slate-300">
                <span>Perintah Optimasi Produksi</span>
                <span class="text-blue-400">Bash</span>
            </div>
            <pre class="p-4 text-xs sm:text-sm text-slate-100 overflow-x-auto font-mono bg-slate-950 leading-relaxed"><code># 1. Build aset frontend produksi
npm run build

# 2. Cache konfigurasi, rute, dan blade view
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Jalankan skrip deploy terintegrasi (opsional jika menggunakan VPS)
chmod +x deploy.sh && ./deploy.sh</code></pre>
        </div>
    </div>

</section>
