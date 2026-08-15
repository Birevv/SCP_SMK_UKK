# PANDUAN PENGEMBANGAN: STEP 12 — INTEGRASI RESTFUL API UNTUK APLIKASI MOBILE (LARAVEL 13 & BEST PRACTICES)

> **Sistem:** School Class Progress (SCP) - UKK 2026  
> **Framework & Runtime:** Laravel 13.x | PHP 8.3+ | Laravel Sanctum 4.x  
> **Modul:** Mobile RESTful API Architecture, Token Authentication (Sanctum), Form Requests, API Resources, Endpoints Contract, Implementation Controllers, & Postman Collection Guide  
> **Target Pengguna:** Mobile App Developer (Flutter / React Native / Kotlin / Swift), Backend Developer, Siswa, Guru, Orang Tua, Tim Kesiswaan  

---

## 🎯 TUJUAN & ARSITEKTUR REST API (STEP 12)

Tahap 12 ini disusun sebagai panduan teknis implementasi **RESTful API** pada sistem **School Class Progress (SCP)** yang menghubungkan backend Laravel dengan **Aplikasi Mobile (Android & iOS)**.

### 🌟 Mengapa Mengikuti Laravel Best Practices & Standar Laravel 13?
1. **Stateless Token Authentication (Laravel Sanctum 4.x):** Mengamankan seluruh endpoint mobile dengan token Bearer yang ringan, cepat, dan mudah dicabut (*revoked*) saat user logout.
2. **Thin Controllers & Single Responsibility:** Controller tetap ringkas (< 15 baris per method) dengan mendelegasikan validasi ke **Form Request Classes** dan transformasi data ke **API Resources (`JsonResource`)**.
3. **Pencegahan N+1 Query & Optimasi Database:** Menerapkan eager loading (`with()`), query relationship `whereBelongsTo()`, dan pemanfaatan local scope model (`forActiveCycle()`).
4. **Integritas Data Transaksional (`DB::transaction()`):** Memastikan proses penyimpanan multi-tabel (seperti Jurnal Mengajar + Presensi Siswa sekelas) tersimpan secara atomik tanpa risiko data inkonsisten.
5. **Modern PHP 8.3+ Typing:** Menggunakan strict return types (`JsonResponse`, `array`), constructor property promotion, dan method casting modern.

---

## 🛠️ TAHAPAN SETUP & ARSITEKTUR REST API (LANGKAH DEMI LANGKAH)

---

### 📍 SUB-STEP 12.1: PREPARASI & SETUP LARAVEL SANCTUM (LARAVEL 13)

Tahap awal ini bertujuan mengaktifkan rute API dan mengonfigurasi mekanisme otentikasi token.

---

#### 1. Jalankan Perintah Instalasi API Laravel (`php artisan install:api`)

##### 📘 Penjelasan & Rationale:
Pada Laravel 11/13, struktur awal proyek sangat ramping. Perintah `php artisan install:api` secara otomatis:
- Memasang paket **Laravel Sanctum 4.x** sebagai dependensi autentikasi API.
- Membuat file `routes/api.php` untuk menampung endpoint REST API.
- Menyiapkan skema migrasi tabel `personal_access_tokens` untuk mencatat token sesi mobile di database.

##### ⌨️ Perintah Terminal:
```bash
php artisan install:api
php artisan migrate
```

##### ✅ Hasil Ekspektasi:
- File `routes/api.php` tersedia di dalam folder `routes/`.
- Tabel `personal_access_tokens` terbentuk di database.

> [!TIP]
> **Troubleshooting Error `Failed to open stream: No such file or directory (routes/api.php)`**:  
> Jika saat menjalankan `php artisan install:api` muncul error `require(.../routes/api.php): Failed to open stream`, hal ini terjadi karena `bootstrap/app.php` sudah mereferensikan file `api.php` sebelum filenya dibuat secara fisik di disk.  
> **Solusi:** Buat file kosong `routes/api.php` terlebih dahulu (misal berisi `<?php`), lalu jalankan kembali `php artisan install:api`.

---

#### 2. Pasang Trait `HasApiTokens` pada Model User (`app/Models/User.php`)

##### 📘 Penjelasan & Rationale:
Trait `HasApiTokens` dari Laravel Sanctum memberikan method esensial pada model `User`:
- `$user->createToken('token-name')->plainTextToken` untuk menerbitkan Bearer Token saat login.
- `$user->currentAccessToken()->delete()` untuk mencabut token saat logout.
- `$request->user()` untuk mengambil instance user yang sedang terotentikasi.

##### 💻 Kode Modifikasi (`app/Models/User.php`):
```php
<?php

namespace App\Models;

use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // 1. Import Trait Sanctum

#[Fillable(['role_id', 'classroom_id', 'parent_id', 'full_name', 'username', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasName
{
    // 2. Pasang HasApiTokens
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(User::class, 'parent_id');
    }

    public function role(): BelongsTo 
    {   
        return $this->belongsTo(Role::class, 'role_id');
    }
}
```

---

#### 3. Konfigurasi Routing & JSON Exceptions (`bootstrap/app.php`)

##### 📘 Penjelasan & Rationale:
Laravel 13 mengonfigurasi seluruh pipeline routing dan exception di `bootstrap/app.php`. Kita perlu memastikan:
1. File `routes/api.php` didaftarkan dengan prefix default `/api`.
2. Exception handler dikonfigurasi dengan `$exceptions->shouldRenderJsonWhen()` agar setiap error atau kegagalan otentikasi (401/404/422/500) pada endpoint `/api/*` selalu mengembalikan respons JSON murni, bukan redirect ke halaman web login HTML Blade.

##### 💻 Kode Konfigurasi (`bootstrap/app.php`):
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php', // Daftarkan rute REST API
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Otomatis render JSON bila request mengarah ke endpoint API
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
```

---

### 📍 SUB-STEP 12.2: STANDARISASI FORMAT JSON RESPONSE & TRAIT HELPER

---

#### 1. Rationale Kontrak Data Standar

Aplikasi mobile (Flutter / React Native) membutuhkan respons JSON yang seragam (*envelope structure*) agar *state management* (BLoC, Riverpod, Redux) dan *error handling* dapat diproses dengan rapi:
- **`success`**: `boolean` (`true` jika berhasil, `false` jika terjadi kesalahan).
- **`message`**: `string` (Pesan penjelasan untuk user interface).
- **`data`**: `object / array / null` (Payload data utama).
- **`errors`**: `object / null` (Pesan rincian validasi per field jika ada).

---

#### 2. Tabel HTTP Status Codes Standar

| HTTP Status | Keterangan | Kapan Digunakan? |
| :--- | :--- | :--- |
| **`200 OK`** | Permintaan Berhasil | Pengambilan data (GET), pembaruan data (PUT/PATCH), logout. |
| **`201 Created`** | Resource Dibuat | Sukses membuat pengajuan izin baru atau menyimpan jurnal baru (POST). |
| **`401 Unauthorized`** | Tidak Terautentikasi | Token Bearer tidak dikirim, salah, atau telah kadaluarsa/dicabut. |
| **`403 Forbidden`** | Akses Dilarang | Siswa mencoba mengakses endpoint khusus Guru. |
| **`404 Not Found`** | Tidak Ditemukan | Resource ID tidak ditemukan di database. |
| **`422 Unprocessable`** | Validasi Gagal | Format input form tidak valid (misal: password kosong, tanggal terbalik). |
| **`500 Server Error`** | Kesalahan Server | Kesalahan internal server / exception tak terduga. |

---

#### 3. Trait Response Helper (`app/Traits/ApiResponser.php`)

##### 📘 Penjelasan & Rationale:
Menyediakan method reusable `$this->successResponse()` dan `$this->errorResponse()` untuk semua controller API sesuai prinsip DRY.

```php
<?php 

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponser
{
    /**
     * Mengembalikan respons sukses terstandarisasi.
     */
    protected function successResponse(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $code);
    }

    /**
     * Mengembalikan respons error terstandarisasi.
     */
    protected function errorResponse(string $message = 'Error', int $code = 400, mixed $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $code);
    }
}
```

---

### 📍 SUB-STEP 12.3: FORM REQUEST VALIDATION CLASSES (LARAVEL BEST PRACTICE)

Sesuai aturan **Laravel Best Practices (Rules: Validation & Keep Controllers Thin)**:
> *"Extract validation from controllers into dedicated Form Request classes. Always use array notation for rules and get data via `$request->validated()`."*

Kita membuat Form Request terpisah di `app/Http/Requests/Api/`:

---

#### 1. Form Request Login (`app/Http/Requests/Api/LoginRequest.php`)

##### ⌨️ Perintah Pembuatan:
```bash
php artisan make:request Api/LoginRequest
```

##### 💻 Kode Form Request:
```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Custom response bila validasi gagal (mengembalikan status 422 JSON standar).
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi kredensial login gagal.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

---

#### 2. Form Request Pengajuan Izin (`app/Http/Requests/Api/StoreLeaveRequest.php`)

##### ⌨️ Perintah Pembuatan:
```bash
php artisan make:request Api/StoreLeaveRequest
```

##### 💻 Kode Form Request:
```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date'       => ['required', 'date'],
            'end_date'         => ['required', 'date', 'after_or_equal:start_date'],
            'reason_type'      => ['required', 'in:Sakit,Izin'],
            'description'      => ['required', 'string', 'max:500'],
            'attachment_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi pengajuan perizinan gagal.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

---

#### 3. Form Request Input Jurnal Mengajar (`app/Http/Requests/Api/StoreJournalRequest.php`)

##### ⌨️ Perintah Pembuatan:
```bash
php artisan make:request Api/StoreJournalRequest
```

##### 💻 Kode Form Request:
```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreJournalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_id'       => ['required', 'integer', 'exists:schedules,id'],
            'date'              => ['required', 'date'],
            'teaching_material' => ['required', 'string', 'max:1000'],
            'notes'             => ['nullable', 'string', 'max:500'],
            'activity_photo'    => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:3072'],
            'attendances'       => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'integer', 'exists:users,id'],
            'attendances.*.status'     => ['required', 'in:Hadir,Sakit,Izin,Alpa'],
            'attendances.*.notes'      => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi input jurnal dan presensi gagal.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
```

---

### 📍 SUB-STEP 12.4: API RESOURCES (DATA TRANSFORMERS)

Sesuai aturan **Laravel Best Practices (Rules: Routing & Architecture)**:
> *"Use Eloquent API Resources (`JsonResource`) to transform models into structured JSON responses, decoupling database schema changes from external mobile clients."*

---

#### 1. UserResource (`app/Http/Resources/Api/UserResource.php`)
```php
<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'full_name'   => $this->full_name,
            'username'    => $this->username,
            'email'       => $this->email,
            'role'        => $this->role?->name,
            'classroom'   => $this->classroom?->name,
            'parent_name' => $this->parent?->full_name,
        ];
    }
}
```

---

#### 2. LeaveRequestResource (`app/Http/Resources/Api/LeaveRequestResource.php`)
```php
<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LeaveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'reason_type'    => $this->reason_type,
            'start_date'     => $this->start_date?->format('Y-m-d'),
            'end_date'       => $this->end_date?->format('Y-m-d'),
            'description'    => $this->description,
            'attachment_url' => $this->attachment_photo ? Storage::url($this->attachment_photo) : null,
            'status'         => $this->status,
            'verified_by'    => $this->verifiedBy?->full_name,
            'created_at'     => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
```

---

#### 3. ScheduleResource (`app/Http/Resources/Api/ScheduleResource.php`)
```php
<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'subject_name'   => $this->subject_name,
            'classroom_name' => $this->classroom?->name,
            'room_name'      => $this->room?->name,
            'day_of_week'    => $this->day_of_week,
            'start_time'     => $this->start_time,
            'end_time'       => $this->end_time,
            'cycle_type'     => $this->cycle_type,
        ];
    }
}
```

---

### 📍 SUB-STEP 12.5: DAFTAR ENDPOINT & PAYLOAD CONTRACT SPESIFIKASI

Spesifikasi lengkap seluruh endpoint REST API v1 School Class Progress (SCP).

---

#### 🔑 1. MODUL AUTENTIKASI (`/api/v1/auth/*`)

##### A. Login User (`POST /api/v1/auth/login`)
- **Fungsi:** Mengautentikasi user dan menerbitkan Bearer Token Sanctum.
- **Headers:** `Accept: application/json`
- **Request Body (JSON):**
  ```json
  {
    "username": "siswa1",
    "password": "password123"
  }
  ```
- **Response Success (`200 OK`):**
  ```json
  {
    "success": true,
    "message": "Login berhasil",
    "data": {
      "token": "1|sanctum_plain_token_string_xxxxxxxx",
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

##### B. Profil User Terautentikasi (`GET /api/v1/auth/me`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Response Success (`200 OK`):** Mengembalikan data user yang login melalui `UserResource`.

##### C. Logout (`POST /api/v1/auth/logout`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Response Success (`200 OK`):**
  ```json
  {
    "success": true,
    "message": "Logout berhasil, token telah dicabut"
  }
  ```

---

#### 📱 2. MODUL SISWA & ORANG TUA (`/api/v1/student/*`)

##### A. Ringkasan Presensi Dashboard (`GET /api/v1/student/attendance-summary`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Response Success (`200 OK`):**
  ```json
  {
    "success": true,
    "message": "Ringkasan presensi berhasil diambil",
    "data": {
      "student_name": "Ahmad Siswa",
      "classroom": "XII RPL 1",
      "statistics": {
        "hadir": 18,
        "sakit": 1,
        "izin": 1,
        "alpa": 0,
        "total": 20,
        "percentage": 90.0
      },
      "recent_history": [
        {
          "id": 101,
          "date": "2026-08-15",
          "subject_name": "Pemrograman Web & Mobile",
          "teacher_name": "Pak Budi, S.Kom",
          "status": "Hadir",
          "notes": "Hadir tepat waktu"
        }
      ]
    }
  }
  ```

##### B. Daftar Riwayat Izin/Sakit (`GET /api/v1/student/leave-requests`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Response Success (`200 OK`):** Mengembalikan array data perizinan melalui `LeaveRequestResource`.

##### C. Buat Pengajuan Izin/Sakit Baru (`POST /api/v1/student/leave-requests`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Content-Type: multipart/form-data`
- **Request Form Data:**
  - `start_date` *(date: YYYY-MM-DD)*
  - `end_date` *(date: YYYY-MM-DD)*
  - `reason_type` *(Sakit | Izin)*
  - `description` *(string)*
  - `attachment_photo` *(file image/pdf max 2MB)*
- **Response Success (`201 Created`):**
  ```json
  {
    "success": true,
    "message": "Surat perizinan berhasil diajukan",
    "data": {
      "id": 15,
      "reason_type": "Sakit",
      "status": "Pending"
    }
  }
  ```

---

#### 👨‍🏫 3. MODUL GURU (`/api/v1/teacher/*`)

##### A. Jadwal Mengajar Putaran Aktif (`GET /api/v1/teacher/schedules`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Response Success (`200 OK`):** Mengembalikan daftar jadwal guru yang difilter dengan scope `forActiveCycle()`.

##### B. Peringatan Alert Jurnal Terlewat (`GET /api/v1/teacher/unfilled-alerts`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Accept: application/json`
- **Deskripsi:** Menghitung jadwal hari ini yang sudah lewat >30 menit dari jam selesai (`end_time`), tetapi belum memiliki entri jurnal.

##### C. Simpan Jurnal & Presensi Kelas (`POST /api/v1/teacher/journals`)
- **Headers:** `Authorization: Bearer <TOKEN>`, `Content-Type: multipart/form-data`
- **Request Payload:**
  - `schedule_id`: `1` (integer)
  - `date`: `2026-08-15` (date)
  - `teaching_material`: `Implementasi REST API Sanctum` (string)
  - `activity_photo`: File Foto Dokumentasi Kelas (file image max 3MB)
  - `attendances`: Array presensi siswa sekelas
- **Response Success (`201 Created`):**
  ```json
  {
    "success": true,
    "message": "Jurnal mengajar dan presensi siswa berhasil disimpan",
    "data": {
      "journal_id": 88,
      "total_attendances_saved": 36
    }
  }
  ```

---

#### ⚙️ 4. MODUL SYSTEM CONFIGURATION (`/api/v1/system/*`)

##### A. Status Konfigurasi Global (`GET /api/v1/system/config`)
- **Headers:** `Accept: application/json`
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

### 📍 SUB-STEP 12.6: IMPLEMENTASI CONTROLLER & ROUTING (LARAVEL 13 BEST PRACTICES)

Berikut adalah implementasi controller yang bersih, ringkas, dan mematuhi best practices:

---

#### 1. AuthController (`app/Http/Controllers/Api/AuthController.php`)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponser;

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::with(['role', 'classroom'])
            ->where('username', $credentials['username'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->errorResponse('Kredensial login tidak valid.', 401);
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->successResponse([
            'token'      => $token,
            'token_type' => 'Bearer',
            'user'       => new UserResource($user),
        ], 'Login berhasil');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'classroom', 'parent']);

        return $this->successResponse(new UserResource($user), 'Data profil berhasil diambil');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout berhasil, token telah dicabut');
    }
}
```

---

#### 2. StudentApiController (`app/Http/Controllers/Api/StudentApiController.php`)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreLeaveRequest;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentApiController extends Controller
{
    use ApiResponser;

    public function attendanceSummary(Request $request): JsonResponse
    {
        $user = $request->user();

        // Dukungan akses untuk akun Orang Tua (melihat data anak) atau Siswa
        /** @var User|null $student */
        $student = $user->role?->name === 'Orang Tua'
            ? $user->children()->first()
            : $user;

        if (! $student) {
            return $this->errorResponse('Data siswa tidak ditemukan', 404);
        }

        // Anti N+1 & whereBelongsTo
        $attendances = Attendance::whereBelongsTo($student, 'student')->get();

        $hadir = $attendances->where('status', 'Hadir')->count();
        $sakit = $attendances->where('status', 'Sakit')->count();
        $izin  = $attendances->where('status', 'Izin')->count();
        $alpa  = $attendances->where('status', 'Alpa')->count();
        $total = $attendances->count();

        $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

        $recentHistory = Attendance::with(['journal.schedule.teacher'])
            ->whereBelongsTo($student, 'student')
            ->latest('date')
            ->take(10)
            ->get()
            ->map(fn (Attendance $item) => [
                'id'           => $item->id,
                'date'         => $item->date?->format('Y-m-d'),
                'subject_name' => $item->journal?->schedule?->subject_name ?? '-',
                'teacher_name' => $item->journal?->teacher?->full_name ?? '-',
                'status'       => $item->status,
                'notes'        => $item->notes,
            ]);

        return $this->successResponse([
            'student_name' => $student->full_name,
            'classroom'    => $student->classroom?->name,
            'statistics'   => [
                'hadir'      => $hadir,
                'sakit'      => $sakit,
                'izin'       => $izin,
                'alpa'       => $alpa,
                'total'      => $total,
                'percentage' => $percentage,
            ],
            'recent_history' => $recentHistory,
        ], 'Ringkasan presensi berhasil diambil');
    }

    public function leaveRequests(Request $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->role?->name === 'Orang Tua' ? $user->children()->first() : $user;

        if (! $student) {
            return $this->errorResponse('Data siswa tidak ditemukan', 404);
        }

        $requests = LeaveRequest::with('verifiedBy')
            ->whereBelongsTo($student, 'student')
            ->latest()
            ->get();

        return $this->successResponse(LeaveRequestResource::collection($requests), 'Daftar perizinan berhasil diambil');
    }

    public function storeLeaveRequest(StoreLeaveRequest $request): JsonResponse
    {
        $user = $request->user();
        $student = $user->role?->name === 'Orang Tua' ? $user->children()->first() : $user;

        if (! $student) {
            return $this->errorResponse('Data siswa tidak ditemukan', 404);
        }

        $data = $request->validated();

        if ($request->hasFile('attachment_photo')) {
            $data['attachment_photo'] = $request->file('attachment_photo')->store('leave_attachments', 'public');
        }

        $data['student_id']   = $student->id;
        $data['submitted_by'] = $user->id;
        $data['status']       = 'Pending';

        $leaveRequest = LeaveRequest::create($data);

        return $this->successResponse(new LeaveRequestResource($leaveRequest), 'Surat perizinan berhasil diajukan', 201);
    }
}
```

---

#### 3. TeacherApiController (`app/Http/Controllers/Api/TeacherApiController.php`)

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJournalRequest;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherApiController extends Controller
{
    use ApiResponser;

    public function schedules(Request $request): JsonResponse
    {
        $teacher = $request->user();

        // Menggunakan scopeForActiveCycle() untuk jadwal putaran aktif
        $schedules = Schedule::with(['classroom', 'room'])
            ->whereBelongsTo($teacher, 'teacher')
            ->forActiveCycle()
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $this->successResponse(ScheduleResource::collection($schedules), 'Jadwal mengajar berhasil diambil');
    }

    public function unfilledAlerts(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $todayName = Carbon::now()->isoFormat('dddd');
        $currentTime = Carbon::now();

        $todaySchedules = Schedule::with('classroom')
            ->whereBelongsTo($teacher, 'teacher')
            ->forActiveCycle()
            ->where('day_of_week', $todayName)
            ->get();

        $alerts = [];
        foreach ($todaySchedules as $schedule) {
            $endTime = Carbon::createFromTimeString($schedule->end_time);
            $hasJournal = Journal::whereBelongsTo($schedule)->whereDate('date', Carbon::today())->exists();

            if (! $hasJournal && $currentTime->greaterThan($endTime->copy()->addMinutes(30))) {
                $alerts[] = [
                    'schedule_id'     => $schedule->id,
                    'classroom'       => $schedule->classroom?->name,
                    'subject_name'    => $schedule->subject_name,
                    'end_time'        => $schedule->end_time,
                    'minutes_late'    => (int) $currentTime->diffInMinutes($endTime),
                    'warning_message' => "Jadwal {$schedule->classroom?->name} telah selesai >30 menit lalu. Harap segera isi jurnal!",
                ];
            }
        }

        return $this->successResponse($alerts, 'Peringatan jurnal terlewat');
    }

    public function storeJournal(StoreJournalRequest $request): JsonResponse
    {
        $data = $request->validated();
        $teacher = $request->user();

        // Jalankan dalam Database Transaction demi integritas data
        $result = DB::transaction(function () use ($request, $data, $teacher) {
            $photoPath = $request->file('activity_photo')->store('journal_photos', 'public');

            $journal = Journal::create([
                'schedule_id'       => $data['schedule_id'],
                'teacher_id'        => $teacher->id,
                'date'              => $data['date'],
                'teaching_material' => $data['teaching_material'],
                'notes'             => $data['notes'] ?? null,
                'activity_photo'    => $photoPath,
            ]);

            // Batch insert data presensi
            foreach ($data['attendances'] as $att) {
                Attendance::create([
                    'journal_id' => $journal->id,
                    'student_id' => $att['student_id'],
                    'status'     => $att['status'],
                    'date'       => $data['date'],
                    'notes'      => $att['notes'] ?? null,
                ]);
            }

            return $journal;
        });

        return $this->successResponse([
            'journal_id'              => $result->id,
            'schedule_id'             => $result->schedule_id,
            'total_attendances_saved' => count($data['attendances']),
        ], 'Jurnal mengajar dan presensi siswa berhasil disimpan', 201);
    }
}
```

---

#### 4. Definisi File Routing REST API (`routes/api.php`)

```php
<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\TeacherApiController;
use App\Models\SystemConfiguration;
use Illuminate\Support\Facades\Route;

// Public Endpoints
Route::prefix('v1')->group(function () {
    Route::post('/auth/login', [AuthController::class, 'login']);
    
    // System Config
    Route::get('/system/config', fn () => response()->json([
        'success' => true,
        'message' => 'Konfigurasi sistem diambil',
        'data'    => SystemConfiguration::current(),
    ]));
});

// Authenticated Endpoints (Sanctum Middleware)
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Auth Routes
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Student & Parent Routes
    Route::prefix('student')->group(function () {
        Route::get('/attendance-summary', [StudentApiController::class, 'attendanceSummary']);
        Route::get('/leave-requests', [StudentApiController::class, 'leaveRequests']);
        Route::post('/leave-requests', [StudentApiController::class, 'storeLeaveRequest']);
    });

    // Teacher Routes
    Route::prefix('teacher')->group(function () {
        Route::get('/schedules', [TeacherApiController::class, 'schedules']);
        Route::get('/unfilled-alerts', [TeacherApiController::class, 'unfilledAlerts']);
        Route::post('/journals', [TeacherApiController::class, 'storeJournal']);
    });
});
```

---

### 📍 SUB-STEP 12.7: TESTING & POSTMAN COLLECTION GUIDE

---

#### 1. Header Wajib untuk Setiap Request
1. **`Accept: application/json`** *(Wajib di setiap request)*: Memastikan server mengembalikan format JSON.
2. **`Authorization: Bearer <TOKEN>`**: Disertakan pada semua endpoint yang terproteksi middleware `auth:sanctum`.

---

#### 2. Skenario Pengujian End-to-End di Postman
1. **Login Sesi:** Kirim `POST /api/v1/auth/login` dengan kredensial user, ambil `data.token`.
2. **Periksa Profil:** Kirim `GET /api/v1/auth/me` menggunakan Bearer Token tersebut.
3. **Cek Summary Presensi:** Kirim `GET /api/v1/student/attendance-summary` untuk melihat persentase kehadiran siswa.
4. **Unggah Surat Izin (Multipart Form):** Kirim `POST /api/v1/student/leave-requests` menggunakan format `form-data` beserta file foto surat bukti.
5. **Cek Peringatan Jurnal:** Kirim `GET /api/v1/teacher/unfilled-alerts` sebagai akun Guru.
6. **Input Jurnal & Presensi Sekelas:** Kirim `POST /api/v1/teacher/journals` dengan `form-data` foto kegiatan dan array JSON presensi.
7. **Logout:** Kirim `POST /api/v1/auth/logout` untuk mencabut token.

---

### 📍 SUB-STEP 12.8: CHECKLIST VERIFIKASI AKHIR REST API

- [ ] Paket Sanctum 4.x dan migrasi tabel `personal_access_tokens` telah terpasang.
- [ ] Model `User` menggunakan trait `HasApiTokens` dan PHP 8 Attributes (`#[Fillable]`).
- [ ] File `bootstrap/app.php` mengonfigurasi rute `api` dan `$exceptions->shouldRenderJsonWhen()`.
- [ ] Form Request (`LoginRequest`, `StoreLeaveRequest`, `StoreJournalRequest`) menangani validasi input.
- [ ] API Resources (`UserResource`, `LeaveRequestResource`, `ScheduleResource`) mengubah format output JSON.
- [ ] Controller mematuhi prinsip *Thin Controller*, *Eager Loading*, dan `DB::transaction()`.
- [ ] Seluruh endpoint mengembalikan HTTP status codes yang akurat (`200`, `201`, `401`, `422`).

---

🎉 **DOKUMEN STEP 12 TELAH DISESUAIKAN PENUH DENGAN LARAVEL 13 & LARAVEL BEST PRACTICES!**
