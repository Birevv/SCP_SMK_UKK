<section id="operational-sop" data-category="Dokumentasi Operasional (SOP)" class="scroll-mt-24 space-y-8">

    {{-- Breadcrumb & Title --}}
    <div>
        <nav class="flex items-center gap-2 text-xs sm:text-sm text-slate-500 dark:text-zinc-400 mb-2.5" aria-label="Breadcrumb">
            <span>Dokumentasi</span>
            <span class="text-slate-300 dark:text-zinc-600">/</span>
            <span class="text-[#1D4ED8] dark:text-blue-400 font-semibold">Dokumentasi Operasional</span>
        </nav>
        <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Dokumentasi Operasional (SOP per Role)
        </h1>
        <p class="mt-3 text-base sm:text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
            Standar operasional prosedur pelaksanaan tata kelola presensi, jurnal mengajar, dan perizinan siswa di SMK Negeri 1 Bantul.
        </p>
    </div>

    {{-- SOP 1: Waka Kurikulum --}}
    <div id="sop-waka" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>SOP 1: Tata Kelola Waka Kurikulum (Administrator Akademik)</span>
        </h2>
        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] divide-y divide-slate-100 dark:divide-zinc-800 text-sm sm:text-base">
            <div class="p-4 sm:p-5">
                <span class="font-bold text-slate-900 dark:text-white block text-base sm:text-lg mb-2">A. Setup Awal Semester</span>
                <ol class="list-decimal list-inside space-y-2 text-slate-700 dark:text-slate-300 leading-relaxed">
                    <li>Buka menu <strong class="text-slate-900 dark:text-white">Academic Years</strong>, aktifkan semester berjalan.</li>
                    <li>Lengkapi master data <strong class="text-slate-900 dark:text-white">Rooms</strong> (Ruang/Lab), <strong class="text-slate-900 dark:text-white">Classrooms</strong> (Kelas), dan <strong class="text-slate-900 dark:text-white">Subjects</strong> (Mapel).</li>
                    <li>Buka <strong class="text-slate-900 dark:text-white">System Configurations</strong>, setel <code class="text-emerald-600 dark:text-emerald-400 font-mono font-medium">is_schedule_locked = false</code> untuk membuka masa input mandiri guru.</li>
                </ol>
            </div>
            <div class="p-4 sm:p-5">
                <span class="font-bold text-slate-900 dark:text-white block text-base sm:text-lg mb-2">B. Kunci Jadwal & Operasional Mingguan</span>
                <ol class="list-decimal list-inside space-y-2 text-slate-700 dark:text-slate-300 leading-relaxed">
                    <li>Setelah verifikasi jadwal selesai, aktifkan <code class="text-amber-600 dark:text-amber-400 font-mono font-medium">is_schedule_locked = true</code> agar jadwal terkunci permanen.</li>
                    <li>Setiap Senin pagi, pastikan toggle putaran aktif (Putaran 1 / Putaran 2) sesuai kalender blok sekolah.</li>
                    <li>Pantau rekap kehadiran sekolah melalui menu <strong class="text-slate-900 dark:text-white">Attendance Report</strong> dan cetak PDF rekap bulanan.</li>
                </ol>
            </div>
        </div>
    </div>

    {{-- SOP 2: Guru Mata Pelajaran --}}
    <div id="sop-guru" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>SOP 2: Pelaksanaan KBM, Jurnal & Presensi (Guru Mata Pelajaran)</span>
        </h2>
        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] p-4 sm:p-5 text-sm sm:text-base space-y-2.5">
            <ol class="list-decimal list-inside space-y-2.5 text-slate-700 dark:text-slate-300 leading-relaxed">
                <li>Buka dashboard panel SCP (<code class="text-[#1D4ED8] dark:text-blue-400 font-mono font-semibold">/app</code>) via browser laptop atau hp di ruang kelas.</li>
                <li>Pada widget jadwal hari ini, klik tombol <strong class="text-slate-900 dark:text-white">"Isi Jurnal"</strong> pada sesi KBM yang sedang berlangsung.</li>
                <li>Tuliskan ringkasan materi dan catatan kendala kelas di kolom <strong class="text-slate-900 dark:text-white">Materi / Topic Description</strong>.</li>
                <li>Klik tombol <strong class="text-[#1D4ED8] dark:text-blue-400">"Buka Kamera"</strong>, arahkan ke kegiatan kelas, klik <strong class="text-slate-900 dark:text-white">"Ambil Foto"</strong> (foto terkompresi otomatis).</li>
                <li>Periksa daftar kehadiran siswa (siswa berizin resmi sudah otomatis tercentang Sakit/Izin). Tandai siswa yang Alpa.</li>
                <li>Klik <strong class="text-emerald-600 dark:text-emerald-400 font-semibold">Simpan Jurnal & Presensi</strong> sebelum batas waktu 30 menit setelah jam pelajaran selesai.</li>
            </ol>
        </div>
    </div>

    {{-- SOP 3: Tim Kesiswaan (Wali Kelas & Guru BK) --}}
    <div id="sop-kesiswaan" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>SOP 3: Verifikasi Surat Izin & Pemantauan (Tim Kesiswaan)</span>
        </h2>
        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] p-4 sm:p-5 text-sm sm:text-base space-y-2">
            <ol class="list-decimal list-inside space-y-2 text-slate-700 dark:text-slate-300 leading-relaxed">
                <li>Buka menu <strong class="text-slate-900 dark:text-white">Leave Requests</strong>. Filter data berstatus <span class="text-amber-600 dark:text-amber-400 font-medium">Pending</span>.</li>
                <li>Klik berkas lampiran untuk melihat foto surat dokter atau surat keterangan orang tua.</li>
                <li>Klik <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Approve</span> jika valid, atau <span class="text-red-500 font-semibold">Reject</span> dengan catatan alasan jika tidak valid.</li>
                <li>Pantau rekap bulanan di menu <strong class="text-slate-900 dark:text-white">Attendance Report</strong> untuk tindak lanjut pembinaan siswa.</li>
            </ol>
        </div>
    </div>

    {{-- SOP 4: Siswa & Orang Tua --}}
    <div id="sop-siswa" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>SOP 4: Pengajuan Izin Mandiri (Siswa & Orang Tua)</span>
        </h2>
        <div class="rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c] p-4 sm:p-5 text-sm sm:text-base space-y-2">
            <ol class="list-decimal list-inside space-y-2 text-slate-700 dark:text-slate-300 leading-relaxed">
                <li>Login dengan akun siswa di web panel atau aplikasi mobile Android SCP.</li>
                <li>Buka menu <strong class="text-slate-900 dark:text-white">Leave Requests</strong>, klik <strong class="text-[#1D4ED8] dark:text-blue-400">Buat Pengajuan</strong>.</li>
                <li>Pilih jenis izin (Sakit / Keperluan Penting), tentukan rentang tanggal, tulis alasan, dan unggah foto bukti surat.</li>
                <li>Kirim formulir. Pantau status approval (Pending -> Approved / Rejected) langsung dari dashboard.</li>
            </ol>
        </div>
    </div>

    {{-- SOP 5: Troubleshooting --}}
    <div id="troubleshooting" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Troubleshooting & Solusi Cepat</span>
        </h2>
        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-zinc-800 bg-white dark:bg-[#12141c]">
            <table class="w-full text-left text-sm sm:text-base">
                <thead class="bg-slate-50 dark:bg-zinc-900 text-slate-800 dark:text-slate-200 font-semibold border-b border-slate-200 dark:border-zinc-800">
                    <tr>
                        <th class="py-3 px-4 w-52 sm:w-60">Gejala Masalah</th>
                        <th class="py-3 px-4">Penyebab</th>
                        <th class="py-3 px-4">Solusi Langsung</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-zinc-800 text-slate-700 dark:text-slate-300">
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Kamera webcam tidak muncul</td>
                        <td class="py-3 px-4">Izin browser diblokir atau belum HTTPS pada IP jaringan.</td>
                        <td class="py-3 px-4">Klik ikon gembok di address bar, ubah izin Kamera ke <strong class="text-slate-900 dark:text-white">Allow</strong>, lalu reload. Akses via localhost atau konfigurasi SSL.</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Jadwal tidak bisa ditambah guru</td>
                        <td class="py-3 px-4">Jadwal telah dikunci oleh Waka Kurikulum.</td>
                        <td class="py-3 px-4">Hubungi Waka Kurikulum untuk membuka kunci jadwal sementara jika ada revisi pembagian jam.</td>
                    </tr>
                    <tr>
                        <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">Foto jurnal tidak tersimpan</td>
                        <td class="py-3 px-4">Symbolic link storage belum aktif.</td>
                        <td class="py-3 px-4">Jalankan perintah terminal: <code class="text-[#1D4ED8] dark:text-blue-400 font-mono font-medium">php artisan storage:link</code>.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- SOP 6: Backup & Maintenance --}}
    <div id="db-maintenance" class="scroll-mt-28 space-y-4 pt-8 border-t border-slate-200 dark:border-zinc-800">
        <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2.5">
            <span class="text-[#1D4ED8] dark:text-blue-400">#</span>
            <span>Maintenance & Backup Basis Data</span>
        </h2>
        <div class="docs-code-block rounded-xl overflow-hidden border border-slate-200 dark:border-zinc-800 bg-slate-900">
            <div class="flex items-center justify-between px-4 py-2 bg-slate-800/90 dark:bg-zinc-900 border-b border-slate-700/80 dark:border-zinc-800 text-xs sm:text-sm font-mono text-slate-300">
                <span>Perintah Maintenance MySQL</span>
                <span class="text-blue-400">Bash</span>
            </div>
            <pre class="p-4 text-xs sm:text-sm text-slate-100 overflow-x-auto font-mono bg-slate-950 leading-relaxed"><code># Backup database MySQL ke file .sql
mysqldump -u root -p scp_smk > backup_scp_$(date +%F).sql

# Restore database jika diperlukan pemulihan
mysql -u root -p scp_smk < backup_scp_2026-11-11.sql

# Bersihkan cache dan optimasi Laravel
php artisan optimize:clear && php artisan optimize</code></pre>
        </div>
    </div>

</section>
