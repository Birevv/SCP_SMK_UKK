# 🚀 PANDUAN LANGKAH-LANGKAH SELANJUTNYA
## Project: School Class Progress (SCP) — UKK Paket 3 Rekayasa Perangkat Lunak 2026

Dokumen ini berisi daftar langkah konkret, terstruktur, dan berurutan yang harus dilakukan untuk menyempurnakan aplikasi, melakukan pengujian, serta mempersiapkan demonstrasi di hadapan Penguji Uji Kompetensi Keahlian (UKK).

---

## 📊 1. Ringkasan Status Saat Ini (Current Progress)

Berdasarkan audit sistem dan automated test yang telah dijalankan:
- ✅ **5 Logika Bisnis Kunci Kelulusan UKK telah terimplementasi & Lolos Unit Test (6 Tests Passed):**
  1. *Anti-Tabrakan Jadwal (Conflict Prevention)* via `NoScheduleConflict.php` (`Start_A < End_B AND End_A > Start_B`).
  2. *Sistem Putaran Blok (Cycle Toggle Putaran 1/2)* via `SystemConfiguration` & `SystemConfigWidget`.
  3. *Mekanisme Kunci Jadwal (Lock Schedule)* via `SchedulePolicy`.
  4. *Sinkronisasi Otomatis Izin & Presensi* via `JournalObserver` & `LeaveRequestObserver`.
  5. *Dashboard Alert Reminder (>30 Menit Tanpa Cron)* via `UnfilledJournalAlertWidget`.
- ✅ **Laporan Presensi & Cetak PDF** telah selesai (`AttendanceReport.php` & template cetak siap cetak).

---

## 🎯 2. Roadmap Pengerjaan Selanjutnya (Action Plan)

Berikut adalah tahapan langkah demi langkah yang direkomendasikan untuk dieksekusi:

```
┌─────────────────────────────────────────────────────────────────────────┐
│ TAHAP 1: Penyempurnaan Keamanan & Hak Akses (RBAC Hardening)            │
├─────────────────────────────────────────────────────────────────────────┤
│ TAHAP 2: Penyempurnaan Seeder & Data Dummy Realistis                     │
├─────────────────────────────────────────────────────────────────────────┤
│ TAHAP 3: Validasi Form & Konfigurasi Upload File                        │
├─────────────────────────────────────────────────────────────────────────┤
│ TAHAP 4: Pengujian Menyeluruh (Simulasi Uji Kompetensi)                 │
├─────────────────────────────────────────────────────────────────────────┤
│ TAHAP 5: Persiapan Presentasi & Berkas Penilaian UKK                    │
└─────────────────────────────────────────────────────────────────────────┘
```

---

### 🔹 TAHAP 1: Penyempurnaan Keamanan & Hak Akses (RBAC Hardening)

Meskipun logika utama sudah berjalan, ada beberapa penyesuaian otorisasi menu agar data master terlindungi dari role non-Waka:

1. **Batasi Akses Resource Data Master:**
   - [x] Buka `app/Filament/Resources/AcademicYears/AcademicYearResource.php` dan tambahkan:
     ```php
     public static function canViewAny(): bool
     {
         return Auth::user()?->role?->name === 'Waka Kurikulum';
     }
     ```
   - [x] Buka `app/Filament/Resources/Subjects/SubjectResource.php` dan tambahkan hal serupa agar hanya Waka Kurikulum yang bisa mengelola Mata Pelajaran.

2. **Kunci Guru Pengajar pada Form Jadwal:**
   - [x] Pada `app/Filament/Resources/Schedules/Schemas/ScheduleForm.php`, ubah field `teacher_id`:
     - Jika user yang login adalah `Guru`, kunci field `teacher_id` ke ID user yang login secara otomatis dan nonaktifkan perubahan dropdown:
       ```php
       Select::make('teacher_id')
           ->default(fn () => Auth::user()?->role?->name === 'Guru' ? Auth::id() : null)
           ->disabled(fn () => Auth::user()?->role?->name === 'Guru')
           ->dehydrated()
       ```
   - [x] Tambahkan validasi `->after('start_time')` pada field `end_time` agar jam selesai tidak boleh lebih awal dari jam mulai.

---

### 🔹 TAHAP 2: Penyempurnaan Seeder & Data Dummy Realistis (Demo-Ready)

Agar saat pengujian di depan Penguji sistem langsung memiliki data siap uji tanpa perlu input manual yang memakan waktu:

1. **Buat Seeder Master Data Tambahan:**
   - [x] Buat `AcademicYearSeeder.php` dengan data tahun ajaran aktif:
     * `2025/2026 - Ganjil` (`is_active = true`)
     * `2025/2026 - Genap` (`is_active = false`)
   - [x] Buat `SubjectSeeder.php` dengan mata pelajaran kejuruan RPL:
     * *Pemrograman Web dan Perangkat Bergerak (PWPB)*
     * *Basis Data*
     * *Pemodelan Perangkat Lunak (PPL)*
     * *Pemrograman Berorientasi Objek (PBO)*
     * *Projek Kreatif dan Kewirausahaan (PKK)*

2. **Perbaiki Relasi Siswa & Kelas pada `UserSeeder.php`:**
   - [x] Karena tabel relasi siswa menggunakan pivot `user_class`, pastikan seeder meng-attach siswa ke kelas:
     ```php
     $siswa = User::firstOrCreate([...]);
     $siswa->classrooms()->syncWithoutDetaching([
         $defaultClassroom->id => [
             'academic_year_id' => $activeAcademicYear->id,
             'status' => 'Aktif',
         ]
     ]);
     ```
   - [x] Tambahkan minimal 5 - 10 akun siswa dummy pada kelas `XII RPL 1` dan `XII RPL 2` agar saat Guru membuat Jurnal, tabel absensi terisi daftar siswa yang lengkap.

3. **Tambahkan Sample Jadwal & Surat Izin:**
   - [ ] Buat jadwal contoh untuk Putaran 1 dan Putaran 2.
   - [ ] Buat 1 sample Surat Izin dengan status `Approved` dan 1 dengan status `Pending` untuk menunjukkan sinkronisasi absensi secara live.

---

### 🔹 TAHAP 3: Validasi Form & Konfigurasi Upload File

1. **Validasi File Upload Foto Jurnal:**
   - [ ] Buka `app/Filament/Resources/Journals/Schemas/JournalForm.php`, tambahkan batasan pada `activity_photo`:
     ```php
     FileUpload::make('activity_photo')
         ->image()
         ->maxSize(2048) // Maksimal 2MB
         ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])
         ->directory('journal-photos')
         ->label('Bukti Foto Kegiatan (Realtime)')
         ->required(),
     ```

2. **Pastikan Symlink Storage Aktif:**
   - [ ] Jalankan perintah:
     ```bash
     php artisan storage:link
     ```
   - [ ] Pastikan folder `storage/app/public/journal-photos` dan `storage/app/public/leave_attachments` dapat diakses dari browser.

---

### 🔹 TAHAP 4: Pengujian Menyeluruh (Simulasi Uji Kompetensi)

Lakukan simulasi pengujian dengan skenario penguji berikut:

#### Skenario 1: Waka Kurikulum
1. Login sebagai `waka` / `password`.
2. Buka Dashboard, uji tombol **Ganti ke Putaran 2** ➔ Cek apakah badge berubah dan jadwal terfilter ke Putaran 2.
3. Klik tombol **Kunci Jadwal** ➔ Cek status berubah menjadi Terkunci.
4. Coba akses menu **Tahun Ajaran**, **Mata Pelajaran**, **Kelas**, **Ruangan**, dan **Pengguna**.

#### Skenario 2: Guru & Validasi Bentrok Jadwal
1. Login sebagai `guru1` / `password`.
2. Saat status jadwal terkunci: Coba buka menu Jadwal Mengajar ➔ Pastikan tombol *Buat Jadwal* hilang / dinonaktifkan.
3. Login kembali sebagai `waka`, buka kunci jadwal.
4. Login sebagai `guru1`, buat jadwal mengajar baru pada hari Senin jam 08:00 - 10:00 di Ruang Lab RPL 1.
5. Coba buat jadwal **KEDUA** yang menabrak jam tersebut (misal jam 09:00 - 11:00 di ruang yang sama) ➔ **Sistem harus menolak dengan pesan error tabrakan!**
6. Buat Jurnal Mengajar untuk jadwal hari ini, unggah foto kegiatan, simpan ➔ Cek di relasi Presensi apakah seluruh siswa otomatis berstatus 'Hadir'.

#### Skenario 3: Siswa & Pengajuan Izin
1. Login sebagai `siswa1` / `password`.
2. Buka menu **Surat Izin / Sakit**, ajukan perizinan sakit untuk tanggal hari ini, unggah bukti surat dokter (status otomatis `Pending`).
3. Pastikan siswa hanya bisa melihat surat izin miliknya sendiri.

#### Skenario 4: Tim Kesiswaan (Wali Kelas / Guru BK)
1. Login sebagai `walikelas1` / `password`.
2. Buka menu **Surat Izin / Sakit** ➔ Klik tombol **Approve** pada izin `siswa1`.
3. Buka Jurnal Mengajar pada tanggal tersebut ➔ Pastikan status presensi `siswa1` otomatis berubah menjadi **Sakit** (Sinkronisasi sukses).

#### Skenario 5: Peringatan Jurnal Terlambat (Dashboard Alert)
1. Buat jadwal yang `end_time`-nya 35 menit sebelum jam saat ini tanpa mengisi jurnal.
2. Buka Dashboard Guru / Waka ➔ Muncul banner merah peringatan *"Peringatan: Jurnal Mengajar Belum Diisi!"* lengkap dengan tombol cepat "Isi Jurnal".

#### Skenario 6: Rekapitulasi & Cetak Laporan
1. Buka menu **Laporan Presensi**.
2. Pilih Kelas `XII RPL 1` dan rentang tanggal bulan ini.
3. Klik tombol **Cetak Laporan (PDF / Print)** ➔ Jendela cetak browser otomatis terbuka dengan format Kop Laporan resmi dan kolom tanda tangan.

---

### 🔹 TAHAP 5: Persiapan Presentasi & Berkas Penilaian UKK

1. **Cheatsheet Akun Login Ujian:**
   | Role | Username | Password Default | Keterangan Akses |
   | :--- | :--- | :--- | :--- |
   | **Waka Kurikulum** | `waka` | `password` | Master Data, Lock Schedule, Switch Putaran |
   | **Guru Mata Pelajaran** | `guru1` | `password` | Input Jadwal Mandiri, Input Jurnal + Foto |
   | **Wali Kelas** | `walikelas1` | `password` | Verifikasi Izin, Rekap Presensi Kelas |
   | **Guru BK** | `gurubk` | `password` | Monitoring Presensi & Verifikasi Izin Siswa |
   | **Siswa / Orang Tua** | `siswa1` | `password` | Pengajuan Izin + Bukti, Cek Rekap Kehadiran |

2. **Poin Jawaban Teknis untuk Penguji (FAQ Penguji UKK):**
   - **Pertanyaan:** *"Bagaimana sistem memastikan tidak terjadi bentrok ruangan atau jam mengajar?"*
     - **Jawaban:** Menggunakan algoritma irisan waktu pada validation rule `NoScheduleConflict`: `(start_time < Input_End) AND (end_time > Input_Start)` dengan pemfilteran hari, siklus putaran aktif, ruangan, kelas, dan guru.
   - **Pertanyaan:** *"Bagaimana mekanisme perizinan siswa otomatis masuk ke absensi guru?"*
     - **Jawaban:** Menggunakan arsitektur *Eloquent Observer* (`JournalObserver` & `LeaveRequestObserver`). Saat jurnal dibuat, sistem query data izin yang berstatus `Approved` dan otomatis menandai siswa tersebut sebagai Sakit/Izin tanpa perlu guru menginput manual satu per satu.
   - **Pertanyaan:** *"Bagaimana alert jurnal terlambat bekerja tanpa cron job?"*
     - **Jawaban:** Sistem menghitung selisih waktu secara dinamis pada *Dashboard Widget Lifecycle* saat halaman dimuat menggunakan Carbon (`now()->subMinutes(30)`), memeriksa jadwal yang sudah selesai namun belum memiliki relasi di tabel `journals`.

---

## 🛠️ 3. Perintah Eksekusi Cepat (Quickstart Commands)

Jalankan perintah ini jika ingin mereset dan menyiapkan lingkungan pengujian dari nol:

```bash
# 1. Jalankan migrasi dan seeder
php artisan migrate:fresh --seed

# 2. Pastikan symlink file upload terhubung
php artisan storage:link

# 3. Jalankan pengujian otomatis untuk memastikan semua fitur hijau (pass)
php artisan test

# 4. Jalankan server lokal
php artisan serve
```

---
*Dokumen ini dibuat otomatis sebagai panduan kerja standar penyelesaian Uji Kompetensi Keahlian (UKK) RPL 2026.*
