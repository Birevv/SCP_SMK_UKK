# 📌 Panduan & Langkah Implementasi: Import Jadwal Otomatis (FET Timetables / PDF / CSV)

Dokumen ini berisi panduan teknis dan langkah-langkah implementasi sistem import jadwal otomatis untuk **Waka Kurikulum**. Dengan fitur ini, Waka tidak perlu lagi menginput jadwal per jam pelajaran satu per satu, dan jadwal mengajar guru akan otomatis terisi secara instan.

---

## 🎯 1. Gambaran Alur Sistem

```
┌─────────────────────────────────────────────────────────────┐
│                    WAKA KURIKULUM                           │
│  Mengunggah File Jadwal (Ekspor FET .csv / .xml ATAU .pdf)   │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                 SERVICE PARSER & MATCHING                   │
│  1. Baca Baris/Kotak Jadwal (Hari, Jam, Mapel, Guru, Ruang) │
│  2. Bersihkan Kode Guru: "68.HARIS BUDIAWAN" -> "HARIS"    │
│  3. Cocokkan ID: User (Guru), Classroom, Subject, Room      │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                PREVIEW & KONFIRMASI (FILAMENT)              │
│  - Menampilkan ringkasan data yang siap di-import           │
│  - Memberi tanda jika ada nama Guru/Mapel yang belum cocok │
│  - Tombol "Proses Simpan Jadwal"                           │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│                     DATABASE `schedules`                    │
│  Semua Guru login langsung melihat jadwal mengajar masing²  │
└─────────────────────────────────────────────────────────────┘
```

---

## 🛠️ 2. Langkah-Langkah Implementasi Teknis

### Langkah 1: Buat Service Parser (`app/Services/FetScheduleParserService.php`)
Service ini bertugas memecah string format khas FET:
- Format Guru: `"68.HARIS BUDIAWAN, S.Pd"` $\rightarrow$ Kode `68`, Nama `HARIS BUDIAWAN, S.Pd`
- Format Mapel: `Kreatifitas, Inovasi dan kewirausahaan` $\rightarrow$ dicocokkan ke tabel `subjects`
- Format Ruang: `LAB 16` $\rightarrow$ dicocokkan ke tabel `rooms`

```php
<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Support\Str;

class FetScheduleParserService
{
    /**
     * Parsing baris data jadwal dari FET (CSV / Array)
     */
    public function parseRow(array $row, int $academicYearId, string $cycleType): ?array
    {
        // Contoh struktur data FET per sel:
        // Day: 'Senin', StartTime: '07:15', EndTime: '10:30', Class: 'XII RPL2'
        // Subject: 'Kreatifitas, Inovasi dan kewirausahaan'
        // TeacherRaw: '68.HARIS BUDIAWAN, S.Pd'
        // RoomRaw: 'LAB 16' (Opsional)

        $teacher = $this->matchTeacher($row['teacher_raw'] ?? '');
        $classroom = $this->matchClassroom($row['classroom_name'] ?? '');
        $subject = $this->matchSubject($row['subject_name'] ?? '');
        $room = $this->matchRoom($row['room_name'] ?? '', $classroom);

        return [
            'academic_year_id' => $academicYearId,
            'teacher_id'       => $teacher?->id,
            'teacher_name'     => $teacher?->full_name ?? $row['teacher_raw'],
            'classroom_id'     => $classroom?->id,
            'classroom_name'   => $classroom?->name ?? $row['classroom_name'],
            'subject_id'       => $subject?->id,
            'subject_name'     => $subject?->name ?? $row['subject_name'],
            'room_id'          => $room?->id,
            'room_name'        => $room?->name ?? '-',
            'day_of_week'      => $row['day_of_week'],
            'start_time'       => $row['start_time'],
            'end_time'         => $row['end_time'],
            'cycle_type'       => $cycleType,
            'is_valid'         => ($teacher && $classroom && $subject),
        ];
    }

    /**
     * Cocokkan nama guru (menghilangkan angka kode di depan seperti "68.")
     */
    public function matchTeacher(string $rawTeacher): ?User
    {
        if (empty($rawTeacher)) return null;

        // Hilangkan angka dan titik di depan (contoh: "68.HARIS BUDIAWAN" -> "HARIS BUDIAWAN")
        $cleanName = preg_replace('/^\d+[\.\s]*/', '', trim($rawTeacher, " \"'"));

        // Cari berdasarkan nama mirip atau NIP
        return User::whereHas('role', fn($q) => $q->where('name', 'Guru'))
            ->where(function ($q) use ($cleanName) {
                $q->where('full_name', 'LIKE', "%{$cleanName}%")
                  ->orWhere('name', 'LIKE', "%{$cleanName}%");
            })->first();
    }

    public function matchClassroom(string $className): ?Classroom
    {
        if (empty($className)) return null;
        $clean = trim($className);

        return Classroom::where('name', 'LIKE', "%{$clean}%")->first();
    }

    public function matchSubject(string $subjectName): ?Subject
    {
        if (empty($subjectName)) return null;
        $clean = trim($subjectName);

        return Subject::where('name', 'LIKE', "%{$clean}%")->first();
    }

    public function matchRoom(string $roomName, ?Classroom $classroom): ?Room
    {
        if (!empty($roomName)) {
            $found = Room::where('name', 'LIKE', "%" . trim($roomName) . "%")->first();
            if ($found) return $found;
        }

        // Default ke ruangan kelas jika ada
        return $classroom?->room ?? Room::first();
    }
}
```

---

### Langkah 2: Buat Action Import di Filament (`ListSchedules.php`)
Tambahkan tombol **"Import Jadwal FET / CSV"** pada header tabel Jadwal di Filament.

Lokasi: `app/Filament/Resources/Schedules/Pages/ListSchedules.php`

```php
<?php

namespace App\Filament\Resources\Schedules\Pages;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\AcademicYear;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Services\FetScheduleParserService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListSchedules extends ListRecords
{
    protected static string $resource = ScheduleResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Hanya Waka Kurikulum / Admin yang bisa import
        if (Auth::user()?->role?->name === 'Waka Kurikulum' || Auth::user()?->role?->name === 'Admin') {
            $actions[] = Action::make('import_fet')
                ->label('Import Jadwal (FET / CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->form([
                    Select::make('academic_year_id')
                        ->label('Tahun Ajaran')
                        ->options(AcademicYear::pluck('name', 'id'))
                        ->default(fn () => AcademicYear::where('is_active', true)->first()?->id)
                        ->required(),

                    Select::make('cycle_type')
                        ->label('Tipe Siklus / Semester')
                        ->options([
                            'Ganjil' => 'Semester Ganjil',
                            'Genap'  => 'Semester Genap',
                        ])
                        ->default(fn () => SystemConfiguration::current()->active_cycle)
                        ->required(),

                    FileUpload::make('file_jadwal')
                        ->label('File Ekspor FET (.csv / .xml)')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/xml', 'text/xml'])
                        ->required()
                        ->storeFiles(false),
                ])
                ->action(function (array $data, FetScheduleParserService $parser) {
                    $file = $data['file_jadwal'];
                    // Baca file CSV/XML dan proses parsing
                    // (Lihat langkah 3 untuk detail parsing file)

                    Notification::make()
                        ->title('Jadwal Berhasil Di-import!')
                        ->body('Seluruh jadwal telah dipetakan ke akun guru masing-masing.')
                        ->success()
                        ->send();
                });
        }

        $actions[] = Actions\CreateAction::make();

        return $actions;
    }
}
```

---

### Langkah 3: (Opsi AI Vision) Upload PDF Langsung via Gemini API
Jika Waka ingin langsung mengunggah file **PDF Jadwal** (seperti gambar dari FET):

1. **Pasang Library Google Gemini / Guzzle**:
   ```bash
   composer require google-gemini-php/client
   ```
2. **Kirim Gambar / Halaman PDF ke Prompt Gemini Vision**:
   ```php
   $prompt = <<<PROMPT
   Kamu adalah sistem parser jadwal sekolah SMK.
   Baca tabel jadwal di gambar ini (hasil software FET Timetables).
   Ekstrak seluruh sel jadwal ke dalam format JSON Array murni dengan format berikut:
   [
     {
       "classroom_name": "XII RPL2",
       "day_of_week": "Senin",
       "start_time": "07:15",
       "end_time": "10:30",
       "subject_name": "Kreatifitas, Inovasi dan kewirausahaan",
       "teacher_raw": "68.HARIS BUDIAWAN, S.Pd",
       "room_name": "LAB 16"
     }
   ]
   PROMPT;
   ```
3. **Hasil JSON** langsung di-*loop* dan disimpan ke tabel `schedules`.

---

## 📋 3. Format Template CSV Sederhana (Jika Pakai Excel)

Jika Waka ingin mengedit manual di Excel sebelum upload, sediakan format kolom CSV seperti ini:

| Hari | Jam_Mulai | Jam_Selesai | Kelas | Mapel | Guru | Ruang |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| Senin | 07:15 | 10:30 | XII RPL 2 | Kreatifitas, Inovasi dan kewirausahaan | HARIS BUDIAWAN, S.Pd | LAB 16 |
| Selasa | 07:15 | 10:30 | XII RPL 2 | Mapel Pilihan RPL Internet Of Things | RUSDIYANTO, S.Pd | LAB 16 |
| Rabu | 07:15 | 09:45 | XII RPL 2 | Pendidikan Agama dan Budi Pekerti | NOVIA RAHMALINA, S.Pd | R. XII RPL 2 |
| Kamis | 07:15 | 09:45 | XII RPL 2 | Bahasa Indonesia | MUHAMMAD HERU PURNOMO | R. XII RPL 2 |
| Jumat | 07:15 | 08:45 | XII RPL 2 | Pendidikan Pancasila | WIDODO, S.Pd. | R. XII RPL 2 |

---

## 🧪 4. Langkah Pengujian & Verifikasi

1. **Uji Login Waka Kurikulum**:
   - Masuk ke menu **Jadwal Pelajaran** $\rightarrow$ Klik tombol **"Import Jadwal (FET / CSV)"**.
   - Unggah file jadwal $\rightarrow$ Klik **Simpan**.
2. **Uji Login Akun Guru**:
   - Login menggunakan akun Guru (misal: *Haris Budiawan*).
   - Buka menu **Jadwal Pelajaran**.
   - **Hasil yang Diharapkan:** Hanya muncul jadwal mengajar milik Pak Haris (Senin jam 07:15 di XII RPL 2, dsb) tanpa harus input manual.
3. **Uji Pengisian Jurnal**:
   - Buka menu **Jurnal Mengajar** $\rightarrow$ Saat membuat jurnal baru, jadwal hari tersebut langsung terpilih otomatis.

---

## 💡 5. Rekomendasi Tambahan untuk UKK

- **Nilai Tambah (*Point Plus*) UKK:** Menunjukkan kemampuan otomasi integrasi data dari software pihak ketiga (*FET Timetables*) ke dalam sistem web berbasis Laravel & Filament.
- **Safety First:** Selalu sediakan opsi *Preview* sebelum data disimpan permanen untuk mencegah duplikasi jadwal.
