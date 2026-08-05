    # PANDUAN PENGEMBANGAN: STEP 6 — TABEL TRANSAKSIONAL (JADWAL, JURNAL, PRESENSI, & PERIZINAN)

> **Sistem:** School Class Progress (SCP) - UKK 2026  
> **Modul:** Master Transaksi Utama (`schedules`, `journals`, `leave_requests`, `attendances`)

---

## 🎯 TUJUAN STEP 6

Membuat 4 Migration dan Model utama yang menjadi tulang punggung (core transactions) dari aplikasi SCP:
1. `Schedule` — Master Jadwal Mengajar Guru
2. `Journal` — Laporan Mengajar Harian & Bukti Foto Realtime
3. `LeaveRequest` — Pengajuan Surat Izin/Sakit dari Siswa/Orang Tua
4. `Attendance` — Catatan Kehadiran Siswa per Jurnal

---

## 🛠️ LANGKAH PENGERJAAN & PERINTAH ARTISAN (STEP-BY-STEP)

### **Langkah 1: Buat Model & Migration via Artisan**

Jalankan perintah berikut di terminal:

```bash
php artisan make:model Schedule -m
php artisan make:model Journal -m
php artisan make:model LeaveRequest -m
php artisan make:model Attendance -m
```

---

### **Langkah 2: Skema Migration & Model `Schedule`**

#### A. Migration `database/migrations/xxxx_xx_xx_xxxxxx_create_schedules_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->string('subject_name');
            $table->enum('day_of_week', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('cycle_type', ['Putaran 1', 'Putaran 2'])->default('Putaran 1');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
```

#### B. Model `app/Models/Schedule.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'classroom_id',
        'room_id',
        'subject_name',
        'day_of_week',
        'start_time',
        'end_time',
        'cycle_type',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function journals(): HasMany
    {
        return $this->hasMany(Journal::class);
    }
}
```

---

### **Langkah 3: Skema Migration & Model `Journal`**

#### A. Migration `database/migrations/xxxx_xx_xx_xxxxxx_create_journals_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->text('teaching_material');
            $table->string('activity_photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
```

#### B. Model `app/Models/Journal.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'teacher_id',
        'date',
        'teaching_material',
        'activity_photo',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
```

---

### **Langkah 4: Skema Migration & Model `LeaveRequest`**

#### A. Migration `database/migrations/xxxx_xx_xx_xxxxxx_create_leave_requests_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('reason_type', ['Sakit', 'Izin']);
            $table->text('description');
            $table->string('attachment_photo')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
```

#### B. Model `app/Models/LeaveRequest.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'submitted_by',
        'start_date',
        'end_date',
        'reason_type',
        'description',
        'attachment_photo',
        'status',
        'verified_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
```

---

### **Langkah 5: Skema Migration & Model `Attendance`**

#### A. Migration `database/migrations/xxxx_xx_xx_xxxxxx_create_attendances_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('journals')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['Hadir', 'Sakit', 'Izin', 'Alpa']);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['journal_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
```

#### B. Model `app/Models/Attendance.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'student_id',
        'status',
        'notes',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
```

---

## 🧪 EKSEKUSI MIGRATION

Setelah semua file migration dan model dibuat, jalankan migrasi database:

```bash
php artisan migrate
```

---

## 📌 CHECKLIST SELESAI STEP 6

- [ ] Model & Migration `schedules` dibuat & dikonfigurasi.
- [ ] Model & Migration `journals` dibuat & dikonfigurasi.
- [ ] Model & Migration `leave_requests` dibuat & dikonfigurasi.
- [ ] Model & Migration `attendances` dibuat & dikonfigurasi.
- [ ] Relasi Eloquent pada setiap Model disesuaikan.
- [ ] Migration database berhasil dijalankan tanpa error (`php artisan migrate`).
