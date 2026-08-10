# 🏫 School Class Progress (SCP) - UKK 2026

![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Filament](https://img.shields.io/badge/Filament-v4.x-FDAE4B?style=for-the-badge&logo=filament&logoColor=black)
![PHPUnit](https://img.shields.io/badge/PHPUnit-Tested-blue?style=for-the-badge&logo=php)

Sistem Informasi Pengelolaan Presensi, Jurnal Mengajar Guru, dan Perizinan Siswa berbasis **Mobile-First Design** untuk Sekolah Menengah Kejuruan (SMK) dengan dukungan **Jadwal Blok 2-Mingguan (Putaran 1 & Putaran 2)**.

Proyek ini dibangun khusus untuk memenuhi kualifikasi nilai tertinggi pada **Uji Kompetensi Keahlian (UKK) Tahun Ajaran 2026/2027 Paket 3 Rekayasa Perangkat Lunak (RPL)**.

---

## 🎯 5 LOGIKA BISNIS UTAMA (KUNCI KELULUSAN UKK)

1. **Anti-Tabrakan Waktu (Conflict Prevention):**  
   Validasi otomatis saat input jadwal mengajar guru (`Schedule`). Sistem menolak jika terjadi bentrok irisan jam (`start_time < new_end AND end_time > new_start`) pada hari, putaran, dan (ruang OR kelas OR guru) yang sama.
2. **Saklar Putaran Blok (Cycle Toggle):**  
   Panel Waka Kurikulum untuk mengatur status siklus aktif (`Putaran 1` / `Putaran 2`). Seluruh query jadwal dan antarmuka operasional guru menyesuaikan secara global.
3. **Mekanisme Kunci Jadwal (Lock Schedule):**  
   Fitur *Lock* dari Waka Kurikulum untuk memproteksi form pembuatan, pengubahan, dan penghapusan jadwal guru baik di UI maupun di backend policy.
4. **Alur Sync Perizinan Absensi Otomatis:**  
   Pengajuan surat izin siswa/ortu berstatus `Approved` otomatis mensinkronkan status presensi (`Sakit`/`Izin`) saat Guru mengisi Jurnal Mengajar harian.
5. **Dashboard Alert Reminder:**  
   Indikator visual peringatan keterlambatan pengisian jurnal di dashboard jika jadwal mengajar guru sudah selesai (>30 menit) namun jurnal belum terisi.

---

## 👥 AKUN HAK AKSES DEFAULT (RBAC 6 ROLES)

Seluruh akun default telah diseed dan dapat digunakan langsung untuk pengujian:

| Role | Username | Password | Deskripsi Akses |
| --- | --- | --- | --- |
| **Waka Kurikulum** | `waka` | `password` | Master Data, Lock Schedule, Cycle Toggle, Laporan Presensi |
| **Guru** | `guru1` | `password` | Input Jadwal Mandiri, Input Jurnal + Foto Realtime, Alert Reminder |
| **Wali Kelas** | `walikelas1` | `password` | Verifikasi/Approve Surat Izin, Laporan Rekap Presensi Siswa |
| **Guru BK** | `gurubk` | `password` | Verifikasi/Approve Surat Izin, Laporan Rekap Presensi Siswa |
| **Siswa** | `siswa1` | `password` | Pengajuan Surat Izin (+Upload Bukti), Widget Kehadiran Siswa |
| **Orang Tua** | `ortu1` | `password` | Pengajuan Surat Izin Anak (+Upload Bukti), Widget Kehadiran Anak |

---

## 🛠️ PANDUAN INSTALASI & MENJALANKAN PROYEK

### 1. Clone & Install Dependencies
```bash
composer install
npm install
```

### 2. Konfigurasi Environment & Migration + Seeder
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
```

### 3. Jalankan Server Lokal
```bash
php artisan serve
npm run dev
```
Akses panel admin di browser: `http://localhost:8000/admin`

---

## 🧪 AUTOMATED FEATURE TESTING SUITE

Proyek ini dilengkapi dengan *unit test / feature test* otomatis untuk memverifikasi kebenaran 5 Logika Bisnis Utama:

```bash
php artisan test
```

Hasil pengujian otomatis: **5 Passed / 7 Assertions (100% Success)**.

---

## 📄 MODUL LAPORAN & DOKUMENTASI STEP-BY-STEP

Dokumen petunjuk pengembangan lengkap per tahap tersimpan pada direktori `Instruction/`:
- [ANALISIS_FITUR_UKK_SCP.md](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/Instruction/ANALISIS_FITUR_UKK_SCP.md)
- [LANGKAH_STEP_8_FILAMENT_RESOURCES.md](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/Instruction/LANGKAH_STEP_8_FILAMENT_RESOURCES.md)
- [LANGKAH_STEP_9_PRESENSI_WIDGETS_TESTING.md](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/Instruction/LANGKAH_STEP_9_PRESENSI_WIDGETS_TESTING.md)
- [LANGKAH_STEP_10_LAPORAN_EXPORT_DAN_DOKUMENTASI_FINAL.md](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/Instruction/LANGKAH_STEP_10_LAPORAN_EXPORT_DAN_DOKUMENTASI_FINAL.md)
- [LANGKAH_STEP_11_PENGUJIAN_FINAL_DEMO_DAN_PENILAIAN_UKK.md](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/Instruction/LANGKAH_STEP_11_PENGUJIAN_FINAL_DEMO_DAN_PENILAIAN_UKK.md)
- [LANGKAH_STEP_12_REST_API_MOBILE.md](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/Instruction/LANGKAH_STEP_12_REST_API_MOBILE.md)
