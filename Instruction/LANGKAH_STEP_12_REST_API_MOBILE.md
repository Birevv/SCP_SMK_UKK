# PANDUAN PENGEMBANGAN: STEP 12 — INTEGRASI RESTFUL API UNTUK APLIKASI MOBILE (FLUTTER / REACT NATIVE)

> **Sistem:** School Class Progress (SCP) - UKK 2026  
> **Modul:** Mobile REST API Architecture, Token Authentication (Laravel Sanctum), Endpoints, Contract Specification, & Postman Collection Guide  
> **Target Pengguna:** Mobile App Developer, Backend Developer, Siswa, Guru, Ortu, Tim Kesiswaan  

---

## 🎯 TUJUAN STEP 12

Tahap 12 ini dipersiapkan khusus untuk panduan pengembangan aplikasi seluler (*Mobile Application*) pendukung platform School Class Progress (SCP). 

Panduan ini berisi langkah-langkah implementasi teknis untuk membangun RESTful API backend berbasis **Laravel Sanctum (Token-Based Authentication)** agar aplikasi Mobile (Android/iOS cross-platform seperti Flutter / React Native / Kotlin / Swift) dapat mengakses data presensi, jurnal mengajar, pengajuan perizinan, dan notifikasi secara aman.

---

## 🛠️ TAHAPAN SETUP & ARSITEKTUR REST API

---

### 📍 SUB-STEP 12.1: SETUP LARAVEL SANCTUM & ROUTING API

#### 1. Jalankan Perintah Instalasi API Laravel
```bash
php artisan install:api
php artisan migrate
```

#### 2. Tambahkan Trait `HasApiTokens` pada Model User (`app/Models/User.php`)
```php
namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
    
    // ...
}
```

#### 3. Daftarkan File Routing API (`bootstrap/app.php`)
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // Router REST API
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
```

---

### 📍 SUB-STEP 12.2: STANDARISASI FORMAT JSON RESPONSE

Buat Response Wrapper standar di Laravel agar seluruh response API konsisten di aplikasi Mobile:

#### Standar JSON Response Success (`200 OK` / `201 Created`):
```json
{
  "success": true,
  "message": "Pesan deskriptif keberhasilan",
  "data": {
    "key": "value"
  }
}
```

#### Standar JSON Response Error (`401 Unauthorized` / `403 Forbidden` / `422 Unprocessable`):
```json
{
  "success": false,
  "message": "Pesan kegagalan atau validasi error",
  "errors": {
    "field_name": [
      "Detail pesan kesalahan validasi"
    ]
  }
}
```

---

### 📍 SUB-STEP 12.3: DAFTAR ENDPOINT & PAYLOAD CONTRACT

---

#### 🔑 1. MODUL AUTENTIKASI (`/api/v1/auth/*`)

##### A. Login (`POST /api/v1/auth/login`)
- **Headers:** `Accept: application/json`
- **Request Body (JSON):**
  ```json
  {
    "username": "siswa1",
    "password": "password"
  }
  ```
- **Response Success (`200 OK`):**
  ```json
  {
    "success": true,
    "message": "Login berhasil",
    "data": {
      "token": "1|sanctum_token_string_here...",
      "token_type": "Bearer",
      "user": {
        "id": 5,
        "full_name": "Ahmad Siswa",
        "username": "siswa1",
        "email": "siswa1@smk.sch.id",
        "role": "Siswa",
        "classroom": "XII RPL 1"
      }
    }
  }
  ```

##### B. Profile User (`GET /api/v1/auth/me`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Response Success (`200 OK`):** Data profil user yang sedang login.

##### C. Logout (`POST /api/v1/auth/logout`)
- **Headers:** `Authorization: Bearer <TOKEN>`
- **Response Success (`200 OK`):** Menoaktifkan / mencabut token aktif.

---

#### 📱 2. MODUL SISWA & ORANG TUA (`/api/v1/student/*`)

##### A. Overview Ringkasan Kehadiran (`GET /api/v1/student/attendance-summary`)
- **Headers:** `Authorization: Bearer <TOKEN>`
- **Deskripsi:** Mengambil persentase kehadiran, jumlah Hadir/Sakit/Izin/Alpa, dan 15 riwayat presensi terbaru (untuk tampilan dashboard mobile Siswa/Ortu).
- **Response Success (`200 OK`):**
  ```json
  {
    "success": true,
    "message": "Ringkasan presensi berhasil diambil",
    "data": {
      "student_name": "Ahmad Siswa",
      "hadir": 18,
      "sakit": 1,
      "izin": 1,
      "alpa": 0,
      "total": 20,
      "percentage": 90.0,
      "history": [
        {
          "id": 101,
          "date": "2026-08-10",
          "subject_name": "Pemrograman Web",
          "status": "Hadir",
          "notes": "Tepat waktu"
        }
      ]
    }
  }
  ```

##### B. Daftar Surat Izin/Sakit (`GET /api/v1/student/leave-requests`)
- **Headers:** `Authorization: Bearer <TOKEN>`
- **Response Success (`200 OK`):** Array berisi status pengajuan izin (`Pending`, `Approved`, `Rejected`) dan link file bukti dokumen.

##### C. Pengajuan Surat Izin Baru (`POST /api/v1/student/leave-requests`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Content-Type: multipart/form-data`
- **Request Parameters:**
  - `type` (string: `Sakit` / `Izin`) — *Required*
  - `start_date` (date: `YYYY-MM-DD`) — *Required*
  - `end_date` (date: `YYYY-MM-DD`) — *Required*
  - `reason` (string) — *Required*
  - `document` (file: `jpg, png, pdf` max 2MB) — *Optional*

---

#### 👨‍🏫 3. MODUL GURU (`/api/v1/teacher/*`)

##### A. Jadwal Mengajar Putaran Aktif (`GET /api/v1/teacher/schedules`)
- **Headers:** `Authorization: Bearer <TOKEN>`
- **Deskripsi:** Mengambil jadwal mengajar guru yang terfilter otomatis berdasarkan `active_cycle` (Putaran 1 / Putaran 2).

##### B. Peringatan Alert Jurnal (`GET /api/v1/teacher/unfilled-alerts`)
- **Headers:** `Authorization: Bearer <TOKEN>`
- **Deskripsi:** Mengirim daftar jadwal hari ini yang sudah lewat >30 menit dari `end_time` namun jurnalnya belum diisi.

##### C. Simpan Jurnal & Presensi Siswa (`POST /api/v1/teacher/journals`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Content-Type: multipart/form-data`
- **Request Payload:**
  - `schedule_id` (integer) — *Required*
  - `date` (date: `YYYY-MM-DD`) — *Required*
  - `topic` (string) — *Required*
  - `photo` (file image realtime) — *Required*
  - `attendances` (array of objects: `[{student_id: 1, status: "Hadir", notes: ""}]`) — *Required*

---

#### ⚙️ 4. MODUL SYSTEM CONFIGURATION (`/api/v1/system/*`)

##### A. Status Konfigurasi Global (`GET /api/v1/system/config`)
- **Public Endpoint / Authenticated**
- **Response Success (`200 OK`):**
  ```json
  {
    "success": true,
    "message": "Konfigurasi sistem diambil",
    "data": {
      "active_cycle": "Putaran 1",
      "is_schedule_locked": false
    }
  }
  ```

---

### 📍 SUB-STEP 12.4: CONTOH SCRIPT & CONTROLLER LARAVEL (REFERENCE CODE)

#### 1. `app/Http/Controllers/Api/AuthController.php`
```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::with(['role', 'classroom'])->where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial login tidak valid.',
            ], 401);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'role' => $user->role?->name,
                ]
            ]
        ]);
    }
}
```

#### 2. Definisi Rute API (`routes/api.php`)
```php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentApiController;
use Illuminate\Support\Facades\Route;

Route::post('/v1/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    Route::get('/student/attendance-summary', [StudentApiController::class, 'attendanceSummary']);
    Route::post('/student/leave-requests', [StudentApiController::class, 'storeLeaveRequest']);
});
```

---

### 📍 SUB-STEP 12.5: TESTING & POSTMAN COLLECTION GUIDE

1. **Gunakan Header Wajib di Postman / Bruno / HTTP Client:**
   - `Accept: application/json`
   - `Authorization: Bearer <TOKEN_HASIL_LOGIN>`
2. **Pengujian File Upload (Multipart Form-Data):**
   - Pilih `form-data` pada tab Body Postman untuk pengujian upload foto jurnal (`photo`) atau surat dokter (`document`).
3. **Penanganan Cross-Origin Resource Sharing (CORS):**
   - Pastikan file `config/cors.php` mengizinkan request dari domain/origin aplikasi mobile (`allowed_origins => ['*']`).

---

## 📌 CHECKLIST INTEGRASI MOBILE REST API

- [ ] Command `php artisan install:api` telah dijalankan saat nanti mempublikasikan API.
- [ ] Token Sanctum terbit dengan benar saat login via mobile client.
- [ ] Endpoint `/api/v1/student/attendance-summary` mengembalikan data presensi yang akurat.
- [ ] Upload multipart foto jurnal dan surat izin berjalan tanpa hambatan.
- [ ] Format error validasi mengembalikan status `422 Unprocessable Entity` berbentuk JSON.

🎉 **DOKUMEN INTEGRASI REST API MOBILE (STEP 12) SIAP DIGUNAKAN OLEH TIM DEVELOPER MOBILE APPLICATION!**
