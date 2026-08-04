# PANDUAN PENGEMBANGAN: STEP 5 — DATABASE SEEDER TERPISAH (MODULAR SEEDERS)

> **Sistem:** School Class Progress (SCP) - UKK 2026  
> **Modul:** Modular Database Seeders (Role, User, Config, Ruangan, & Kelas)

---

## 🎯 TUJUAN SEEDER TERPISAH (MODULAR)

Setiap modul seeder dibuat di file kelasnya masing-masing agar kode rapi, mudah di-maintenance, dan dapat dijalankan secara terpisah maupun bersamaan.

**Daftar Seeder Terpisah:**
1. `RoleSeeder.php` — 6 Role Utama UKK
2. `UserSeeder.php` — User Default Waka Kurikulum
3. `SystemConfigurationSeeder.php` — Default Saklar Putaran & Lock Schedule
4. `RoomSeeder.php` — Sampel Data Ruangan
5. `ClassroomSeeder.php` — Sampel Data Kelas
6. `DatabaseSeeder.php` — Main Seeder pemanggil seluruh Seeder

---

## 🛠️ LANGKAH PENGERJAAN & PERINTH ARTISAN (STEP-BY-STEP)

### **Langkah 1: Buat Seluruh File Seeder via Artisan**

Buka terminal dan jalankan 5 perintah pembuatan file seeder berikut:

```bash
php artisan make:seeder RoleSeeder
php artisan make:seeder UserSeeder
php artisan make:seeder SystemConfigurationSeeder
php artisan make:seeder RoomSeeder
php artisan make:seeder ClassroomSeeder
```

---

### **Langkah 2: Isi Kode `RoleSeeder.php`**

Buka file `database/seeders/RoleSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Waka Kurikulum',
            'Guru',
            'Wali Kelas',
            'Guru BK',
            'Siswa',
            'Orang Tua',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
    }
}
```

---

### **Langkah 3: Isi Kode `UserSeeder.php`**

Buka file `database/seeders/UserSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $wakaRole = Role::where('name', 'Waka Kurikulum')->first();

        User::firstOrCreate(
            ['username' => 'waka'],
            [
                'full_name' => 'Waka Kurikulum Admin',
                'email' => 'waka@scp.sch.id',
                'password' => Hash::make('password'),
                'role_id' => $wakaRole?->id,
            ]
        );
    }
}
```

---

### **Langkah 4: Isi Kode `SystemConfigurationSeeder.php`**

Buka file `database/seeders/SystemConfigurationSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Database\Seeder;

class SystemConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $wakaUser = User::where('username', 'waka')->first();

        if (SystemConfiguration::count() === 0) {
            SystemConfiguration::create([
                'active_cycle' => 'Putaran 1',
                'is_schedule_locked' => false,
                'updated_by' => $wakaUser?->id,
            ]);
        }
    }
}
```

---

### **Langkah 5: Isi Kode `RoomSeeder.php`**

Buka file `database/seeders/RoomSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = ['Lab RPL 1', 'Lab Komputer 2', 'Ruang Teori 01'];

        foreach ($rooms as $roomName) {
            Room::firstOrCreate(['name' => $roomName]);
        }
    }
}
```

---

### **Langkah 6: Isi Kode `ClassroomSeeder.php`**

Buka file `database/seeders/ClassroomSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Classroom;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    public function run(): void
    {
        $classrooms = ['XII RPL 1', 'XII RPL 2', 'XI RPL 1'];

        foreach ($classrooms as $className) {
            Classroom::firstOrCreate(['name' => $className]);
        }
    }
}
```

---

### **Langkah 7: Panggil Seluruh Seeder di `DatabaseSeeder.php`**

Buka file [DatabaseSeeder.php](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/database/seeders/DatabaseSeeder.php) dan daftarkan seluruh seeder di method `run()`:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            SystemConfigurationSeeder::class,
            RoomSeeder::class,
            ClassroomSeeder::class,
        ]);
    }
}
```

---

## 🧪 EKSEKUSI & VERIFIKASI SEEDER

### **1. Jalankan Seluruh Seeder**
```bash
php artisan db:seed
```

### **2. Jalankan Seeder Spesifik (Contoh: Hanya RoleSeeder)**
```bash
php artisan db:seed --class=RoleSeeder
```

### **3. Reset & Seed Ulang Seluruh Database dari Nol**
```bash
php artisan migrate:fresh --seed
```

---

## 📌 CHECKLIST SELESAI STEP 5

- [x] File `RoleSeeder.php` dibuat & memuat 6 role.
- [x] File `UserSeeder.php` dibuat & memuat admin Waka.
- [x] File `SystemConfigurationSeeder.php` dibuat & memuat default config.
- [x] File `RoomSeeder.php` dibuat & memuat data ruangan.
- [x] File `ClassroomSeeder.php` dibuat & memuat data kelas.
- [x] `DatabaseSeeder.php` memanggil `$this->call(...)` secara berurutan.
- [x] Perintah `php artisan db:seed` berhasil dijalankan tanpa error.
    