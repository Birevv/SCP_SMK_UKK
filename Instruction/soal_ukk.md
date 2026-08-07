# UJI KOMPETENSI KEAHLIAN

# TAHUN AJARAN 2026/2027

## SOAL PRAKTIK KEJURUAN

- **Satuan Pendidikan	:** Sekolah Menengah Kejuruan (SMK)
- **Kompetensi Keahlian	:** Rekayasa Perangkat Lunak
- **Kode Kompetensi	:** Paket 3 RPL-2026
- **Alokasi Waktu		:** 16 - 24 Jam
- **Bentuk Penilaian	:** Penugasan Perorangan
- **Judul Tugas		:** **School Class Progress (**Presesnis dan Jurnal Sekolah**)**

## I. PETUNJUK UMUM

1. Periksalah dengan teliti seluruh dokumen spesifikasi kebutuhan sistem SCP, aturan bisnis, dan skema basis data sebelum mulai menulis kode program.
2. Gunakan perangkat lunak yang bersifat open-source atau berlisensi resmi sesuai dengan prosedur kerja standar di laboratorium.
3. Terapkan prinsip Keselamatan dan Kesehatan Kerja (K3) serta kerapian manajemen berkas proyek (Clean Code & Standard Directory Structure) secara konsisten.
4. Lakukan penyimpanan repositori kode (commit) secara berkala dengan pesan yang deskriptif guna mempermudah pelacakan audit trail.
5. Pastikan logika backend, validasi bentrok jadwal, sinkronisasi alur perizinan, dan manajemen siklus blok diimplementasikan secara mutlak.

## II. DAFTAR PERALATAN DAN PERANGKAT KERJA

**No**

**Nama Alat/Perangkat**

**Spesifikasi Minimal**

**Keterangan**

1

Komputer Workstation

CPU Core i3 / setara, RAM 4 GB (8 GB direkomendasikan), Storage Bebas

1 Unit / Siswa

2

Sistem Operasi

Windows / Linux Desktop

1 OS / PC

3

Perangkat Lunak Pendukung

VS Code / IDE Pilihan, XAMPP / Laragon / Docker environment

Secukupnya

4

Database Management System

MySQL / MariaDB / PostgreSQL

Secukupnya

5

Koneksi Internet

Minimal 2 Mbps (untuk sinkronisasi package & library)

Stabil

## III. SOAL / TUGAS PROYEK

Anda diminta untuk membangun sistem School Class Progress (SCP), sebuah platform pengelolaan presensi, jurnal mengajar guru, dan perizinan siswa yang dirancang khusus untuk mendukung pembelajaran berbasis Teaching Factory dengan siklus jadwal blok 2 (dua) mingguan. Aplikasi dirancang dengan pendekatan Mobile-First Design agar optimal saat diakses oleh guru di kelas maupun orang tua di rumah.

### 1. Hak Akses Pengguna (Role-Based Access Control)

Untuk mengefisiensikan waktu pengerjaan ujian, arsitektur UI sistem disederhanakan menjadi 4 kelompok antarmuka utama dari 6 role yang ada:

1. **Waka Kurikulum**

Mengelola master data, menentukan siklus blok aktif (Putaran 1/2), melakukan 'Lock Schedule', dan menerima notifikasi rekap guru yang belum mengisi jurnal.

1. **Guru**

Menginput rencana jadwal mengajar secara mandiri (sesuai SK), mengisi presensi & jurnal real-time dengan bukti foto, dan menerima notifikasi peringatan di dashboard.

1. **Tim Kesiswaan (Wali Kelas & Guru BK)**

Memantau rekap absensi dan melakukan verifikasi persetujuan pengajuan izin/sakit. (Keduanya berbagi tampilan antarmuka yang serupa).

1. **Siswa & Orang Tua**

Menggunakan portal login yang sama untuk melihat rekap kehadiran dan mengajukan surat izin (dilengkapi fitur unggah bukti surat dokter/resmi).\_\_ \_\_

### 2. Matriks Fitur Utama Sistem

Fitur Utama Sistem

Siswa/Ortu

2. Guru

Wali Kelas & Guru BK

Waka

Login, Logout, Update Profil

✔

✔

✔

✔

Input Jadwal (Cek Tabrakan Waktu/Ruang)

\-

✔

\-

\-

Input Jurnal & Presensi (+Upload Foto Realtime)

\-

✔

\-

\-

Pengajuan Izin (+Upload Dokumen)

✔

\-

\-

\-

Verifikasi/Approve Izin

\-

\-

✔

\-

Dashboard Alert: Reminder Jurnal (>30mnt)

\-

✔

\-

\-

Lock Schedule & Toggle Siklus Blok

\-

\-

\-

✔

### 3. Spesifikasi Aturan Bisnis & Algoritma (Kunci Kelulusan)

Aplikasi Anda akan dinyatakan gagal/tidak valid jika tidak mengimplementasikan lima logika bisnis inti di bawah ini:

1. Logika Validasi Tabrakan Waktu (Conflict Prevention)

Saat Guru menginput jadwal mengajarnya pada putaran tertentu, sistem harus otomatis mendeteksi dan menolak (menampilkan error) jika terdapat irisan waktu, ruang, atau kelas dengan jadwal yang sudah diinput oleh guru lain pada siklus putaran dan hari yang sama. (Petunjuk Rumus Query: Rentang A bentrok dengan Rentang B jika \`Start\_A < End\_B AND End\_A > Start\_B\`).

2. Sistem Blok & Saklar Putaran (Cycle Toggle)

Waka Kurikulum memiliki panel untuk mengatur status minggu ini berada pada 'Putaran 1' atau 'Putaran 2'. Saat status diubah, seluruh dashboard operasional guru dan antarmuka pencarian jadwal akan menyesuaikan secara global mengikuti putaran aktif tersebut.

3. Mekanisme Kunci Jadwal (Lock Schedule)

Setelah masa pengisian jadwal selesai, Waka Kurikulum berhak menekan tombol 'Lock'. Setelah dikunci, Guru tidak lagi memiliki hak akses untuk mengedit, menghapus, atau menambah jadwal secara mandiri (tombol aksi disembunyikan dan endpoint API dilindungi).

4. Alur Verifikasi Perizinan Absensi (Sync Logic)

Siswa/Orang tua mengajukan izin (mengunggah bukti dokumen). Status awal adalah Pending. Saat Tim Kesiswaan mengubah status menjadi 'Approved', data dicatat. Presensi pada tabel \`attendances\` baru akan disinkronisasi/diisi otomatis dengan status 'Izin/Sakit' ketika Guru Mata Pelajaran membuat Jurnal dan membuka kelas pada hari tersebut.

5. Dashboard Alert Reminder

Sistem memindai jadwal guru hari ini saat halaman dashboard dimuat. Jika terdapat jadwal yang sudah selesai (melewati end\_time lebih dari 30 menit) namun Guru tersebut belum mengisi tabel journals, maka munculkan peringatan atau notifikasi visual di dashboard. (Tidak menggunakan Cron Job).

## IV. GAMBAR KERJA & SKEMA BASIS DATA (DATABASE SCHEMA)

Struktur database wajib mengacu pada Blueprint berikut ini agar logika Conflict Prevention dan Sinkronisasi Izin dapat berjalan dengan baik.

![Text document](asset:sha256:5fa826cc6a023ddbf290ed80901f70d2ee0d02ef15f7f3a654733f90313d18b2)

*system_configurations
leave_requests
id β
int
id D
int
active_cycle
enum NN
student_id o
int NN
is_schedule_locked
boolean NN
applicant_id @
int NN
updated_by@
int NN
verified_by @
int
V
start_date
date NN
roles
users
end_date
date NN
id D
int +
id D
int
.
reason
text NN
role_name@
varchar(50)NN
role_id o
int NN
document_path
varchar(255) NN
parent_id @
int>
status_approval
enum NN
class_id @
int
/
classes
username 咖
varchar(50) NN
id O
int
O
attendances
+
password
varchar(255) NN
varchar(50) NN
idD
int
class_name @
fulliname
varchar(100)NN
journal_id o
int NN
V
created_at
timestamp
student_id o
int NN
《
rooms
status
enum NN
id O
int +
schedules
date
date NN
room_name @
varchar(50)NN
id D
int
teacher_id @
intNN
W
class_id
int NN
journals
room_id @
int NN
idD
int
subject
varchar(100)NN
schedule_id o
intNN
day_of_week
enum NN
date
date NN
start_time
time NN
topic_description
text NN
end_time
timeNN
photo_path
varchar(255)NN
cycle_type
enum NN
created_at
timestamp
dbdiagram.io*

## V. INSTRUMEN & INDIKATOR PENILAIAN (RUBRIK EVALUASI)

Evaluasi kelulusan didasarkan pada total akumulasi skor dari empat komponen penilaian berikut:

No

Komponen Penilaian

Indikator Keberhasilan / Kriteria Penilaian

Skor Maks

1

**Persiapan Kerja (10%)**

- Mampu mengonfigurasi local web server, database, runtime env.
- Menyiapkan struktur folder rapi & inisialisasi manajemen dependensi/Git repositori.

10

2

**Proses & Kualitas Coding (40%)**

- **Anti-Tabrakan Waktu:** Sistem sukses memblokir input jadwal jika bentrok (Ruang, Kelas, Waktu).
- **Lock Schedule:** Waka dapat mengunci form jadwal dengan aman.
- **Workflow Sinkronisasi Perizinan:** Data presensi di database otomatis selaras ketika Izin disetujui & jurnal dibuat.

40

3

**Hasil Produk / UI-UX (30%)**

- **Modul Putaran Blok:** Filter Putaran 1 & 2 bekerja merubah tampilan global.
- **Form Jurnal & Photo:** Validasi unggah file (ekstensi, batas ukuran) berjalan baik.
- **Dashboard Alert:** Terdapat indikator visual ketika guru terlambat membuat jurnal.

30

4

**Sikap & Waktu (20%)**

- Kemandirian dalam pemecahan masalah (debugging log).
- Menyelesaikan seluruh alur fitur tepat waktu sesuai jam yang ditentukan.

20

**"SELAMAT & SUKSES"**

| No | Nama Alat/Perangkat | Spesifikasi Minimal | Keterangan |
| --- | --- | --- | --- |
| 1 | Komputer Workstation | CPU Core i3 / setara, RAM 4 GB (8 GB direkomendasikan), Storage Bebas | 1 Unit / Siswa |
| 2 | Sistem Operasi | Windows / Linux Desktop | 1 OS / PC |
| 3 | Perangkat Lunak Pendukung | VS Code / IDE Pilihan, XAMPP / Laragon / Docker environment | Secukupnya |
| 4 | Database Management System | MySQL / MariaDB / PostgreSQL | Secukupnya |
| 5 | Koneksi Internet | Minimal 2 Mbps (untuk sinkronisasi package & library) | Stabil |

| Fitur Utama Sistem | Siswa/Ortu | Guru | Wali Kelas & Guru BK | Waka |
| --- | --- | --- | --- | --- |
| Login, Logout, Update Profil | ✔ | ✔ | ✔ | ✔ |
| Input Jadwal (Cek Tabrakan Waktu/Ruang) | - | ✔ | - | - |
| Input Jurnal & Presensi (+Upload Foto Realtime) | - | ✔ | - | - |
| Pengajuan Izin (+Upload Dokumen) | ✔ | - | - | - |
| Verifikasi/Approve Izin | - | - | ✔ | - |
| Dashboard Alert: Reminder Jurnal (>30mnt) | - | ✔ | - | - |
| Lock Schedule & Toggle Siklus Blok | - | - | - | ✔ |

| No | Komponen Penilaian | Indikator Keberhasilan / Kriteria Penilaian | Skor Maks |
| --- | --- | --- | --- |
| 1 | Persiapan Kerja (10%) | Mampu mengonfigurasi local web server, database, runtime env.<br><br>Menyiapkan struktur folder rapi & inisialisasi manajemen dependensi/Git repositori. | 10 |
| 2 | Proses & Kualitas Coding (40%) | Anti-Tabrakan Waktu: Sistem sukses memblokir input jadwal jika bentrok (Ruang, Kelas, Waktu).<br><br>Lock Schedule: Waka dapat mengunci form jadwal dengan aman.<br><br>Workflow Sinkronisasi Perizinan: Data presensi di database otomatis selaras ketika Izin disetujui & jurnal dibuat. | 40 |
| 3 | Hasil Produk / UI-UX (30%) | Modul Putaran Blok: Filter Putaran 1 & 2 bekerja merubah tampilan global.<br><br>Form Jurnal & Photo: Validasi unggah file (ekstensi, batas ukuran) berjalan baik.<br><br>Dashboard Alert: Terdapat indikator visual ketika guru terlambat membuat jurnal. | 30 |
| 4 | Sikap & Waktu (20%) | Kemandirian dalam pemecahan masalah (debugging log).<br><br>Menyelesaikan seluruh alur fitur tepat waktu sesuai jam yang ditentukan. | 20 |