# 🎨 Dokumen Spesifikasi Desain & Master Prompts UI/UX: Kalender Akademik Interaktif SCP SMK

Dokumen ini berisi spesifikasi antarmuka pengguna (UI), pengalaman pengguna (UX), *design tokens*, anatomi komponen, serta **kumpulan Master Prompt siap pakai** (untuk AI UI generator seperti v0, Figma AI, Claude Artefacts, Lovable, maupun Frontend Designer) guna mewujudkan Kalender Akademik SCP SMK yang modern, fungsional, dan ramah pengguna di desktop maupun mobile.

---

## 📐 1. Design System & Design Tokens Foundation

### A. Palet Warna (Color System - Light & Dark Mode)
Menggunakan standar warna Filament v5 & Tailwind CSS:
* **Background Utama**:
  * Light: `#F8FAFC` (`bg-slate-50`)
  * Dark: `#0F172A` (`bg-slate-900`)
* **Surface / Container Card**:
  * Light: `#FFFFFF` (`bg-white border-slate-200/80 shadow-sm`)
  * Dark: `#1E293B` (`bg-slate-800/90 border-slate-700/80 shadow-md`)
* **Primary / Brand (Navigasi & Elemen Utama)**:
  * Blue: `#2563EB` (`bg-blue-600 text-white hover:bg-blue-700`)
* **Kategori Acara (Event Category Badges & Connected Bars)**:
  * 🔴 **Libur Nasional (`danger`)**:
    * Light: `bg-red-50 text-red-700 border-red-200` | Bar: `bg-red-500 text-white`
    * Dark: `bg-red-950/40 text-red-300 border-red-800` | Bar: `bg-red-600 text-white`
  * 🟠 **Libur Sekolah / Semester (`warning`)**:
    * Light: `bg-amber-50 text-amber-700 border-amber-200` | Bar: `bg-amber-500 text-white`
    * Dark: `bg-amber-950/40 text-amber-300 border-amber-800` | Bar: `bg-amber-600 text-white`
  * 🌹 **Upacara Bendera / Peringatan (`rose`)**:
    * Light: `bg-rose-50 text-rose-700 border-rose-200` | Bar: `bg-rose-600 text-white`
    * Dark: `bg-rose-950/40 text-rose-300 border-rose-800` | Bar: `bg-rose-700 text-white`
  * 🔵 **Ujian / Asesmen (`info`)**:
    * Light: `bg-sky-50 text-sky-700 border-sky-200` | Bar: `bg-sky-600 text-white`
    * Dark: `bg-sky-950/40 text-sky-300 border-sky-800` | Bar: `bg-sky-700 text-white`
  * 🟢 **Praktik Kerja Lapangan / PKL (`teal`)**:
    * Light: `bg-teal-50 text-teal-700 border-teal-200` | Bar: `bg-teal-600 text-white`
    * Dark: `bg-teal-950/40 text-teal-300 border-teal-800` | Bar: `bg-teal-700 text-white`
  * 🌺 **Uji Kompetensi Keahlian / UKK (`rose/violet`)**:
    * Light: `bg-purple-50 text-purple-700 border-purple-200` | Bar: `bg-purple-600 text-white`
    * Dark: `bg-purple-950/40 text-purple-300 border-purple-800` | Bar: `bg-purple-700 text-white`
  * 🟢 **Kegiatan Kesiswaan / OSIS (`success`)**:
    * Light: `bg-emerald-50 text-emerald-700 border-emerald-200` | Bar: `bg-emerald-600 text-white`
    * Dark: `bg-emerald-950/40 text-emerald-300 border-emerald-800` | Bar: `bg-emerald-700 text-white`
  * 🟣 **Rapat Guru / Dinas (`primary`)**:
    * Light: `bg-indigo-50 text-indigo-700 border-indigo-200` | Bar: `bg-indigo-600 text-white`
    * Dark: `bg-indigo-950/40 text-indigo-300 border-indigo-800` | Bar: `bg-indigo-700 text-white`
  * ⚪ **Lainnya (`gray`)**:
    * Light: `bg-slate-100 text-slate-700 border-slate-200` | Bar: `bg-slate-600 text-white`
    * Dark: `bg-slate-800 text-slate-300 border-slate-700` | Bar: `bg-slate-600 text-white`

### B. Tipografi & Hirarki Teks
* **Font Family**: Inter, Plus Jakarta Sans, atau sistem UI font sans-serif.
* **Heading Halaman**: `text-2xl font-bold tracking-tight text-slate-900 dark:text-white`
* **Sub-Heading / Deskripsi**: `text-sm text-slate-500 dark:text-slate-400`
* **Nama Hari (Header Kolom)**: `text-xs font-semibold uppercase tracking-wider text-slate-500`
* **Angka Tanggal**: `text-sm font-semibold` (Hari ini: dibungkus lingkaran biru `w-7 h-7 flex items-center justify-center bg-blue-600 text-white rounded-full font-bold`)
* **Label Event Bar**: `text-[11px] font-medium leading-tight truncate`

---

## 🖥️ 2. Anatomi Komponen Antarmuka (UI Component Anatomy)

```
+---------------------------------------------------------------------------------------------------+
|  Data Master > Kalender Akademik                                                                  |
|  KALENDER AKADEMIK                                                                                |
|  Kelola agenda sekolah, libur resmi, PKL, UKK, dan jadwal semester SMK Negeri 1 Bantul            |
+---------------------------------------------------------------------------------------------------+
|  [ 📅 Kalender Akademik (Active) ]   [ 📋 Kelola Tahun Ajaran ]                                   |
+---------------------------------------------------------------------------------------------------+
|  [ Tahun Ajaran: 2026/2027 Ganjil (Aktif) v ]  [ Tingkat: Semua Tingkat v ]                       |
|  [ < ]  [ Oktober 2026 v ]  [ > ]  [ Hari Ini ]  |  [ 📅 Grid | 📑 List ]                         |
|                                                [ 📥 Tarik Libur Nasional ]  [ + Tambah Agenda ]   |
+---------------------------------------------------------------------------------------------------+
|  SENIN      | SELASA     | RABU       | KAMIS      | JUMAT      | SABTU (Muted)| MINGGU (Muted)|
+-------------+------------+------------+------------+------------+--------------+---------------+
| 28          | 29         | 30         | 1          | 2          | 3            | 4             |
|             |            |            |            | [Rapat]    |              |               |
+-------------+------------+------------+------------+------------+--------------+---------------+
| 5           | 6          | 7          | 8          | 9          | 10           | 11            |
| [================== Ujian Asesmen Sumatif (ASAS) [Kls X-XII] ==================]  |               |
+-------------+------------+------------+------------+------------+--------------+---------------+
| 12          | 13 (TODAY) | 14         | 15         | 16         | 17           | 18            |
|             | (● 13)     |            |            |            | [📢 Upacara] | [🔴 Libur]    |
|             | [+ Tambah] |            |            |            | [Wajib Hadir]|               |
+---------------------------------------------------------------------------------------------------+
|  Legend: 🔴 Libur  🟠 Libur Sekolah  🌹 Upacara  🔵 Ujian  🟢 PKL  🌺 UKK  🟢 Kegiatan  🟣 Rapat |
+---------------------------------------------------------------------------------------------------+
```

---

## 📱 3. Desain Responsif & Tampilan Mobile (Adaptive Hybrid UX)

### A. Tampilan Mobile (`sm` / `md` screens):
1. **Bagian Atas (Header & Date Strip)**:
   - Header ringkas dengan tombol *Prev/Next* bulan dan *Filter Dropdown*.
   - Kalender mini berbentuk *strip/grid ringkas* di mana tanggal yang memiliki agenda ditandai dengan **titik warna (dot indicator)** di bawah angka tanggal.
2. **Bagian Bawah (Vertical Agenda Feed / Timeline Cards)**:
   - Disajikan dalam format kartu list kronologis yang sangat nyaman discroll dengan jempol:
     ```
     +-----------------------------------------------------------+
     | [ 17 ]  HUT Kemerdekaan RI Ke-81                 [📢 UPACARA]
     | [ AGT]  07:00 - 09:30 WIB • Seluruh Siswa & Guru
     |         ⚠️ Wajib Hadir / Presensi Upacara Bendera
     +-----------------------------------------------------------+
     | [ 18 ]  Uji Kompetensi Keahlian (UKK) RPL           [🌺 UKK]
     | [ s/d]  18 s/d 22 Agustus 2026 • Khusus Kelas XII
     | [ 22 ]  KBM Reguler Ditiadakan untuk Kelas XII
     +-----------------------------------------------------------+
     ```
3. **Floating Action Button (FAB)**:
   - Tombol bulat `+` mengambang di pojok kanan bawah bagi Waka Kurikulum untuk menambah acara secara instan.

---

## 📋 4. Master Prompt Siap Pakai (Ready-to-Use UI/UX Prompts)

Gunakan salah satu dari 3 prompt berikut untuk dimasukkan ke AI UI Generator (v0.dev, Lovable, Figma AI, Claude, dsb.):

### 🌟 PROMPT 1: Master Prompt Tampilan Lengkap Halaman Kalender Akademik (Desktop & Responsive)

```markdown
Design a modern, high-end Academic Calendar Web Application UI for a Vocational High School (SMK) admin panel built with Tailwind CSS, React / Blade, and Lucide / Heroicons.

Theme & Aesthetics:
- Clean enterprise SaaS aesthetic (similar to Linear, Raycast, and Filament PHP v3/v5).
- Supports both crisp Light Mode and deep Dark Mode (slate-900 background).
- Border radii: rounded-xl for containers, rounded-lg for cards and buttons.
- Subtle borders (border-slate-200 dark:border-slate-800) and smooth hover transitions.

Layout & Structure:
1. Breadcrumb & Title Header:
   - Breadcrumb: Data Master > Kalender Akademik
   - Heading: "Kalender Akademik" with subtitle: "Kelola agenda sekolah, libur nasional, PKL, UKK, dan jadwal semester aktif SMK Negeri 1 Bantul".
   - Two Main Tabs: Tab 1 "📅 Kalender Akademik" (Active), Tab 2 "📋 Kelola Tahun Ajaran".

2. Top Control & Navigation Bar:
   - Academic Year Filter: Dropdown selector showing "2026/2027 - Ganjil (Aktif)" with green status pill.
   - Grade Audience Filter: Dropdown with options [Semua Tingkat, Kelas X, Kelas XI, Kelas XII].
   - Month & Year Navigator:
     - Left & right arrow buttons [ < ] [ > ].
     - Month dropdown (Januari - Desember) and Year input.
     - "Hari Ini" (Today) quick-jump button.
   - View Mode Switcher: Toggle button between [ 📅 Grid Kalender ] and [ 📑 List Agenda ].
   - Action Buttons:
     - Secondary button with icon: "📥 Tarik Libur Nasional (API Sync)"
     - Primary button (Blue): "+ Tambah Agenda" (triggers create modal).

3. Monthly Calendar Grid (Desktop View):
   - 7 columns: Senin, Selasa, Rabu, Kamis, Jumat, Sabtu, Minggu (Saturday and Sunday have a subtle muted tint).
   - Each cell has a date number. Today's date is highlighted with a blue circle pill.
   - Days from previous and next months are dimmed with muted text opacity.
   - Multi-Day Event Rendering:
     - Multi-day events (e.g., UKK 5 days, Semester Break 2 weeks) span horizontally across days with continuous connected colored bars:
       - Start day has rounded-l-md with category icon and bold title.
       - Middle days have straight flat edges (border-x-0).
       - End day has rounded-r-md.
     - Shows small badge for target audience, e.g., "[Kls XII]" for UKK or "[Kls XI]" for PKL.
     - Upacara event shows a megaphone icon and "[Wajib Hadir]" micro-pill.
   - Hover state on any date cell reveals a subtle "+" icon button to quickly add an event on that date.

4. Event Category Color Coding:
   - Libur Nasional: Red (Danger)
   - Libur Sekolah / Semester: Orange (Warning)
   - Upacara Bendera: Rose / Crimson with megaphone icon
   - Ujian / Asesmen: Blue (Info)
   - Praktik Kerja Lapangan (PKL): Teal (Vibrant teal)
   - Uji Kompetensi Keahlian (UKK): Purple / Rose-violet
   - Kegiatan Siswa / OSIS: Emerald Green
   - Rapat Guru / Dinas: Indigo

5. Mobile Adaptive View:
   - On screens < 768px, display a sleek mini-month date strip with colored dot indicators under dates that have events.
   - Below it, render a vertical Timeline Card Feed of events for that month, displaying the date badge, category tag, audience tag, event title, time, and whether attendance is mandatory.
```

---

### 🌟 PROMPT 2: Master Prompt Modal Dialog Input / Edit Agenda Acara (Waka Kurikulum)

```markdown
Design an elegant, accessible, and intuitive Modal Dialog UI for creating and editing School Calendar Events in an educational web application.

Modal Specifications:
- Container: Centered modal with smooth backdrop-blur (bg-slate-900/50 backdrop-blur-sm). Max width: max-w-xl (576px), rounded-2xl, border-slate-200 dark:border-slate-800, p-6.
- Header:
  - Title: "Tambah Agenda Akademik" (or "Edit Agenda Akademik" with delete icon on top right).
  - Subtitle: "Tentukan jadwal kegiatan, target tingkatan kelas, dan status KBM/kehadiran."
  - Close "X" button.

Form Fields (Two-Column & Full-Width Grid):
1. Judul Agenda (Full Width):
   - Text input with placeholder "Contoh: Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak".
2. Tahun Ajaran (Full Width):
   - Read-only or select dropdown prefilled with active semester e.g., "2026/2027 Ganjil (Juli - Desember 2026)".
3. Kategori Acara (Visual Segmented Cards / Radio Grid):
   - 2x4 grid of clickable cards with icons:
     [🔴 Libur Nasional] [🟠 Libur Sekolah] [🌹 Upacara Bendera] [🔵 Ujian/Asesmen]
     [🟢 PKL] [🌺 UKK] [🟢 Kegiatan Siswa] [🟣 Rapat Dinas]
   - Selected category highlights with colored ring border.
4. Target Tingkat Kelas (target_grades):
   - Label: "Target Tingkat Siswa Terdampak"
   - Helper text: "Kosongkan jika berlaku untuk seluruh sekolah."
   - Segmented checkbox pills: [ Seluruh Sekolah ] [ Kelas X ] [ Kelas XI ] [ Kelas XII ]
   - If PKL is selected, Kelas XI & XII are auto-checked. If UKK is selected, Kelas XII is auto-checked.
5. Operational Switches (Card with 3 Toggle Switches):
   - Switch 1: "Hari Libur Resmi (is_holiday)" -> Helper: Menandai tanggal merah di kalender.
   - Switch 2: "Nonaktifkan KBM Guru (disable_kbm)" -> Helper: Meniadakan jam mengajar & tagihan jurnal guru.
   - Switch 3: "Wajib Hadir / Presensi (requires_attendance)" -> Helper: Siswa tetap wajib absen gerbang/upacara. (Active for Upacara, Inactive for regular holidays).
6. Date & Time Range (2 Columns):
   - Tanggal Mulai (Date Picker) & Tanggal Selesai (Date Picker).
   - "Seharian Penuh (All Day)" toggle switch.
   - Jam Mulai & Jam Selesai (Time Pickers, only shown if All Day is false).
7. Catatan / Keterangan (Textarea):
   - Placeholder "Catatan tambahan, seragam khusus upacara, atau ruang pelaksanaan...".

Footer Actions:
- Left: "Hapus Agenda" button (Red text/ghost button, only visible in Edit mode).
- Right: "Batal" (Ghost button) and "Simpan Agenda" (Primary Blue solid button with save icon).
```

---

### 🌟 PROMPT 3: Master Prompt Modal Sinkronisasi Libur Nasional (API Sync)

```markdown
Design a streamlined, confidence-inspiring Confirmation & Progress Modal UI for "Sinkronisasi Hari Libur Nasional Otomatis (API Sync)" in a school administration system.

Modal Specifications:
- Container: Clean modal, max-w-lg, rounded-2xl, centered with subtle drop shadow.
- Header:
  - Icon: Circular badge with a download/calendar icon with blue gradient.
  - Title: "Tarik Libur Nasional Indonesia (API SKB 3 Menteri)"
  - Subtitle: "Sinkronisasi otomatis seluruh tanggal merah & cuti bersama resmi pemerintah."

Content Body:
1. Target Semester & Year Card:
   - Info callout card displaying:
     - Tahun Kalender: "2026"
     - Semester Terpilih: "2026/2027 Ganjil (1 Juli 2026 s/d 31 Desember 2026)"
     - Filter Status: "Sistem otomatis menyaring tanggal merah yang berada di dalam rentang semester ini saja."
2. Preview / Features Checklist:
   - 🟢 Otomatis memasukkan hari raya & cuti bersama tanpa input manual.
   - 🟢 Otomatis mendeteksi 17 Agustus sebagai UPACARA dengan kewajiban presensi aktif.
   - 🟢 Bebas duplikasi data (tidak akan menimpa agenda manual yang sudah ada).
3. Fallback Status Indicator:
   - "Koneksi API: api-hari-libur.vercel.app [Online] • Fallback Offline Preset: Tersedia"

Modal Footer:
- Left: "Batal" (Secondary outlined button).
- Right: "Mulai Sinkronisasi Sekarang" (Primary blue button with sync/refresh icon).
- Progress / Success State:
  - When running: Shows a progress bar / animated spinner.
  - When completed: Green checkmark badge with text: "Berhasil menambahkan 14 hari libur nasional ke kalender semester aktif." and a "Tutup" button.
```

---

## 🛠️ 5. Cara Menggunakan Prompt Ini

1. **Untuk Frontend / Fullstack Developer**: Gunakan **PROMPT 1** dan **PROMPT 2** untuk membuat komponen Blade / Livewire atau React dengan Tailwind CSS secara langsung.
2. **Untuk Desain Figma / AI Tools (v0.dev / Claude Artefact / Lovable)**: Cukup salin (*copy-paste*) blok teks prompt di atas ke dalam kolom prompt v0.dev atau Claude 3.7 Sonnet untuk langsung menghasilkan kode UI interaktif yang 100% presisi.
3. **Penyimpanan**: Dokumen ini telah disimpan di dalam folder project `Instruction/prompt_ui_ux_kalender_akademik.md` sebagai panduan frontend resmi tim pengembang.

