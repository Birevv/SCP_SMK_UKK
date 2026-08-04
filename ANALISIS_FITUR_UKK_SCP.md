# PANDUAN PENGEMBANGAN STEP-BY-STEP (BEDAH FITUR 1 PER 1)

## Uji Kompetensi Keahlian (UKK) 2026 — School Class Progress (SCP)

Dokumen ini membedah setiap langkah pengerjaan secara bertahap (satu per satu) beserta fungsi, alasan kebutuhan di soal UKK, rincian kolom/atribut, dan relasinya.

---

## 🚦 STATUS CHECKLIST PENGERJAAN

- [x] **Step 0: Setup Inisialisasi & Model Dasar**
    - [x] Model & Migration `classrooms`
    - [x] Model & Migration `users`
    - [x] Widget Statistik Dashboard (`TotalUserWidget`)
- [x] **Step 1: Modul Hak Akses & Role (`roles`)**
- [x] **Step 2: Update Tabel `users` (`role_id` & `parent_id`)**
- [x] **Step 3: Master Data Ruangan (`rooms`)**
- [x] **Step 4: Konfigurasi Sistem (`system_configurations`)**
- [🔄] **Step 5: Seeder Data Awal (Roles, Default Config, Ruangan, Users)** *(Langkah Selanjutnya)*
- [ ] **Step 6: Tabel Transactional (Jadwal, Jurnal, Presensi, Perizinan)**
- [ ] **Step 7: Implemetasi 5 Logika Bisnis Utama (Kunci Kelulusan UKK)**

---

## 📌 BEDAH PENJELASAN SETIAP TAHAP (1 PER 1)

---

### TAHAP 1: HAK AKSES & USER MANAGEMENT

#### 📍 Langkah 1.1: Migration & Model `roles` (Langkah Saat Ini)

- **Apa itu dan Untuk Apa?**
  Tabel dan Model `roles` digunakan untuk menyimpan daftar peran/hak akses pengguna di dalam sistem SCP.
- **Mengapa Dibutuhkan di Soal UKK?**
  Soal UKK mewajibkan **Role-Based Access Control (RBAC)** yang membagi pengguna menjadi 6 role utama dengan antarmuka yang disesuaikan:
    1. `Waka Kurikulum` (Atur siklus blok Putaran 1/2 & Lock Schedule).
    2. `Guru` (Input jadwal mengajar, pengisian jurnal, dan presensi foto realtime).
    3. `Wali Kelas` (Memantau rekap absensi dan verifikasi surat izin).
    4. `Guru BK` (Memantau rekap absensi dan verifikasi surat izin).
    5. `Siswa` (Mengajukan izin & melihat rekap kehadiran).
    6. `Orang Tua` (Mengajukan izin anak & melihat rekap kehadiran anak).
- **Atribut / Kolom Tabel `roles`:**
    - `id`: Primary key (autoincrement).
    - `name`: Nama role (contoh: Waka Kurikulum, Guru, Wali Kelas, Guru BK, Siswa, Orang Tua).
- **Status:** 🟢 Migration & Model `roles` sudah dibuat.

---

#### 📍 Langkah 1.2: Update Migration & Model `users`

- **Apa itu dan Untuk Apa?**
  Menambahkan kolom `role_id` (foreign key ke `roles`) dan `parent_id` (foreign key ke `users`) di tabel `users`.
- **Mengapa Dibutuhkan di Soal UKK?**
    - **`role_id`**: Agar setiap user yang login (di Filament atau Portal) diketahui hak aksesnya, sehingga menu/tombol disesuaikan dengan rolenya.
    - **`parent_id`**: Menghubungkan akun **Orang Tua** ke akun **Siswa** (anaknya). Di soal UKK, Orang Tua dan Siswa menggunakan portal login yang sama, namun Orang Tua mengajukan izin atas nama anaknya (`parent_id` merujuk ke ID siswa).
- **Kolom Tambahan di `users`:**
    - `role_id`: Foreign key merujuk ke `roles.id`.
    - `parent_id`: Foreign key (nullable) merujuk ke `users.id` (untuk relasi Ortu -> Siswa).
    - `classroom_id`: Foreign key (nullable) merujuk ke `classrooms.id` (untuk Siswa & Wali Kelas).

---

#### 📍 Langkah 1.3: Migration & Model Ruangan (`rooms`)

- **Apa itu dan Untuk Apa?**
  Menyimpan master data lokasi/ruangan kelas atau laboratorium sekolah (contoh: Lab RPL 1, Ruang Teori 2, Lab Komputer).
- **Mengapa Dibutuhkan di Soal UKK?**
  Diperlukan untuk **Logika Anti-Tabrakan Waktu (Conflict Prevention)**. Saat Guru menginput jadwal mengajar di suatu jam dan hari, sistem wajib mengecek apakah ruangan (`room_id`) tersebut sudah dipakai oleh guru/kelas lain atau belum.
- **Atribut / Kolom Tabel `rooms`:**
    - `id`: Primary key.
    - `room_name`: Nama ruangan (varchar 50).

---

#### 📍 Langkah 1.4: Migration & Model Konfigurasi Sistem (`system_configurations`)

- **Apa itu dan Untuk Apa?**
  Tabel ini menyimpan status/state global dari sistem SCP yang diatur oleh Waka Kurikulum.
- **Mengapa Dibutuhkan di Soal UKK?**
  Menunjang 2 Logika Bisnis Utama UKK:
    1. **Saklar Putaran Blok (Cycle Toggle)**: Kolom `active_cycle` (Enum: `'Putaran 1'`, `'Putaran 2'`).
    2. **Mekanisme Kunci Jadwal (Lock Schedule)**: Kolom `is_schedule_locked` (Boolean: `true` / `false`).
- **Atribut / Kolom Tabel `system_configurations`:**
    - `id`: Primary key.
    - `active_cycle`: Enum (`'Putaran 1'`, `'Putaran 2'`).
    - `is_schedule_locked`: Boolean (default `false`).
    - `updated_by`: Foreign key ke `users.id` (Waka yang mengubah).

---

### TAHAP 2: SEEDER DATA AWAL

#### 📍 Langkah 2.1: Database Seeder

- **Apa itu dan Untuk Apa?**
  Script PHP untuk mengisi otomatis data awal (Role, Admin Waka, Ruangan, dan System Configuration) sehingga aplikasi siap diuji tanpa perlu input manual satu per satu dari nol.
- **Data yang Diseed:**
    - 6 Data Role di tabel `roles`.
    - 1 User bawaan `Waka Kurikulum` untuk login pertama kali.
    - 1 Record default di `system_configurations` (Putaran 1, Lock = false).
    - Beberapa Ruangan sampel (`rooms`).

---

### TAHAP 3: TABEL TRANSAKSI UTAMA

#### 📍 Langkah 3.1: Migration & Model Jadwal Mengajar (`schedules`)

- **Untuk Apa?** Guru menginput jadwal mengajar mingguan (Mata Pelajaran, Hari, Jam Mulai, Jam Selesai, Ruangan, Kelas, dan Putaran Blok 1/2).
- **Kaitan Logika UKK:** Menjadi dasar validasi Anti-Bentrok dan penentu dashboard alert guru.

#### 📍 Langkah 3.2: Migration & Model Jurnal Mengajar (`journals`)

- **Untuk Apa?** Guru mengisi laporan mengajar harian (Topik Pembelajaran, Upload Bukti Foto Kegiatan Realtime, dan Tanggal).
- **Kaitan Logika UKK:** Menjadi pemicu (trigger) untuk sinkronisasi otomatis status presensi siswa.

#### 📍 Langkah 3.3: Migration & Model Presensi (`attendances`)

- **Untuk Apa?** Mencatat kehadiran siswa per jurnal mengajar (Status: Hadir, Sakit, Izin, Alpa).
- **Kaitan Logika UKK:** Tersinkronisasi otomatis dengan pengajuan izin yang sudah di-approve.

#### 📍 Langkah 3.4: Migration & Model Pengajuan Izin (`leave_requests`)

- **Untuk Apa?** Siswa/Ortu mengajukan surat izin/sakit beserta upload foto surat resmi/dokter.
- **Kaitan Logika UKK:** Diverifikasi oleh Wali Kelas/BK (Status: Pending, Approved, Rejected).

---

### TAHAP 4: IMPLEMENTASI 5 LOGIKA BISNIS UTAMA (KUNCI KELULUSAN)

| Logika Bisnis                   | Penjelasan Rinci Bedah Fitur                                                                                                                                                                                           |
| ------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **1. Anti-Tabrakan Waktu**      | Sebelum jadwal disave, jalankan query pengecekan irisan jam `(start_time < new_end AND end_time > new_start)` pada hari, putaran, dan (ruang OR kelas OR guru) yang sama. Jika ada, munculkan error validation.        |
| **2. Toggle Siklus Blok**       | Waka merubah `active_cycle` di panel. Semua query jadwal guru otomatis memfilter `WHERE cycle_type = active_cycle`.                                                                                                    |
| **3. Lock Schedule**            | Jika `is_schedule_locked == true`, tombol Create/Edit/Delete di resource Jadwal Guru disembunyikan dan diproteksi di Policy backend.                                                                                   |
| **4. Sync Perizinan Otomatis**  | Saat Guru menyimpan Jurnal hari ini, query `leave_requests` siswa kelas tersebut yang berstatus `Approved` di tanggal hari ini. Masukkan siswa tersebut ke `attendances` dengan status `Izin`/`Sakit` secara otomatis. |
| **5. Dashboard Alert Reminder** | Di Dashboard Guru, query jadwal hari ini. Jika `JAM_SEKARANG > end_time + 30m` DAN jurnal belum terisi, tampilkan komponen `Widget Alert Warning` warna merah.                                                         |

---

## 🎯 LANGKAH SELANJUTNYA UNTUK ANDA:

Langkah 1.4 telah selesai! Langkah selanjutnya adalah **Step 5: Database Seeder Data Awal (Roles, Default Config, Ruangan, & Users)**.



