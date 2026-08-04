# PANDUAN PENGEMBANGAN: LANGKAH 1.4 — MIGRATION & MODEL KONFIGURASI SISTEM (`system_configurations`)

> **Sistem:** School Class Progress (SCP) - UKK 2026  
> **Modul:** Konfigurasi Global Sistem (Saklar Blok Putaran & Lock Schedule)

---

## 🎯 TUJUAN & LATIHAN LOGIKA UKK

Tabel `system_configurations` menyimpan pengaturan global sistem yang dikendalikan oleh **Waka Kurikulum**.

**Mengapa Kritis dalam UKK?**  
Tabel ini menunjang **2 Logika Bisnis Utama UKK**:
1. **Saklar Putaran Blok (Cycle Toggle)**: Menyimpan `active_cycle` (`'Putaran 1'` atau `'Putaran 2'`). Semua query jadwal mengajar akan memfilter sesuai putaran aktif ini.
2. **Kunci Jadwal (Lock Schedule)**: Menyimpan `is_schedule_locked` (`true` atau `false`). Jika bernilai `true`, maka Guru tidak dapat menambah/mengedit jadwal mengajar di Filament.

---

## 📋 DAFTAR FILE YANG DIBUAT / DIUBAH

1. `database/migrations/xxxx_xx_xx_xxxxxx_create_system_configurations_table.php` *(New)*
2. `app/Models/SystemConfiguration.php` *(New)*
3. `app/Filament/Resources/SystemConfigurations/SystemConfigurationResource.php` *(Opsional - atau Custom Page Filament)*

---

## 🛠️ LANGKAH MENGAKSES & MENGERJAKAN (STEP-BY-STEP)

### **Langkah 1: Membuat Migration & Model via Artisan**

Buka terminal di root project, lalu jalankan:

```bash
php artisan make:model SystemConfiguration -m
```

---

### **Langkah 2: Menulis Skema Tabel di Migration**

Buka file migration baru `database/migrations/YYYY_MM_DD_HHMMSS_create_system_configurations_table.php`, lalu susun struktur kolom berikut:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_configurations', function (Blueprint $table) {
            $table->id();
            $table->enum('active_cycle', ['Putaran 1', 'Putaran 2'])->default('Putaran 1');
            $table->boolean('is_schedule_locked')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_configurations');
    }
};
```

---

### **Langkah 3: Mengatur Model Eloquent (`SystemConfiguration.php`)**

Buka file `app/Models/SystemConfiguration.php`, lalu atur properti `$fillable`, `$casts`, dan relasi ke `User`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemConfiguration extends Model
{
    protected $fillable = [
        'active_cycle',
        'is_schedule_locked',
        'updated_by',
    ];

    protected $casts = [
        'is_schedule_locked' => 'boolean',
    ];

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
```

---

### **Langkah 4: Jalankan Migration ke Database**

Jalankan perintah berikut di terminal:

```bash
php artisan migrate
```

---

### **Langkah 5: Membuat Filament Resource (Pengaturan Sistem)**

Jalankan perintah pembuat Resource Filament:

```bash
php artisan make:filament-resource SystemConfiguration --generate
```

Pengaturan rekomendasi di `SystemConfigurationResource.php`:
- `$navigationGroup = "Pengaturan";`
- `$navigationIcon = Heroicon::Cog6Tooth;`
- `$navigationLabel = "Konfigurasi Sistem";`
- `$pluralLabel = "Konfigurasi Sistem";`

---

## 🧪 VERIFIKASI & PENGUJIAN

### **Verifikasi via Artisan Tinker**

Jalankan:
```bash
php artisan tinker
```
Ketik:
```php
App\Models\SystemConfiguration::create([
    'active_cycle' => 'Putaran 1',
    'is_schedule_locked' => false,
]);
App\Models\SystemConfiguration::first();
```

---

## 📌 CHECKLIST SELESAI STEP 1.4

- [ ] File migration `create_system_configurations_table` dibuat dan di-migrate.
- [ ] Model `SystemConfiguration.php` diset dengan `$fillable` dan `$casts`.
- [ ] Resource Filament `SystemConfigurationResource` dibuat untuk pengaturan Waka Kurikulum.
