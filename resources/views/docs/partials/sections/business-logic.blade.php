<section id="business-logic" data-category="5 Logika Bisnis Utama" class="scroll-mt-24 space-y-8">

    {{-- Breadcrumb & Title --}}
    <div>
        <nav class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mb-2.5" aria-label="Breadcrumb">
            <span>Dokumentasi</span>
            <span class="text-slate-300 dark:text-zinc-600">/</span>
            <span class="text-[#1D4ED8] dark:text-blue-400 font-semibold">5 Logika Bisnis Utama</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            5 Logika Bisnis Utama
        </h1>
        <p class="mt-3 text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
            Implementasi lima algoritma inti persyaratan kelulusan instrumen praktik UKK Paket 3 RPL.
        </p>
    </div>

    {{-- 1. Anti-Tabrakan Waktu --}}
    <div id="anti-tabrakan" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>1. Validasi Anti-Tabrakan Waktu (Conflict Prevention)</span>
        </h2>
        <p class="text-base text-slate-700 dark:text-slate-300 leading-relaxed">
            Mencegah guru mengajar di dua tempat, kelas memiliki dua guru, atau ruangan dipakai bersamaan pada hari dan siklus yang sama.
        </p>

        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] p-4 text-sm sm:text-base space-y-2">
            <span class="text-xs font-semibold text-slate-400 dark:text-zinc-500 uppercase tracking-wider block">Rumus Interseksi Waktu Terbuka:</span>
            <code class="block font-mono text-sm sm:text-base text-[#1D4ED8] dark:text-blue-400 bg-slate-50 dark:bg-zinc-900 p-3 rounded-lg border border-slate-200 dark:border-zinc-800">
                (Start_Baru &lt; End_Eksis) AND (End_Baru &gt; Start_Eksis)
            </code>
            <p class="text-slate-500 dark:text-zinc-400 text-xs sm:text-sm pt-1">
                Jadwal yang bersentuhan tepat di batas jam (misal 07:00-08:30 dan 08:30-10:00) dinyatakan sah (tidak bentrok).
            </p>
        </div>

        <div class="docs-code-block rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-800 bg-slate-900">
            <div class="flex items-center justify-between px-4 py-2 bg-slate-800/90 dark:bg-zinc-900 border-b border-slate-700/80 dark:border-zinc-800 text-xs sm:text-sm font-mono text-slate-300">
                <span>Scope Pengecekan Jadwal di Schedule.php</span>
                <span class="text-blue-400">PHP</span>
            </div>
            <pre class="p-4 text-xs sm:text-sm text-slate-100 overflow-x-auto font-mono bg-slate-950 leading-relaxed"><code>$hasConflict = Schedule::where('day_of_week', $data['day_of_week'])
    ->where('cycle_type', $data['cycle_type'])
    ->where(function ($q) use ($data) {
        $q->where('room_id', $data['room_id'])
          ->orWhere('class_id', $data['class_id'])
          ->orWhere('teacher_id', $data['teacher_id']);
    })
    ->where('start_time', '&lt;', $data['end_time'])
    ->where('end_time', '&gt;', $data['start_time'])
    ->when($recordId, fn($q) => $q->where('id', '!=', $recordId))
    ->exists();

if ($hasConflict) {
    throw ValidationException::withMessages([
        'start_time' => 'Jadwal bentrok dengan guru, kelas, atau ruang lain!',
    ]);
}</code></pre>
        </div>
    </div>

    {{-- 2. Saklar Putaran Blok --}}
    <div id="saklar-putaran" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>2. Saklar Putaran Blok (Cycle Toggle)</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-3 px-4">Siklus</th>
                        <th class="py-3 px-4">Peruntukan Kurikulum</th>
                        <th class="py-3 px-4">Dampak pada Sistem</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Putaran 1</td>
                        <td class="py-3 px-4">Minggu A: Teori Umum / Normatif Adaptif</td>
                        <td class="py-3 px-4">Dashboard & jurnal menampilkan jadwal cycle_type = 1</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Putaran 2</td>
                        <td class="py-3 px-4">Minggu B: Praktik Bengkel Kejuruan TEFA</td>
                        <td class="py-3 px-4">Dashboard & jurnal menampilkan jadwal cycle_type = 2</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- 3. Kunci Jadwal --}}
    <div id="kunci-jadwal" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>3. Mekanisme Kunci Jadwal (Lock Schedule)</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-3 px-4">Lapisan</th>
                        <th class="py-3 px-4">Kondisi is_schedule_locked = true</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Filament UI</td>
                        <td class="py-3 px-4">Aksi Buat, Ubah, dan Hapus jadwal otomatis disembunyikan untuk Guru.</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Policy Gate</td>
                        <td class="py-3 px-4 font-mono text-[#1D4ED8] dark:text-blue-400 font-medium">SchedulePolicy::create/update/delete() = false</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">REST API</td>
                        <td class="py-3 px-4 font-mono text-amber-600 dark:text-amber-400 font-medium">POST /api/teacher/schedules mengembalikan HTTP 403 Forbidden</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- 4. Alur Sinkronisasi Perizinan --}}
    <div id="sync-perizinan" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>4. Alur Sinkronisasi Presensi dari Surat Izin (Sync Logic)</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm sm:text-base">
            <div class="p-4 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] space-y-1.5">
                <span class="font-bold text-[#1D4ED8] dark:text-blue-400 block text-base sm:text-lg">1. Siswa Mengajukan</span>
                <p class="text-slate-600 dark:text-slate-300 leading-relaxed">Siswa submit tanggal, alasan, dan bukti foto surat ke sistem (status Pending).</p>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] space-y-1.5">
                <span class="font-bold text-amber-600 dark:text-amber-400 block text-base sm:text-lg">2. Tim Kesiswaan Approve</span>
                <p class="text-slate-600 dark:text-slate-300 leading-relaxed">Wali kelas atau guru BK memeriksa berkas dan menyetujui izin (status Approved).</p>
            </div>
            <div class="p-4 rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] space-y-1.5">
                <span class="font-bold text-emerald-600 dark:text-emerald-400 block text-base sm:text-lg">3. Sinkronisasi Otomatis</span>
                <p class="text-slate-600 dark:text-slate-300 leading-relaxed">Jurnal guru pada hari izin otomatis memuat status Sakit atau Izin tanpa ketik manual.</p>
            </div>
        </div>
    </div>

    {{-- 5. Dashboard Alert Reminder --}}
    <div id="alert-reminder" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>5. Dashboard Alert Reminder (Keterlambatan Jurnal)</span>
        </h2>
        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] p-4 sm:p-5 text-sm sm:text-base space-y-2.5">
            <div class="flex items-center justify-between font-semibold text-slate-900 dark:text-white">
                <span class="text-base sm:text-lg">Aturan Deteksi Stateless (Tanpa Cron Job)</span>
                <span class="text-amber-600 dark:text-amber-400 font-mono text-xs sm:text-sm bg-amber-50 dark:bg-amber-950/60 px-2.5 py-1 rounded-md border border-amber-200 dark:border-amber-800">Tenggat Waktu: 30 Menit</span>
            </div>
            <p class="text-slate-700 dark:text-slate-300 leading-relaxed">
                Peringatan merah muncul saat: <code class="text-slate-900 dark:text-white font-mono bg-slate-100 dark:bg-zinc-800 px-2 py-0.5 rounded">now() &gt; end_time + 30 menit</code> dan belum ada data jurnal tersimpan untuk jadwal aktif hari ini.
            </p>
        </div>
    </div>

</section>
