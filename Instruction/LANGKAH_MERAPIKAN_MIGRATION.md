# Panduan Langkah-Langkah Merapikan Struktur Migration

Dokumen ini berisi panduan teknis langkah demi langkah untuk menyempurnakan struktur database Laravel Anda berdasarkan review perbandingan dengan SQL lama (`db_skansaba`). 

Tujuan utama dari langkah-langkah ini adalah:
1. Menambahkan tabel `academic_years` (Tahun Ajaran).
2. Menambahkan tabel `subjects` (Mata Pelajaran).
3. Melengkapi kolom identitas (`nisn`, `nip`, `phone`) pada tabel `users`.
4. Memperbarui tabel `schedules` agar berelasi dengan `academic_years` dan `subjects`.

> [!WARNING]
> Langkah-langkah ini mengasumsikan aplikasi masih dalam tahap pengembangan (development) sehingga kita bisa mengubah file migration yang sudah ada dan menjalankan `php artisan migrate:fresh`. Pastikan data dummy Anda sudah di-backup atau bisa di-generate ulang dengan Seeder.

---

## Langkah 1: Membuat Migration dan Model Baru

Buka terminal dan jalankan perintah artisan berikut untuk membuat Model dan file Migration baru untuk `AcademicYear` dan `Subject`:

```bash
php artisan make:model AcademicYear -m
php artisan make:model Subject -m
```

## Langkah 2: Mengisi File Migration Baru

### 1. Migration `academic_years`
Buka file migration yang baru dibuat (`database/migrations/xxxx_xx_xx_xxxxxx_create_academic_years_table.php`) dan ubah bagian `up()`:

```php
public function up(): void
{
    Schema::create('academic_years', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Contoh: "2026/2027 Ganjil"
        $table->boolean('is_active')->default(false); // Penanda semester aktif
        $table->timestamps();
    });
}
```

### 2. Migration `subjects`
Buka file migration (`database/migrations/xxxx_xx_xx_xxxxxx_create_subjects_table.php`) dan ubah bagian `up()`:

```php
public function up(): void
{
    Schema::create('subjects', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // Contoh: "Matematika", "Bahasa Indonesia"
        $table->timestamps();
    });
}
```

---

## Langkah 3: Memperbarui File Migration yang Sudah Ada

### 1. Tabel `users`
Buka file `database/migrations/0001_01_01_000000_create_users_table.php`. Tambahkan kolom `nisn`, `nip`, dan `phone`:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('role_id')->nullable();
    $table->foreignId('classroom_id')->nullable();
    $table->string('full_name');
    $table->string('username');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    
    // Tambahkan 2 baris ini:
    $table->string('identity_number')->nullable()->unique()->comment('NIP for Teacher, NISN for Student');
    $table->string('phone')->nullable();
    
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
```

### 2. Tabel `schedules`
Buka file `database/migrations/2026_08_05_145127_create_schedules_table.php`. Kita perlu menambahkan `academic_year_id` dan mengubah `subject_name` menjadi `subject_id`.

```php
Schema::create('schedules', function (Blueprint $table) {
    $table->id();
    // Tambahkan academic_year_id
    $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
    
    $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
    $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
    
    // Ganti $table->string('subject_name'); menjadi:
    $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
    
    $table->enum('day_of_week', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat']);
    $table->time('start_time');
    $table->time('end_time');
    $table->enum('cycle_type', ['Putaran 1', 'Putaran 2'])->default('Putaran 1');
    $table->timestamps();
});
```

> [!IMPORTANT]
> **Penting tentang urutan Migration:** Karena `schedules` sekarang bergantung pada `academic_years` dan `subjects`, pastikan file migration `create_academic_years_table` dan `create_subjects_table` berjalan **LEBIH DULU** sebelum `create_schedules_table`. 
> 
> Anda bisa me-rename nama file migration baru tersebut (mengubah prefix tanggalnya) menjadi lebih awal dari `2026_08_05_145127`, misalnya `2026_08_04_200000_create_academic_years_table.php`.

---

## Langkah 4: Memperbarui Model Eloquent

### 1. Model `AcademicYear.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    protected $fillable = ['name', 'is_active'];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
```

### 2. Model `Subject.php`
```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name'];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
```

### 3. Model `User.php`
Buka `app/Models/User.php`, tambahkan kolom baru ke array `$fillable`:

```php
protected $fillable = [
    'role_id',
    'classroom_id',
    'full_name',
    'username',
    'email',
    'password',
    'identity_number', // Tambahkan ini
    'phone',           // Tambahkan ini
];
```

### 4. Model `Schedule.php`
Buka `app/Models/Schedule.php`. Update array `$fillable` dan tambahkan fungsi relasi.

```php
protected $fillable = [
    'academic_year_id', // Tambahkan ini
    'teacher_id',
    'classroom_id',
    'room_id',
    'subject_id',       // Ubah dari subject_name
    'day_of_week',
    'start_time',
    'end_time',
    'cycle_type',
];

// Relasi baru
public function academicYear()
{
    return $this->belongsTo(AcademicYear::class);
}

public function subject()
{
    return $this->belongsTo(Subject::class);
}
```

---

## Langkah 5: Memperbarui Database Seeder (Opsional tapi Wajib jika ada)

Jika Anda sebelumnya memiliki `ScheduleSeeder` atau seeder lain yang mengisi tabel jadwal dan user, Anda **harus** memperbaruinya.

Contoh jika mengisi `schedules`:
Ubah bagian: `'subject_name' => 'Pemrograman Web',`
Menjadi: `'subject_id' => 1,` (dan pastikan data subject dengan ID 1 sudah dibuat, begitu pula untuk `academic_year_id`).

---

## Langkah 6: Eksekusi Refresh Database

Setelah semua kode diubah, jalankan perintah ini di terminal untuk mereset seluruh tabel dan menjalankan ulang struktur baru:

```bash
php artisan migrate:fresh --seed
```

> [!CAUTION]
> Perintah `migrate:fresh` akan **MENGHAPUS SEMUA DATA** di database Anda. Pastikan Anda sudah siap kehilangan data dummy sebelum menjalankannya.

Setelah perintah ini berhasil (tidak ada pesan error/merah), struktur database Anda sudah rapi dan sempurna sesuai kebutuhan operasional sekolah!
