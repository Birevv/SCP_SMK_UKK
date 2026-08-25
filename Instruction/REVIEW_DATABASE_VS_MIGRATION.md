# Review Struktur Database: SQL Lama (`db_skansaba`) vs Migration Laravel Saat Ini

Berdasarkan perbandingan antara struktur database pada file SQL lama (`db_skansaba (2).sql`) dan struktur migration Laravel yang saat ini digunakan, terdapat perubahan paradigma yang cukup signifikan ke arah yang lebih baik dan modern.

Migration yang digunakan saat ini mengadopsi struktur yang lebih **sederhana, terpusat (dinormalisasi), dan mengikuti standar konvensi Laravel** (menggunakan bahasa Inggris dan penamaan plural).

Berikut adalah analisis perbedaan, review, dan rekomendasi terkait apakah ada yang perlu diganti, ditambah, atau dikurangi:

---

## 1. Entitas Pengguna (Siswa, Guru, Orang Tua)

- **SQL Lama:** Memisahkan data ke dalam tabel `users`, `guru`, `siswa`, `orang_tua`, dan `ortu_siswa`. Pendekatan ini membuat relasi untuk proses autentikasi (login) dan pencarian data menjadi lebih terfragmentasi dan rumit.
- **Migration Saat Ini:**  Menggabungkan semua aktor ke dalam satu tabel utama `users` dengan pembeda berupa `role_id` dan `classroom_id`.
- **Review & Rekomendasi:** Pendekatan pada migration saat ini **sudah sangat tepat**. Menggunakan satu tabel `users` memudahkan integrasi dengan fitur autentikasi bawaan Laravel.
- **Tindakan (Perlu Ditambahkan):** Di tabel `users` migration, belum terdapat kolom untuk nomor identitas spesifik yang biasanya digunakan di sekolah. Direkomendasikan untuk menambahkan:
  - `nisn` (string, unik, *nullable* - diisi jika rolenya Siswa).
  - `nip` (string, unik, *nullable* - diisi jika rolenya Guru).
  - `phone_number` (string, *nullable* - untuk menggantikan tabel orang tua / nomor HP pengguna).

## 2. Data Master Akademik (Mata Pelajaran, Jurusan, Tahun Ajaran)

- **SQL Lama:** Memiliki tabel spesifik untuk `mata_pelajaran`, `jurusan`, dan `tahun_ajaran`.
- **Migration Saat Ini:** 
  - `mata_pelajaran` tidak memiliki tabel khusus, melainkan hanya berupa field string `subject_name` di tabel `schedules`.
  - `jurusan` dilebur ke dalam nama kelas di tabel `classrooms`.
  - `tahun_ajaran` (Semester) **tidak ada sama sekali**.
- **Review & Rekomendasi:** 
  - Tidak adanya tabel mata pelajaran dan jurusan membuat aplikasi lebih simpel. Hal ini bisa diterima selama tidak ada logika khusus yang membutuhkan relasi ke entitas tersebut. Namun, jika nama mapel diketik manual, sangat rentan terjadi inkonsistensi data (typo).
  - **Hilangnya `tahun_ajaran` adalah isu kritikal.** Dalam sistem manajemen sekolah, data presensi dan jadwal *harus mutlak* bisa difilter berdasarkan semester/tahun ajaran. Tanpa tabel ini, data presensi tahun ini dan tahun-tahun berikutnya akan tercampur dan sulit difilter.
- **Tindakan (Perlu Ditambahkan):**
  - **Wajib:** Buat file migration baru untuk tabel `academic_years` (Tahun Ajaran), yang memiliki penanda semester aktif (`is_active`).
  - **Wajib:** Tambahkan `academic_year_id` sebagai *foreign key* di tabel `schedules`.
  - **Opsional:** Buat tabel `subjects` (Mata Pelajaran) untuk standardisasi input jadwal, menghindari input text bebas.

## 3. Penjadwalan (Jadwal Pelajaran)

- **SQL Lama:** Tabel `jadwal_pelajaran` menggunakan index jam pelajaran (`jam_ke_mulai`, `jam_ke_selesai`).
- **Migration Saat Ini:** Tabel `schedules` menggunakan waktu aktual (`start_time`, `end_time`) dan sistem rotasi/siklus (`cycle_type`).
- **Review & Rekomendasi:** Pendekatan pada migration saat ini **jauh lebih fleksibel** karena bisa mengakomodasi jam riil secara presisi dan sistem rotasi jadwal, tidak sekadar mengandalkan "Jam ke-1". Ini adalah peningkatan yang sangat baik dan **tidak perlu diubah**.

## 4. Presensi dan Jurnal Harian

- **SQL Lama:** Menggunakan konsep `presensi_header` (data kelas/pertemuan harian) dan `presensi_detail` (status kehadiran masing-masing siswa).
- **Migration Saat Ini:** Menggunakan nama yang lebih standar, yaitu `journals` (sebagai header/pertemuan) dan `attendances` (sebagai detail siswa). Diperkaya dengan fitur dokumentasi operasional (`teaching_material`, `activity_photo`).
- **Review & Rekomendasi:** Konsep di migration ini **sangat bagus dan komprehensif** untuk kebutuhan administrasi sekolah modern (mendukung jurnal mengajar guru terintegrasi dengan foto bukti). **Tidak ada yang perlu dikurangi.**

## 5. Fitur Baru Tambahan di Migration

- **Migration Saat Ini:** Menghadirkan tabel `leave_requests` (untuk pengajuan izin siswa) dan `system_configurations`.
- **Review:** Ini adalah fitur esensial yang sangat berguna, yang sebelumnya tidak terpikirkan di desain SQL lama. Ini memfasilitasi proses bisnis pengajuan izin secara terpusat dan digital.

---

## Kesimpulan & Keputusan Akhir

Desain struktur database berbasis migration Laravel yang digunakan saat ini **sudah jauh lebih matang, modern, fleksibel, dan solid** dibandingkan struktur statis pada file SQL lama. **TIDAK DISARANKAN** untuk mundur dan menggunakan desain SQL lama, karena itu berarti membuang optimasi yang sudah berjalan.

**Langkah Eksekusi (To-Do List) untuk menyempurnakan struktur Migration saat ini:**

1. [ ] **Tabel Academic Years:** Buat tabel `academic_years` (berisi field nama semester/tahun, dan status `is_active`). Relasikan ke tabel `schedules`.
2. [ ] **Update Tabel Users:** Tambahkan kolom pendukung identitas: `nisn` (opsional unik), `nip` (opsional unik), dan `phone` (opsional).
3. [ ] **(Opsional) Tabel Subjects:** Buat tabel `subjects` agar input nama mata pelajaran bersifat *dropdown list* relasional, bukan *free text*.
