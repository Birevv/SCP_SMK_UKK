# PANDUAN PENGEMBANGAN: STEP 7 — IMPLEMENTASI 5 LOGIKA BISNIS UTAMA (KUNCI KELULUSAN UKK)

> **Sistem:** School Class Progress (SCP) - UKK 2026  
> **Modul:** Business Logic & Validation Engine

---

## 🎯 TUJUAN STEP 7

Mengimplementasikan 5 Logika Bisnis Utama yang menjadi **kriteria penilaian kunci dan penentu kelulusan UKK 2026**:

1. **Anti-Tabrakan Waktu (Conflict Prevention)** — Memastikan tidak ada bentrok Jam + Hari + Putaran untuk Ruangan, Kelas, maupun Guru.
2. **Toggle Siklus Blok (Cycle Toggle)** — Filter otomatis Jadwal berdasarkan Putaran Aktif yang diset oleh Waka.
3. **Lock Schedule (Proteksi Kunci Jadwal)** — Penguncian aksi Tambah/Edit/Hapus Jadwal jika opsi dikunci oleh Waka.
4. **Sync Perizinan Otomatis (Auto-Attendance Sync)** — Pengisian otomatis status `Izin`/`Sakit` pada presensi ketika Jurnal disimpan.
5. **Dashboard Alert Reminder** — Peringatan merah bagi Guru jika belum mengisi Jurnal > 30 menit setelah jam pelajaran usai

     ---

## 🛠️ BEDAH 5 LOGIKA BISNIS & IMPLEMENTASI KODE

---

### 📍 LOGIKA 1: ANTI-TABRAKAN WAKTU (CONFLICT PREVENTION)

#### Penjelasan:
Sebelum data jadwal disave, sistem mengecek irisan waktu `(start_time < new_end AND end_time > new_start)` pada `day_of_week` dan `cycle_type` yang sama. Bentrok terjadi jika:
- Ruangan sama (`room_id`)
- Kelas sama (`classroom_id`)
- Guru sama (`teacher_id`)

#### Implementasi Rule Validation (`app/Rules/NoScheduleConflict.php`):

```bash
php artisan make:rule NoScheduleConflict
```

Kode `app/Rules/NoScheduleConflict.php`:

```php
<?php

namespace App\Rules;

use App\Models\Schedule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoScheduleConflict implements ValidationRule
{
    public function __construct(
        protected string $dayOfWeek,
        protected string $startTime,
        protected string $endTime,
        protected string $cycleType,
        protected ?int $classroomId = null,
        protected ?int $roomId = null,
        protected ?int $teacherId = null,
        protected ?int $ignoreId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Schedule::where('day_of_week', $this->dayOfWeek)
            ->where('cycle_type', $this->cycleType)
            ->where(function ($q) {
                $q->where('start_time', '<', $this->endTime)
                  ->where('end_time', '>', $this->startTime);
            });

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        $conflict = $query->where(function ($q) {
            $q->where('room_id', $this->roomId)
              ->orWhere('classroom_id', $this->classroomId)
              ->orWhere('teacher_id', $this->teacherId);
        })->first();

        if ($conflict) {
            if ($conflict->room_id == $this->roomId) {
                $fail("Ruangan sudah digunakan oleh mata pelajaran {$conflict->subject_name} pada jam tersebut.");
            } elseif ($conflict->classroom_id == $this->classroomId) {
                $fail("Kelas tersebut sudah memiliki jadwal mata pelajaran {$conflict->subject_name} pada jam tersebut.");
            } elseif ($conflict->teacher_id == $this->teacherId) {
                $fail("Guru tersebut sudah mengajar mata pelajaran {$conflict->subject_name} di kelas lain pada jam tersebut.");
            }
        }
    }
}
```

---

### 📍 LOGIKA 2: TOGGLE SIKLUS BLOK (CYCLE TOGGLE)

#### Penjelasan:
Waka Kurikulum memilih `active_cycle` ('Putaran 1' atau 'Putaran 2') pada modul Konfigurasi Sistem. Seluruh query Jadwal untuk Guru/Siswa akan otomatis ter-filter sesuai putaran yang aktif.

#### Implementasi Scope / Helper Query:

Pada Model `Schedule.php`:

```php
public function scopeForActiveCycle($query)
{
    $activeCycle = \App\Models\SystemConfiguration::first()?->active_cycle ?? 'Putaran 1';
    return $query->where('cycle_type', $activeCycle);
}
```

---

### 📍 LOGIKA 3: MEKANISME KUNCI JADWAL (LOCK SCHEDULE)

#### Penjelasan:
Jika `is_schedule_locked` bernilai `true` pada `system_configurations`, maka tombol Create, Edit, dan Delete pada `ScheduleResource` disembunyikan dan di-block oleh Policy.

#### Implementasi Policy (`app/Policies/SchedulePolicy.php`):

```bash
php artisan make:policy SchedulePolicy --model=Schedule
```

Kode `app/Policies/SchedulePolicy.php`:

```php
<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;

class SchedulePolicy
{
    private function isLocked(): bool
    {
        return (bool) SystemConfiguration::first()?->is_schedule_locked;
    }

    public function create(User $user): bool
    {
        return !$this->isLocked();
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return !$this->isLocked();
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return !$this->isLocked();
    }
}
```

---

### 📍 LOGIKA 4: SINKRONISASI PERIZINAN OTOMATIS (AUTO-ATTENDANCE SYNC)

#### Penjelasan:
Saat Guru menyimpan **Jurnal Mengajar** hari ini, sistem secara otomatis mengecek tabel `leave_requests` untuk siswa di kelas tersebut yang memiliki surat izin berstatus `Approved` pada tanggal jurnal. Siswa tersebut otomatis dimasukkan ke tabel `attendances` dengan status `'Izin'` atau `'Sakit'`.

#### Implementasi Observer / Event Service (`app/Observers/JournalObserver.php`):

```bash
php artisan make:observer JournalObserver --model=Journal
```

Kode `app/Observers/JournalObserver.php`:

```php
<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\User;

class JournalObserver
{
    public function created(Journal $journal): void
    {
        $classroomId = $journal->schedule?->classroom_id;
        if (!$classroomId) return;

        // Ambil seluruh siswa di kelas tersebut
        $students = User::where('classroom_id', $classroomId)->get();

        foreach ($students as $student) {
            // Cek apakah ada perizinan yang disetujui untuk tanggal jurnal ini
            $approvedLeave = LeaveRequest::where('student_id', $student->id)
                ->where('status', 'Approved')
                ->whereDate('start_date', '<=', $journal->date)
                ->whereDate('end_date', '>=', $journal->date)
                ->first();

            if ($approvedLeave) {
                Attendance::updateOrCreate(
                    [
                        'journal_id' => $journal->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'status' => $approvedLeave->reason_type, // 'Sakit' atau 'Izin'
                        'notes' => 'Otomatis tersinkronisasi dari Pengajuan Izin',
                    ]
                );
            }
        }
    }
}
```

Daftarkan Observer di `app/Providers/AppServiceProvider.php`:

```php
use App\Models\Journal;
use App\Observers\JournalObserver;

public function boot(): void
{
    Journal::observe(JournalObserver::class);
}
```

---

### 📍 LOGIKA 5: DASHBOARD ALERT REMINDER UNTUK GURU

#### Penjelasan:
Jika jam saat ini sudah melebihi **`end_time + 30 menit`** dari jadwal hari ini, dan Guru belum mengisi Jurnal untuk jadwal tersebut, maka Widget Dashboard Guru akan menampilkan kartu peringatan (Alert Warning) berwarna merah.

#### Implementasi Query Alert:

```php
$today = now()->locale('id')->isoFormat('dddd'); // Contoh: 'Senin'
$currentTime = now()->format('H:i:s');

$unfilledSchedules = Schedule::where('teacher_id', auth()->id())
    ->where('day_of_week', $today)
    ->forActiveCycle()
    ->whereRaw("ADDTIME(end_time, '00:30:00') < ?", [$currentTime])
    ->whereDoesntHave('journals', function ($query) {
        $query->whereDate('date', now()->toDateString());
    })
    ->get();
```

---

## 📌 CHECKLIST SELESAI STEP 7

- [ ] Rule `NoScheduleConflict` dibuat & diuji untuk mencegah bentrok waktu.
- [ ] Scope `scopeForActiveCycle` diimplementasikan di Model `Schedule`.
- [ ] `SchedulePolicy` mengunci aksi Tambah/Edit/Hapus jika `is_schedule_locked` aktif.
- [ ] `JournalObserver` otomatis mengisi presensi siswa berdasarkan perizinan `Approved`.
- [ ] Logika filter Dashboard Alert Reminder disiapkan.
