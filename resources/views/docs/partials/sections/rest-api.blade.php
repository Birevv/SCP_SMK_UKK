<section id="rest-api" data-category="REST API & Mobile" class="scroll-mt-24 space-y-8">

    {{-- Breadcrumb & Title --}}
    <div>
        <nav class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mb-2.5" aria-label="Breadcrumb">
            <span>Dokumentasi</span>
            <span class="text-slate-300 dark:text-zinc-600">/</span>
            <span class="text-[#1D4ED8] dark:text-blue-400 font-semibold">REST API & Mobile</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            REST API & Integrasi Mobile Android
        </h1>
        <p class="mt-3 text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
            Spesifikasi endpoint RESTful JSON API untuk komunikasi aplikasi mobile Android siswa dan guru dengan server SCP.
        </p>
    </div>

    {{-- API 1: Auth & Token --}}
    <div id="api-auth" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>1. Autentikasi Sanctum Bearer Token</span>
        </h2>
        <div class="docs-code-block rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-800 bg-slate-900">
            <div class="flex items-center justify-between px-4 py-2 bg-slate-800/90 dark:bg-zinc-900 border-b border-slate-700/80 dark:border-zinc-800 text-xs sm:text-sm font-mono text-slate-300">
                <span>POST /api/login</span>
                <span class="text-blue-400">JSON</span>
            </div>
            <pre class="p-4 text-xs sm:text-sm text-slate-100 overflow-x-auto font-mono bg-slate-950 leading-relaxed"><code>// Request Payload:
{
  "username": "guru1",
  "password": "password"
}

// Response (HTTP 200 OK):
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "token": "1|eXamPLeTokENsaNcTUm2026...",
    "user": {
      "id": 2,
      "username": "guru1",
      "name": "Budi Santoso, S.Kom",
      "roles": ["Guru"]
    }
  }
}</code></pre>
        </div>
        <p class="text-sm text-slate-600 dark:text-slate-400">
            Sertakan token pada header request berikutnya: <code class="text-slate-900 dark:text-white font-mono bg-slate-100 dark:bg-zinc-800 px-2 py-0.5 rounded">Authorization: Bearer &lt;token&gt;</code>.
        </p>
    </div>

    {{-- API 2: Referensi Data --}}
    <div id="api-references" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>2. Endpoint Data Referensi (Dropdown Options)</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base font-mono">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800 font-sans">
                    <tr>
                        <th class="py-3 px-4">Method</th>
                        <th class="py-3 px-4">Endpoint</th>
                        <th class="py-3 px-4">Fungsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 text-[#1D4ED8] dark:text-blue-400 font-bold">GET</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/references/classrooms</td>
                        <td class="py-3 px-4 font-sans">Daftar kelas aktif dan jurusan</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-[#1D4ED8] dark:text-blue-400 font-bold">GET</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/references/rooms</td>
                        <td class="py-3 px-4 font-sans">Daftar ruangan kelas dan lab</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-[#1D4ED8] dark:text-blue-400 font-bold">GET</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/references/subjects</td>
                        <td class="py-3 px-4 font-sans">Daftar mata pelajaran produktif & umum</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-[#1D4ED8] dark:text-blue-400 font-bold">GET</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/references/calendar-events</td>
                        <td class="py-3 px-4 font-sans">Jadwal libur dan agenda akademik</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- API 3: Siswa --}}
    <div id="api-siswa" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>3. Endpoint Portal Siswa (role: Siswa)</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base font-mono">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800 font-sans">
                    <tr>
                        <th class="py-3 px-4">Method</th>
                        <th class="py-3 px-4">Endpoint</th>
                        <th class="py-3 px-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 text-[#1D4ED8] dark:text-blue-400 font-bold">GET</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/student/dashboard</td>
                        <td class="py-3 px-4 font-sans">Persentase kehadiran dan jadwal hari ini</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-[#1D4ED8] dark:text-blue-400 font-bold">GET</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/student/attendance-history</td>
                        <td class="py-3 px-4 font-sans">Rekap riwayat presensi harian per mapel</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-emerald-600 dark:text-emerald-400 font-bold">POST</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/student/leave-requests</td>
                        <td class="py-3 px-4 font-sans">Submit surat izin (form-data: start_date, end_date, reason, file)</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- API 4: Guru --}}
    <div id="api-guru" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>4. Endpoint Guru & Jurnal (role: Guru)</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base font-mono">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800 font-sans">
                    <tr>
                        <th class="py-3 px-4">Method</th>
                        <th class="py-3 px-4">Endpoint</th>
                        <th class="py-3 px-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 text-[#1D4ED8] dark:text-blue-400 font-bold">GET</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/teacher/schedules</td>
                        <td class="py-3 px-4 font-sans">Daftar jadwal mengajar guru pada putaran aktif</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-[#1D4ED8] dark:text-blue-400 font-bold">GET</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/teacher/schedules/{id}/students</td>
                        <td class="py-3 px-4 font-sans">Daftar siswa kelas + status izin otomatis</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-emerald-600 dark:text-emerald-400 font-bold">POST</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/teacher/journals</td>
                        <td class="py-3 px-4 font-sans">Simpan materi KBM, upload foto kamera, dan submit absensi</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- API 5: Kesiswaan --}}
    <div id="api-homeroom" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>5. Endpoint Verifikasi Kesiswaan (role: Wali Kelas / Guru BK)</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base font-mono">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800 font-sans">
                    <tr>
                        <th class="py-3 px-4">Method</th>
                        <th class="py-3 px-4">Endpoint</th>
                        <th class="py-3 px-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 text-[#1D4ED8] dark:text-blue-400 font-bold">GET</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/homeroom/leave-requests</td>
                        <td class="py-3 px-4 font-sans">Daftar pengajuan izin siswa yang memerlukan verifikasi</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 text-amber-600 dark:text-amber-400 font-bold">PUT</td>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">/api/homeroom/leave-requests/{id}/verify</td>
                        <td class="py-3 px-4 font-sans">Ubah status izin menjadi 'approved' atau 'rejected'</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</section>
