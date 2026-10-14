# 🛡️ PANDUAN LENGKAP SETUP SPATIE ROLE & FILAMENT SHIELD (REVISI)
## Project: School Class Progress (SCP) — SMK UKK Paket 3

Panduan ini berisi langkah-langkah konkret (**step-by-step**) penyesuaian migrasi database, integrasi **Spatie Laravel Permission**, dan **Filament Shield** di mana **hak akses manajemen role & permission HANYA diberikan kepada Super Admin**, dan **BUKAN Waka Kurikulum**. Waka Kurikulum memiliki porsi fitur tersendiri (manajemen kurikulum, jadwal, dan akademik).

---

## 📌 DAFTAR ISI
1. [Prinsip Hak Akses: Super Admin vs Waka Kurikulum](#1-prinsip-hak-akses-super-admin-vs-waka-kurikulum)
2. [Langkah 1: Penyesuaian Migrasi Database (Anti-Bentrok)](#langkah-1-penyesuaian-migrasi-database-anti-bentrok)
3. [Langkah 2: Model `Role.php` & `User.php`](#langkah-2-model-rolephp--userphp)
4. [Langkah 3: Registrasi Plugin Filament Shield](#langkah-3-registrasi-plugin-filament-shield)
5. [Langkah 4: Proteksi Eksklusif Role Policy (Hanya Super Admin)](#langkah-4-proteksi-eksklusif-role-policy-hanya-super-admin)
6. [Langkah 5: Konfigurasi Super Admin & Gate Bypass di AppServiceProvider](#langkah-5-konfigurasi-super-admin--gate-bypass-di-appserviceprovider)
7. [Langkah 6: Update Seeder (`RoleSeeder` & `UserSeeder`)](#langkah-6-update-seeder-roleseeder--userseeder)
8. [Langkah 7: Generate Permissions & Policies Otomatis](#langkah-7-generate-permissions--policies-otomatis)
9. [Langkah 8: Verifikasi & Uji Coba Multi-Aktor](#langkah-8-verifikasi--uji-coba-multi-aktor)
10. [Troubleshooting & Solusi Error Umum](#troubleshooting--solusi-error-umum)

---

## 1. Prinsip Hak Akses: Super Admin vs Waka Kurikulum

> [!IMPORTANT]
> **PEMISAHAN WEWENANG (SEPARATION OF CONCERNS):**
> - **Super Admin (`admin@scp.sch.id`):** 
>   - Satu-satunya akun yang berhak mengakses menu **Shield / Roles** di Filament.
>   - Mengatur izin akses (permissions) untuk seluruh aktor sistem (Guru, Wali Kelas, Guru BK, Siswa).
>   - Mengelola seluruh **Data Master**: Pengguna (Users), Tahun Ajaran (Academic Years), Mata Pelajaran (Subjects), Ruangan (Rooms), dan Kelas (Classrooms).
>   - Mengelola Konfigurasi Sistem CRUD, Jadwal Mengajar, Jurnal, dan Verifikasi Perizinan Siswa.
>   - Memiliki bypass Gate (`Gate::before`) untuk seluruh fungsionalitas.
> - **Waka Kurikulum (`waka@scp.sch.id`):** 
>   - Hanya memiliki wewenang:
>     1. **Login & Logout**
>     2. **Update Profile** (EditProfile)
>     3. **Kunci Jadwal (Lock Schedule)** via Widget Dashboard
>     4. **Ganti Siklus Blok (Cycle Toggle Putaran 1 / Putaran 2)** via Widget Dashboard
>   - Seluruh fitur lainnya (Master Data, Manajemen Role, Jadwal, Presensi, dan Pengaturan Sistem) dialihkan secara penuh kepada **Super Admin**.

---

## Langkah 1: Penyesuaian Migrasi Database (Anti-Bentrok)

### Mengapa Migrasi Harus Disesuaikan?
Secara default, jika Anda langsung mempublikasikan migrasi Spatie (`create_permission_tables`), akan terjadi bentrok error `table "roles" already exists` karena proyek ini sudah memiliki migrasi `2026_08_03_142857_create_roles_table.php`.

### Solusi Bersih (Telah Diimplementasikan):
Migrasi `database/migrations/2026_08_03_142857_create_roles_table.php` diperbarui agar langsung memuat seluruh struktur Spatie Permission yang kompatibel dengan foreign key `role_id` pada tabel `users`. File duplikat Spatie tidak diperlukan lagi.

Berikut isi lengkap file migrasi `database/migrations/2026_08_03_142857_create_roles_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), 'Error: team_foreign_key on config/permission.php not loaded.');

        // 1. Tabel Permissions
        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        // 2. Tabel Roles (Kompatibel dengan relasi role_id pada tabel users)
        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->id();
            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        // 3. Tabel Model Has Permissions
        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)->references('id')->on($tableNames['permissions'])->cascadeOnDelete();
            $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
        });

        // 4. Tabel Model Has Roles
        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)->references('id')->on($tableNames['roles'])->cascadeOnDelete();
            $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
        });

        // 5. Tabel Role Has Permissions
        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)->references('id')->on($tableNames['permissions'])->cascadeOnDelete();
            $table->foreign($pivotRole)->references('id')->on($tableNames['roles'])->cascadeOnDelete();
            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        Schema::dropIfExists($tableNames['role_has_permissions']);
        Schema::dropIfExists($tableNames['model_has_roles']);
        Schema::dropIfExists($tableNames['model_has_permissions']);
        Schema::dropIfExists($tableNames['roles']);
        Schema::dropIfExists($tableNames['permissions']);
    }
};
```

Dengan penyesuaian di atas:
- Perintah `php artisan migrate:fresh --seed` berjalan **100% mulus tanpa error**.
- Semua Automated Unit/Feature Test berjalan lancar.

---

## Langkah 2: Model `Role.php` & `User.php`

### 2.1 Model `Role.php`
Buka [app/Models/Role.php](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/app/Models/Role.php):
Pastikan class `Role` meng-extend `Spatie\Permission\Models\Role`.

```php
<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
    ];
}
```

### 2.2 Model `User.php`
Buka [app/Models/User.php](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/app/Models/User.php):
1. Tambahkan `FilamentUser` pada `implements`.
2. Tambahkan trait `HasRoles`.
3. Tambahkan method `canAccessPanel()`.

```php
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasName, FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * Membatasi siapa saja yang boleh masuk ke Filament Admin.
     * Siswa diblokir dari dashboard Admin.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['Super Admin', 'Waka Kurikulum', 'Guru', 'Wali Kelas', 'Guru BK'])
            || in_array($this->role?->name, ['Super Admin', 'Waka Kurikulum', 'Guru', 'Wali Kelas', 'Guru BK']);
    }

    // Tetap pertahankan relasi role legacy untuk kompatibilitas API controller:
    public function role(): BelongsTo 
    {   
        return $this->belongsTo(Role::class, 'role_id');
    }
}
```

---

## Langkah 3: Registrasi Plugin Filament Shield

Buka file [app/Providers/Filament/AdminPanelProvider.php](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/app/Providers/Filament/AdminPanelProvider.php):

Import `FilamentShieldPlugin` dan daftarkan di dalam method `->plugins([...])`:

```php
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->colors([
            'primary' => Color::Blue,
        ])
        ->plugins([
            FilamentShieldPlugin::make(), // <-- Daftarkan di sini
        ])
        // ... konfigurasi lainnya
}
```

---

## Langkah 4: Proteksi Eksklusif Role Policy (Hanya Super Admin)

Agar menu dan data Role di Filament Shield **HANYA BISA DIAKSES OLEH SUPER ADMIN**, buat file [app/Policies/RolePolicy.php](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/app/Policies/RolePolicy.php):

```php
<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Contracts\Role;

class RolePolicy
{
    /**
     * Hanya Super Admin yang berhak melihat menu dan mengelola Spatie Role / Filament Shield.
     * Waka Kurikulum, Guru, dan role lainnya DITOLAK.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasRole('Super Admin');
    }
}
```

> [!TIP]
> Dengan Policy ini, ketika user `waka` login ke panel Filament, menu **Roles / Shield** otomatis **HILANG** dari sidebar navigasi, dan jika diakses langsung lewat URL akan mengembalikan `403 Forbidden`.

---

## Langkah 5: Konfigurasi Super Admin & Gate Bypass di AppServiceProvider

Buka file [app/Providers/AppServiceProvider.php](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/app/Providers/AppServiceProvider.php):

Tambahkan `Gate::before` untuk memberikan hak penuh kepada akun dengan role `Super Admin`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Berikan akses penuh ke semua gates jika memiliki role Super Admin
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
```

---

## Langkah 6: Update Seeder (`RoleSeeder` & `UserSeeder`)

Pastikan akun `admin` (Super Admin) dan akun `waka` (Waka Kurikulum) terpisah jelas pada seeder.

### 6.1 [database/seeders/RoleSeeder.php](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/database/seeders/RoleSeeder.php)

```php
<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'Super Admin',      // <-- Khusus Pengelola Role & Shield
            'Waka Kurikulum',   // <-- Khusus Manajemen Akademik & Jadwal
            'Guru',
            'Wali Kelas',
            'Guru BK',
            'Siswa',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
```

### 6.2 [database/seeders/UserSeeder.php](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/database/seeders/UserSeeder.php)

```php
// 0. Akun Super Administrator (Mengelola Role & Shield)
$superAdmin = User::firstOrCreate(
    ['username' => 'Muzan'],
    [
        'full_name' => 'Kibutsuji Muzan',
        'identity_number' => '197001011990011001',
        'email' => 'admin@scp.sch.id',
        'password' => Hash::make('password'),
        'role_id' => Role::where('name', 'Super Admin')->value('id'),
    ]
);
$superAdmin->syncRoles(['Super Admin']);

// 1. Akun Waka Kurikulum (Manajemen Akademik, BUKAN Super Admin)
$waka = User::firstOrCreate(
    ['username' => 'waka'],
    [
        'full_name' => 'Waka Kurikulum Admin',
        'identity_number' => '197501012000031001',
        'email' => 'waka@scp.sch.id',
        'password' => Hash::make('password'),
        'role_id' => Role::where('name', 'Waka Kurikulum')->value('id'),
    ]
);
$waka->syncRoles(['Waka Kurikulum']); // <-- Hanya role Waka Kurikulum!
```

---

## Langkah 7: Generate Permissions & Policies Otomatis

Jalankan perintah berikut di terminal:

```bash
php artisan shield:generate --all
```

Perintah ini akan membaca semua Resource di folder `app/Filament/Resources/` dan secara otomatis membuat permissions serta class policy di direktori `app/Policies/`.

---

## Langkah 8: Verifikasi & Uji Coba Multi-Aktor

### 1. Uji Coba Akun Super Admin:
- Jalankan web server: `php artisan serve`
- Buka: `http://127.0.0.1:8000/admin`
- Login dengan:
  - **Username / Email**: `admin` / `admin@scp.sch.id`
  - **Password**: `password`
- **Hasil yang Diharapkan:**
  - Muncul grup menu **Shield** > **Roles**.
  - Super admin dapat membuat role, mengatur izin checklist setiap resource, page, dan widget.

### 2. Uji Coba Akun Waka Kurikulum:
- Buka jendela *Incognito / Private Window*.
- Login dengan:
  - **Username / Email**: `waka` / `waka@scp.sch.id`
  - **Password**: `password`
- **Hasil yang Diharapkan:**
  - Menu **Shield** > **Roles** **TIDAK MUNCUL** sama sekali di navigasi.
  - Jika mengetik URL `http://127.0.0.1:8000/admin/shield/roles`, sistem langsung menampilkan halaman **403 Forbidden**.
  - Waka tetap memiliki menu manajerial akademik sekolah (Jadwal, Rekap Presensi, dll.).

### 3. Uji Coba Otomatis (Automated Test):
Jalankan tes kapan saja untuk memastikan proteksi ini tetap terjaga:
```bash
php artisan test --filter=RolePermissionTest
```

---

## Troubleshooting & Solusi Error Umum

| Masalah | Penyebab | Solusi |
| :--- | :--- | :--- |
| **Menu Shield masih terlihat oleh Waka** | Cache permissions Spatie masih tersimpan di memory | Jalankan:<br>`php artisan permission:cache-reset`<br>`php artisan optimize:clear` |
| **Error `table roles already exists`** | Terdapat file migrasi duplikat di `database/migrations/` | Hapus file migrasi tambahan `create_permission_tables` karena skemanya sudah disatukan di `2026_08_03_142857_create_roles_table.php`. |
| **Role baru tidak muncul di Filament** | Guard name tidak sesuai | Pastikan role dibuat dengan `guard_name = 'web'`. |
| **Perubahan permissions di UI belum berefek** | Browser session atau permission cache | Logout, jalankan `php artisan permission:cache-reset`, lalu login kembali. |
| **Muncul teks class model seperti `App\Models\AcademicYear` di bawah nama resource** | Opsi bawaan `show_model_path` aktif di config Shield | Ubah `'show_model_path' => false` pada file `config/filament-shield.php`, lalu jalankan `php artisan optimize:clear`. |

---

> Dokumen revisi ini telah diselaraskan dengan arsitektur **Uji Kompetensi Keahlian (UKK) Rekayasa Perangkat Lunak 2026** pada aplikasi **School Class Progress (SCP)**.
