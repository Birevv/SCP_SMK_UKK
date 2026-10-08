# 📋 Implementation Plan — API Mobile SCP

> **Project**: SCP SMK (Sistem Catatan Pembelajaran)
> **Scope**: API Mobile untuk role **Siswa**, **Guru**, dan **Wali Kelas**
> **Tidak termasuk**: Role Waka Kurikulum (hanya di panel Filament admin)
> **Tanggal**: 31 Agustus 2026

---

## 📊 Status Saat Ini

### Code yang Sudah Ada

| Komponen | File | Status |
|----------|------|--------|
| Auth Controller | `app/Http/Controllers/Api/AuthController.php` | ✅ Logic sudah ada (login, me, logout) |
| Student Controller | `app/Http/Controllers/Api/StudentApiController.php` | ✅ Logic sudah ada (attendance, leave) |
| Login Request | `app/Http/Requests/Api/LoginRequest.php` | ✅ Sudah ada |
| Store Leave Request | `app/Http/Requests/Api/StoreLeaveRequest.php` | ✅ Sudah ada |
| Store Journal Request | `app/Http/Requests/Api/StoreJournalRequest.php` | ✅ Sudah ada |
| User Resource | `app/Http/Resources/Api/UserResource.php` | ✅ Sudah ada |
| Schedule Resource | `app/Http/Resources/Api/ScheduleResource.php` | ✅ Sudah ada |
| LeaveRequest Resource | `app/Http/Resources/Api/LeaveRequestResource.php` | ✅ Sudah ada |
| ApiResponser Trait | `app/Traits/ApiResponser.php` | ✅ Sudah ada |

### ⚠️ Masalah Utama

> **SEMUA route di `routes/api.php` BELUM TERDAFTAR.** File hanya berisi route default Laravel `/user`. Artinya meskipun controller sudah ditulis, tidak ada satupun endpoint yang bisa diakses dari mobile.

---

## 📋 Daftar Lengkap 21 Endpoint

| # | Endpoint | Method | Role | Status |
|---|----------|--------|------|--------|
| 1 | `/api/login` | POST | Public | 🟡 Code ada, route belum |
| 2 | `/api/logout` | POST | All | 🟡 Code ada, route belum |
| 3 | `/api/me` | GET | All | 🟡 Code ada, route belum |
| 4 | `/api/profile` | PUT | All | ❌ Belum ada |
| 5 | `/api/profile/password` | PUT | All | ❌ Belum ada |
| 6 | `/api/student/dashboard` | GET | Siswa | ❌ Belum ada |
| 7 | `/api/student/schedules/today` | GET | Siswa | ❌ Belum ada |
| 8 | `/api/student/attendance-summary` | GET | Siswa | 🟡 Code ada, route belum |
| 9 | `/api/student/attendance-history` | GET | Siswa | ❌ Belum ada |
| 10 | `/api/student/leave-requests` | GET | Siswa | 🟡 Code ada, route belum |
| 11 | `/api/student/leave-requests` | POST | Siswa | 🟡 Code ada, route belum |
| 12 | `/api/teacher/dashboard` | GET | Guru | ❌ Belum ada |
| 13 | `/api/teacher/schedules` | GET | Guru | ❌ Belum ada |
| 14 | `/api/teacher/schedules` | POST | Guru | ❌ Belum ada |
| 15 | `/api/teacher/schedules/{id}/students` | GET | Guru | ❌ Belum ada |
| 16 | `/api/teacher/journals` | POST | Guru | ❌ Belum ada |
| 17 | `/api/teacher/journals` | GET | Guru | ❌ Belum ada |
| 18 | `/api/homeroom/dashboard` | GET | Wali Kelas | ❌ Belum ada |
| 19 | `/api/homeroom/leave-requests` | GET | Wali Kelas | ❌ Belum ada |
| 20 | `/api/homeroom/leave-requests/{id}` | GET | Wali Kelas | ❌ Belum ada |
| 21 | `/api/homeroom/leave-requests/{id}/verify` | PUT | Wali Kelas | ❌ Belum ada |

**Legenda**: 🟡 = Code controller ada tapi route belum terdaftar | ❌ = Belum ada sama sekali

---

## 🔧 Langkah-Langkah Implementasi

### LANGKAH 1: Tambah Kolom `profile_photo` di Migration Users yang Sudah Ada

**Tujuan**: Mendukung fitur "Change Photo" pada screen Update Profil.

**File**: `database/migrations/0001_01_01_000000_create_users_table.php`

Tambahkan kolom `profile_photo` langsung di migration existing (bukan migration terpisah):

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->foreignId('role_id')->nullable();
    $table->foreignId('classroom_id')->nullable();
    $table->string('full_name');
    $table->string('username');
    $table->string('email')->nullable()->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->string('identity_number')->nullable()->unique()->comment('NIP for Teacher, NISN for Student');
    $table->string('phone')->nullable();
    $table->string('profile_photo')->nullable();  // ← TAMBAHKAN INI
    $table->enum('status', ['Aktif', 'Lulus', 'Keluar', 'DO'])->default('Aktif');
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
```

Kemudian jalankan fresh migration:
```bash
php artisan migrate:fresh --seed
```

> **Catatan**: Karena ini mengubah migration awal, perlu `migrate:fresh`. Pastikan data sudah di-backup atau gunakan seeder.

---

### LANGKAH 2: Update Model `User.php`

**File**: `app/Models/User.php`

**Perubahan**:
- Tambahkan `profile_photo` ke attribute `#[Fillable]`
- Tambahkan helper accessor untuk mendapatkan classroom singular (untuk siswa)

```php
#[Fillable(['role_id', 'full_name', 'username', 'email', 'password', 'identity_number', 'phone', 'status', 'profile_photo'])]
```

Tambahkan accessor:
```php
public function getProfilePhotoUrlAttribute(): ?string
{
    return $this->profile_photo
        ? Storage::url($this->profile_photo)
        : null;
}

// Helper: get single classroom for student
public function getClassroomAttribute()
{
    return $this->classrooms->first();
}
```

---

### LANGKAH 3: Update `UserResource.php`

**File**: `app/Http/Resources/Api/UserResource.php`

**Perubahan**: Tambah field `profile_photo_url`

```php
public function toArray(Request $request): array
{
    return [
        'id' => $this->id,
        'full_name' => $this->full_name,
        'identity_number' => $this->identity_number,
        'username' => $this->username,
        'email' => $this->email,
        'phone' => $this->phone,
        'profile_photo_url' => $this->profile_photo_url,
        'role' => $this->role?->name,
        'classroom' => $this->classrooms->pluck('name')->join(', '),
    ];
}
```

---

### LANGKAH 4: Buat Form Requests Baru

> Semua form request baru harus include `failedValidation()` override agar response error berbentuk JSON.

#### 4a. `UpdateProfileRequest.php`

**Buat file baru**: `app/Http/Requests/Api/UpdateProfileRequest.php`

```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'profile_photo' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi Update Profile Gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
```

#### 4b. `ChangePasswordRequest.php`

**Buat file baru**: `app/Http/Requests/Api/ChangePasswordRequest.php`

```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi Ganti Password Gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
```

#### 4c. `StoreScheduleRequest.php`

**Buat file baru**: `app/Http/Requests/Api/StoreScheduleRequest.php`

```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day_of_week' => ['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'room_id' => ['required', 'integer', 'exists:rooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'cycle_type' => ['required', 'in:Putaran 1,Putaran 2'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi Input Jadwal Gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
```

#### 4d. `VerifyLeaveRequest.php`

**Buat file baru**: `app/Http/Requests/Api/VerifyLeaveRequest.php`

```php
<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class VerifyLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:Approved,Rejected'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validasi Verifikasi Izin Gagal.',
            'errors' => $validator->errors(),
        ], 422));
    }
}   
```

---

### LANGKAH 5: Buat API Resources Baru

#### 5a. `JournalResource.php`

**Buat file baru**: `app/Http/Resources/Api/JournalResource.php`

```php
<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class JournalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'subject_name' => $this->schedule?->subject?->name,
            'classroom_name' => $this->schedule?->classroom?->name,
            'room_name' => $this->schedule?->room?->name,
            'cycle_type' => $this->schedule?->cycle_type,
            'teaching_material' => $this->teaching_material,
            'notes' => $this->notes,
            'activity_photo_url' => $this->activity_photo ? Storage::url($this->activity_photo) : null,
            'attendance_count' => $this->attendances_count ?? $this->attendances->count(),
            'present_count' => $this->attendances->where('status', 'Hadir')->count(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
```

#### 5b. `AttendanceHistoryResource.php`

**Buat file baru**: `app/Http/Resources/Api/AttendanceHistoryResource.php`

```php
<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->format('Y-m-d'),
            'status' => $this->status,
            'notes' => $this->notes,
            'subject_name' => $this->journal?->schedule?->subject?->name,
            'teacher_name' => $this->journal?->schedule?->teacher?->full_name,
            'classroom_name' => $this->journal?->schedule?->classroom?->name,
            'start_time' => $this->journal?->schedule?->start_time,
            'end_time' => $this->journal?->schedule?->end_time,
        ];
    }
}
```

---

### LANGKAH 6: Extend `AuthController.php` — Profile & Password

**File yang dimodifikasi**: `app/Http/Controllers/Api/AuthController.php`

**Tambah 2 method baru** di dalam class `AuthController`:

```php
// Tambahkan import berikut di bagian atas file:
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Requests\Api\ChangePasswordRequest;
use Illuminate\Support\Facades\Storage;

// Tambahkan method berikut di dalam class AuthController:

public function updateProfile(UpdateProfileRequest $request): JsonResponse
{
    $user = $request->user();
    $data = $request->validated();

    if ($request->hasFile('profile_photo')) {
        // Hapus foto lama jika ada
        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }
        $data['profile_photo'] = $request->file('profile_photo')->store('profile_photos', 'public');
    }

    $user->update($data);
    $user->load(['role', 'classrooms']);

    return $this->successResponse(new UserResource($user), 'Profile berhasil diperbarui');
}

public function changePassword(ChangePasswordRequest $request): JsonResponse
{
    $user = $request->user();
    $user->update([
        'password' => $request->validated('new_password'),
    ]);
 
    return $this->successResponse(null, 'Password berhasil diubah');
}
```

---

### LANGKAH 7: Extend `StudentApiController.php` — Dashboard, Schedule, History

**File yang dimodifikasi**: `app/Http/Controllers/Api/StudentApiController.php`

**Tambah 3 method baru** di dalam class `StudentApiController`:

```php
// Tambahkan import berikut di bagian atas file:
use App\Http\Resources\Api\AttendanceHistoryResource;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\Schedule;
use App\Models\SystemConfiguration;

// Tambahkan method berikut di dalam class StudentApiController:

public function dashboard(Request $request): JsonResponse
{
    $student = $request->user();
    $today = Carbon::today();
    $dayName = $today->locale('id')->isoFormat('dddd'); // Senin, Selasa, dst.

    // Kelas siswa
    $classroomIds = $student->classrooms()->pluck('classrooms.id');
    $classroomName = $student->classrooms->pluck('name')->join(', ');
    // Ringkasan presensi bulan ini
    $attendances = Attendance::whereBelongsTo($student, 'student')
        ->whereMonth('date', $today->month)
        ->whereYear('date', $today->year)
        ->get();

    $hadir = $attendances->where('status', 'Hadir')->count();
    $sakit = $attendances->where('status', 'Sakit')->count();
    $izin  = $attendances->where('status', 'Izin')->count();
    $alpa  = $attendances->where('status', 'Alpa')->count();
    $total = $attendances->count();
    $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 0;

    // Jadwal hari ini
    $todaySchedules = Schedule::with(['subject', 'classroom', 'room'])
        ->whereIn('classroom_id', $classroomIds)
        ->where('day_of_week', $dayName)
        ->forActiveCycle()
        ->orderBy('start_time')
        ->get();

    // Pending leave requests
    $pendingLeaveCount = LeaveRequest::whereBelongsTo($student, 'student')
        ->where('status', 'Pending')
        ->count();

    return $this->successResponse([
        'student_name' => $student->full_name,
        'classroom' => $classroomName,
        'current_date' => $today->locale('id')->isoFormat('dddd, D MMMM Y'),
        'attendance_summary' => [
            'month' => $today->locale('id')->isoFormat('MMMM'),
            'hadir' => $hadir,
            'sakit' => $sakit,
            'izin' => $izin,
            'alpa' => $alpa,
            'total' => $total,
            'percentage' => $percentage,
        ],
        'today_schedules' => ScheduleResource::collection($todaySchedules),
        'pending_leave_count' => $pendingLeaveCount,
    ], 'Dashboard siswa berhasil diambil');
}

public function todaySchedule(Request $request): JsonResponse
{
    $student = $request->user();
    $dayName = Carbon::today()->locale('id')->isoFormat('dddd');

    $classroomIds = $student->classrooms()->pluck('classrooms.id');

    $schedules = Schedule::with(['subject', 'classroom', 'room'])
        ->whereIn('classroom_id', $classroomIds)
        ->where('day_of_week', $dayName)
        ->forActiveCycle()
        ->orderBy('start_time')
        ->get();

    return $this->successResponse(
        ScheduleResource::collection($schedules),
        'Jadwal hari ini berhasil diambil'
    );
}

public function attendanceHistory(Request $request): JsonResponse
{
    $student = $request->user();

    $query = Attendance::with(['journal.schedule.subject', 'journal.schedule.classroom', 'journal.teacher'])
        ->whereBelongsTo($student, 'student')
        ->latest('date');

    // Filter bulan
    if ($request->filled('month')) {
        $query->whereMonth('date', $request->month);
    }

    // Filter tahun
    if ($request->filled('year')) {
        $query->whereYear('date', $request->year);
    }

    $perPage = $request->input('per_page', 20);
    $attendances = $query->paginate($perPage);

    return $this->successResponse(
        AttendanceHistoryResource::collection($attendances)->response()->getData(true),
        'Riwayat presensi berhasil diambil'
    );
}
```

---

### LANGKAH 8: Buat Controller Guru Berdasarkan Resource (`app/Http/Controllers/Api/Teacher/`) (Baru)

Untuk menjaga prinsip **Single Responsibility Principle (SRP)** dan menghindari file controller yang terlalu besar (*fat controller*), fungsionalitas Guru dibagi ke dalam sub-namespace `App\Http\Controllers\Api\Teacher\` dengan 3 controller terpisah:

1. `DashboardController.php` — Menangani ringkasan dashboard, jadwal hari ini, & peringatan jurnal.
2. `ScheduleController.php` — Menangani jadwal mengajar guru (`index`, `store`) dan data siswa per jadwal (`students`).
3. `JournalController.php` — Menangani pencatatan jurnal & presensi (`store`) serta riwayat jurnal (`index`).

#### 8a. `app/Http/Controllers/Api/Teacher/DashboardController.php`

```php
<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponser;

    /**
     * Ambil data dashboard guru (jadwal hari ini, status konfigurasi, & peringatan jurnal).
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $today = Carbon::today();
        $dayName = $today->locale('id')->isoFormat('dddd');
        $config = SystemConfiguration::current();

        $todaySchedules = Schedule::with(['subject', 'classroom', 'room'])
            ->where('teacher_id', $teacher->id)
            ->where('day_of_week', $dayName)
            ->forActiveCycle()
            ->orderBy('start_time')
            ->get()
            ->map(function (Schedule $schedule) use ($today) {
                $hasJournal = Journal::where('schedule_id', $schedule->id)
                    ->where('date', $today)
                    ->exists();

                return [
                    'id' => $schedule->id,
                    'subject_name' => $schedule->subject?->name,
                    'classroom_name' => $schedule->classroom?->name,
                    'room_name' => $schedule->room?->name,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'has_journal' => $hasJournal,
                ];
            });

        $now = Carbon::now();
        $pendingJournals = $todaySchedules->filter(function ($s) use ($now) {
            $endTime = Carbon::parse($s['end_time']);
            return !$s['has_journal'] && $now->greaterThan($endTime->addMinutes(30));
        })->values()->map(fn ($s) => [
            'schedule_id' => $s['id'],
            'subject_name' => $s['subject_name'],
            'classroom_name' => $s['classroom_name'],
            'message' => 'Jurnal belum diisi (>30 menit terlambat)',
        ]);

        return $this->successResponse([
            'teacher_name' => $teacher->full_name,
            'current_time' => $now->format('H:i'),
            'current_date' => $today->locale('id')->isoFormat('dddd, D MMMM Y'),
            'today_schedules' => $todaySchedules,
            'pending_journals' => $pendingJournals,
            'active_cycle' => $config->active_cycle,
            'is_schedule_locked' => $config->is_schedule_locked,
        ], 'Data dashboard guru berhasil diambil');
    }

    /**
     * Alias untuk dashboard()
     */
    public function dashboard(Request $request): JsonResponse
    {
        return $this->index($request);
    }
}
```

---

#### 8b. `app/Http/Controllers/Api/Teacher/ScheduleController.php`

```php
<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScheduleRequest;
use App\Http\Resources\Api\ScheduleResource;
use App\Models\AcademicYear;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use ApiResponser;

    /**
     * Ambil seluruh jadwal mengajar milik guru yang login, dikelompokkan per hari.
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $config = SystemConfiguration::current();

        $query = Schedule::with(['subject', 'classroom', 'room'])
            ->where('teacher_id', $teacher->id);

        if ($request->filled('cycle_type')) {
            $query->where('cycle_type', $request->cycle_type);
        } else {
            $query->forActiveCycle();
        }

        if ($request->filled('classroom_id')) {
            $query->where('classroom_id', $request->classroom_id);
        }

        $schedules = $query->orderBy('start_time')->get();

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $grouped = [];
        foreach ($days as $day) {
            $grouped[$day] = ScheduleResource::collection(
                $schedules->where('day_of_week', $day)->values()
            );
        }

        return $this->successResponse([
            'schedules' => $grouped,
            'active_cycle' => $config->active_cycle,
            'is_schedule_locked' => $config->is_schedule_locked,
        ], 'Data jadwal berhasil diambil');
    }

    /**
     * Simpan jadwal mengajar baru dengan validasi bentrok waktu & ruang/kelas/guru.
     */
    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $teacher = $request->user();
        $config = SystemConfiguration::current();

        if ($config->is_schedule_locked) {
            return $this->errorResponse('Jadwal telah dikunci oleh Waka Kurikulum. Tidak dapat menambah jadwal.', 403);
        }

        $data = $request->validated();

        $conflict = Schedule::where('day_of_week', $data['day_of_week'])
            ->where('cycle_type', $data['cycle_type'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->where(function ($q) use ($teacher, $data) {
                $q->where('teacher_id', $teacher->id)
                  ->orWhere('classroom_id', $data['classroom_id'])
                  ->orWhere('room_id', $data['room_id']);
            })
            ->with(['teacher', 'classroom', 'room'])
            ->first();

        if ($conflict) {
            $detail = 'Bentrok dengan: ';
            if ($conflict->teacher_id == $teacher->id) {
                $detail .= 'Jadwal Anda Sendiri';
            } elseif ($conflict->classroom_id == $data['classroom_id']) {
                $detail .= 'Kelas ' . $conflict->classroom?->name;
            } else {
                $detail .= 'Ruang ' . $conflict->room?->name;
            }

            return $this->errorResponse('Jadwal bentrok terdeteksi', 422, [
                'conflict_type' => 'schedule',
                'detail' => $detail,
                'conflicting_schedule' => new ScheduleResource($conflict),
            ]);
        }

        $data['teacher_id'] = $teacher->id;
        $data['academic_year_id'] = Schedule::where('teacher_id', $teacher->id)->latest()->value('academic_year_id')
            ?? AcademicYear::latest()->value('id');

        $schedule = Schedule::create($data);
        $schedule->load(['subject', 'classroom', 'room']);

        return $this->successResponse(new ScheduleResource($schedule), 'Jadwal berhasil ditambahkan', 201);
    }

    /**
     * Ambil daftar siswa pada jadwal tertentu berserta status izin yang sudah diapprove hari ini.
     */
    public function students(Schedule $schedule, Request $request): JsonResponse
    {
        $teacher = $request->user();

        if ($schedule->teacher_id !== $teacher->id) {
            return $this->errorResponse('Anda tidak memiliki akses ke jadwal ini', 403);
        }

        $classroomId = $schedule->classroom_id;
        $today = Carbon::today();

        $students = User::whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
            ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroomId))
            ->orderBy('full_name')
            ->get()
            ->map(function (User $student) use ($today) {
                $approvedLeave = LeaveRequest::where('student_id', $student->id)
                    ->where('status', 'Approved')
                    ->where('start_date', '<=', $today)
                    ->where('end_date', '>=', $today)
                    ->first();

                return [
                    'id' => $student->id,
                    'full_name' => $student->full_name,
                    'identity_number' => $student->identity_number,
                    'has_approved_leave' => $approvedLeave !== null,
                    'leave_reason' => $approvedLeave?->reason_type,
                ];
            });

        return $this->successResponse([
            'schedule' => [
                'id' => $schedule->id,
                'subject_name' => $schedule->subject?->name,
                'classroom_name' => $schedule->classroom?->name,
                'room_name' => $schedule->room?->name,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ],
            'students' => $students,
        ], 'Data siswa berhasil diambil');
    }

    // Method aliases untuk kompatibilitas
    public function mySchedules(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function storeSchedule(StoreScheduleRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    public function getStudents(Schedule $schedule, Request $request): JsonResponse
    {
        return $this->students($schedule, $request);
    }
}
```

---

#### 8c. `app/Http/Controllers/Api/Teacher/JournalController.php`

```php
<?php

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreJournalRequest;
use App\Http\Resources\Api\JournalResource;
use App\Models\Attendance;
use App\Models\Journal;
use App\Models\Schedule;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JournalController extends Controller
{
    use ApiResponser;

    /**
     * Ambil riwayat jurnal mengajar milik guru yang login (dengan filter bulan, tahun, & cycle).
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $query = Journal::with(['schedule.subject', 'schedule.classroom', 'attendances'])
            ->where('teacher_id', $teacher->id)
            ->latest('date');

        // Filter bulan
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        // Filter tahun
        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }

        // Filter cycle
        if ($request->filled('cycle_type')) {
            $query->whereHas('schedule', fn ($q) => $q->where('cycle_type', $request->cycle_type));
        }

        $perPage = $request->input('per_page', 15);
        $journals = $query->paginate($perPage);

        return $this->successResponse(
            JournalResource::collection($journals)->response()->getData(true),
            'Riwayat jurnal berhasil diambil'
        );
    }

    /**
     * Simpan jurnal pembelajaran dan data presensi siswa.
     */
    public function store(StoreJournalRequest $request): JsonResponse
    {
        $teacher = $request->user();
        $data = $request->validated();

        $schedule = Schedule::findOrFail($data['schedule_id']);
        if ($schedule->teacher_id !== $teacher->id) {
            return $this->errorResponse('Anda tidak memiliki akses ke jadwal ini', 403);
        }

        if ($request->hasFile('activity_photo')) {
            $data['activity_photo'] = $request->file('activity_photo')->store('journal_photos', 'public');
        }

        $data['teacher_id'] = $teacher->id;
        $attendancesData = $data['attendances'];
        unset($data['attendances']);

        $journal = Journal::create($data);

        foreach ($attendancesData as $att) {
            Attendance::updateOrCreate(
                [
                    'journal_id' => $journal->id,
                    'student_id' => $att['student_id'],
                ],
                [
                    'status' => $att['status'],
                    'date' => $data['date'],
                    'notes' => $att['notes'] ?? null,
                ]
            );
        }

        $journal->load(['schedule.subject', 'schedule.classroom', 'attendances']);

        return $this->successResponse(new JournalResource($journal), 'Jurnal dan presensi berhasil disimpan', 201);
    }

    // Method aliases untuk kompatibilitas
    public function journalHistory(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    public function storeJournal(StoreJournalRequest $request): JsonResponse
    {
        return $this->store($request);
    }
}
```

---
### LANGKAH 9: Buat Controller Wali Kelas Berdasarkan Resource (`app/Http/Controllers/Api/Homeroom/`) (Baru)

Sama seperti Controller Guru, untuk menjaga prinsip **Single Responsibility Principle (SRP)** dan kemudahan pemeliharaan kode, fungsionalitas Wali Kelas dibagi ke dalam sub-namespace `App\Http\Controllers\Api\Homeroom\` menjadi 2 controller terpisah:

1. `DashboardController.php` — Menangani ringkasan kehadiran siswa kelas hari ini dan daftar pengajuan izin pending.
2. `LeaveRequestController.php` — Menangani daftar pengajuan izin siswa (`index`), detail izin (`show`), dan verifikasi persetujuan/penolakan izin (`verify`).

> **Penentuan Wali Kelas**: Guru yang terhubung ke kelas melalui pivot table `user_class` dianggap sebagai wali kelas dari kelas tersebut.

---

#### 9a. `app/Http/Controllers/Api/Homeroom/DashboardController.php`

```php
<?php

namespace App\Http\Controllers\Api\Homeroom;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Traits\ApiResponser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponser;

    /**
     * Ambil kelas yang di-wali-kelasi oleh guru yang login.
     */
    protected function getHomeroomClassroom(User $teacher)
    {
        return $teacher->classrooms()->first();
    }

    /**
     * Ambil semua student IDs di kelas wali kelas.
     */
    protected function getStudentIds(int $classroomId)
    {
        return User::whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
            ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroomId))
            ->pluck('id');
    }

    /**
     * Ambil ringkasan dashboard wali kelas (presensi kelas hari ini & daftar pengajuan izin pending).
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $classroom = $this->getHomeroomClassroom($teacher);

        if (!$classroom) {
            return $this->errorResponse('Anda belum ditugaskan sebagai wali kelas', 403);
        }

        $studentIds = $this->getStudentIds($classroom->id);
        $today = Carbon::today();

        // Ringkasan kehadiran hari ini
        $todayAttendances = Attendance::whereIn('student_id', $studentIds)
            ->whereDate('date', $today)
            ->get();

        $hadir = $todayAttendances->where('status', 'Hadir')->count();
        $izin  = $todayAttendances->where('status', 'Izin')->count();
        $sakit = $todayAttendances->where('status', 'Sakit')->count();
        $alpa  = $todayAttendances->where('status', 'Alpa')->count();
        $totalStudents = $studentIds->count();
        $percentage = $totalStudents > 0 ? round(($hadir / $totalStudents) * 100, 1) : 0;

        // Pending leave requests (max 5 terbaru)
        $pendingLeaves = LeaveRequest::with('student')
            ->whereIn('student_id', $studentIds)
            ->where('status', 'Pending')
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (LeaveRequest $lr) => [
                'id' => $lr->id,
                'student_name' => $lr->student?->full_name,
                'reason_type' => $lr->reason_type,
                'description' => $lr->description,
                'start_date' => $lr->start_date?->format('Y-m-d'),
                'end_date' => $lr->end_date?->format('Y-m-d'),
                'status' => $lr->status,
            ]);

        $pendingCount = LeaveRequest::whereIn('student_id', $studentIds)
            ->where('status', 'Pending')
            ->count();

        return $this->successResponse([
            'teacher_name' => $teacher->full_name,
            'classroom_name' => $classroom->name,
            'attendance_today' => [
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpa' => $alpa,
                'total_students' => $totalStudents,
                'percentage' => $percentage,
            ],
            'pending_leave_requests' => $pendingLeaves,
            'pending_count' => $pendingCount,
        ], 'Dashboard wali kelas berhasil diambil');
    }

    /**
     * Alias untuk dashboard()
     */
    public function dashboard(Request $request): JsonResponse
    {
        return $this->index($request);
    }
}
```

---

#### 9b. `app/Http/Controllers/Api/Homeroom/LeaveRequestController.php`

```php
<?php

namespace App\Http\Controllers\Api\Homeroom;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerifyLeaveRequest;
use App\Http\Resources\Api\LeaveRequestResource;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaveRequestController extends Controller
{
    use ApiResponser;

    /**
     * Ambil kelas yang di-wali-kelasi oleh guru yang login.
     */
    protected function getHomeroomClassroom(User $teacher)
    {
        return $teacher->classrooms()->first();
    }

    /**
     * Ambil semua student IDs di kelas wali kelas.
     */
    protected function getStudentIds(int $classroomId)
    {
        return User::whereHas('role', fn ($q) => $q->where('name', 'Siswa'))
            ->whereHas('classrooms', fn ($q) => $q->where('classrooms.id', $classroomId))
            ->pluck('id');
    }

    /**
     * Ambil seluruh pengajuan izin siswa di kelas wali kelas (dengan filter status & pagination).
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $classroom = $this->getHomeroomClassroom($teacher);

        if (!$classroom) {
            return $this->errorResponse('Anda belum ditugaskan sebagai wali kelas', 403);
        }

        $studentIds = $this->getStudentIds($classroom->id);

        $query = LeaveRequest::with(['student', 'verifiedBy'])
            ->whereIn('student_id', $studentIds)
            ->latest();

        // Filter status
        if ($request->filled('status') && $request->status !== 'All') {
            $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 15);
        $leaveRequests = $query->paginate($perPage);

        return $this->successResponse(
            LeaveRequestResource::collection($leaveRequests)->response()->getData(true),
            'Daftar pengajuan izin berhasil diambil'
        );
    }

    /**
     * Detail pengajuan izin siswa.
     */
    public function show(LeaveRequest $leaveRequest, Request $request): JsonResponse
    {
        $teacher = $request->user();
        $classroom = $this->getHomeroomClassroom($teacher);

        if (!$classroom) {
            return $this->errorResponse('Anda belum ditugaskan sebagai wali kelas', 403);
        }

        // Pastikan leave request milik siswa di kelas wali kelas
        $studentIds = $this->getStudentIds($classroom->id);
        if (!$studentIds->contains($leaveRequest->student_id)) {
            return $this->errorResponse('Pengajuan izin ini bukan milik siswa di kelas Anda', 403);
        }

        $leaveRequest->load('student');

        return $this->successResponse([
            'id' => $leaveRequest->id,
            'student' => [
                'full_name' => $leaveRequest->student?->full_name,
                'identity_number' => $leaveRequest->student?->identity_number,
                'classroom' => $classroom->name,
            ],
            'leave_info' => [
                'reason_type' => $leaveRequest->reason_type,
                'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
                'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
                'duration_days' => $leaveRequest->start_date && $leaveRequest->end_date
                    ? $leaveRequest->start_date->diffInDays($leaveRequest->end_date) + 1
                    : null,
                'description' => $leaveRequest->description,
                'attachment_url' => $leaveRequest->attachment_photo ? Storage::url($leaveRequest->attachment_photo) : null,
                'status' => $leaveRequest->status,
            ],
            'submitted_at' => $leaveRequest->created_at?->format('Y-m-d H:i:s'),
        ], 'Detail pengajuan izin berhasil diambil');
    }

    /**
     * Verifikasi (Approve/Reject) pengajuan izin siswa.
     */
    public function verify(VerifyLeaveRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $teacher = $request->user();
        $classroom = $this->getHomeroomClassroom($teacher);

        if (!$classroom) {
            return $this->errorResponse('Anda belum ditugaskan sebagai wali kelas', 403);
        }

        // Pastikan leave request milik siswa di kelas wali kelas
        $studentIds = $this->getStudentIds($classroom->id);
        if (!$studentIds->contains($leaveRequest->student_id)) {
            return $this->errorResponse('Pengajuan izin ini bukan milik siswa di kelas Anda', 403);
        }

        if ($leaveRequest->status !== 'Pending') {
            return $this->errorResponse('Pengajuan izin ini sudah diverifikasi sebelumnya', 422);
        }

        $data = $request->validated();
        $leaveRequest->update([
            'status' => $data['status'],
            'verified_by' => $teacher->id,
        ]);

        // Sinkronisasi attendance otomatis via LeaveRequestObserver

        $statusText = $data['status'] === 'Approved' ? 'disetujui' : 'ditolak';

        return $this->successResponse(
            new LeaveRequestResource($leaveRequest->fresh()->load('verifiedBy')),
            "Pengajuan izin berhasil {$statusText}"
        );
    }

    /**
     * Alias untuk leaveRequests()
     */
    public function leaveRequests(Request $request): JsonResponse
    {
        return $this->index($request);
    }
}
```

---
### LANGKAH 10: Buat `ReferenceController.php` (Baru)

**Buat file baru**: `app/Http/Controllers/Api/ReferenceController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\Subject;
use App\Traits\ApiResponser;
use Illuminate\Http\JsonResponse;

class ReferenceController extends Controller
{
    use ApiResponser;

    public function classrooms(): JsonResponse
    {
        $classrooms = Classroom::select('id', 'name')->orderBy('name')->get();

        return $this->successResponse($classrooms, 'Daftar kelas berhasil diambil');
    }

    public function rooms(): JsonResponse
    {
        $rooms = Room::select('id', 'name')->orderBy('name')->get();

        return $this->successResponse($rooms, 'Daftar ruang berhasil diambil');
    }

    public function subjects(): JsonResponse
    {
        $subjects = Subject::select('id', 'name')->orderBy('name')->get();

        return $this->successResponse($subjects, 'Daftar mata pelajaran berhasil diambil');
    }
}
```

---

### LANGKAH 11: Daftarkan Semua Route di `api.php`

**File**: `routes/api.php`

Ini adalah langkah paling kritikal — tanpa ini, tidak ada endpoint yang bisa diakses.

```php
// routes/api.php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Homeroom\DashboardController as HomeroomDashboardController;
use App\Http\Controllers\Api\Homeroom\LeaveRequestController as HomeroomLeaveRequestController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\Api\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Api\Teacher\JournalController as TeacherJournalController;
use App\Http\Controllers\Api\Teacher\ScheduleController as TeacherScheduleController;
use Illuminate\Support\Facades\Route;
```

#### 11a. Public Routes (Tanpa Auth)

| Method | Endpoint | Controller | Deskripsi |
|--------|----------|------------|-----------|
| POST | `/api/login` | `AuthController@login` | Login, dapatkan token |

```php
Route::post('/login', [AuthController::class, 'login']);
```

#### 11b. Auth & Profile (Semua Role)

| Method | Endpoint | Controller | Deskripsi |
|--------|----------|------------|-----------|
| POST | `/api/logout` | `AuthController@logout` | Logout, revoke token |
| GET | `/api/me` | `AuthController@me` | Get data user login |
| PUT | `/api/profile` | `AuthController@updateProfile` | Update nama, phone, foto |
| PUT | `/api/profile/password` | `AuthController@changePassword` | Ganti password |

```php
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/profile/password', [AuthController::class, 'changePassword']);
```

#### 11c. Reference Data (Untuk Dropdown Form)

| Method | Endpoint | Controller | Deskripsi |
|--------|----------|------------|-----------|
| GET | `/api/references/classrooms` | `ReferenceController@classrooms` | Daftar kelas |
| GET | `/api/references/rooms` | `ReferenceController@rooms` | Daftar ruang |
| GET | `/api/references/subjects` | `ReferenceController@subjects` | Daftar mata pelajaran |

```php
    Route::prefix('references')->group(function () {
        Route::get('/classrooms', [ReferenceController::class, 'classrooms']);
        Route::get('/rooms', [ReferenceController::class, 'rooms']);
        Route::get('/subjects', [ReferenceController::class, 'subjects']);
    });
```

#### 11d. Siswa API

| Method | Endpoint | Controller | Deskripsi |
|--------|----------|------------|-----------|
| GET | `/api/student/dashboard` | `StudentApiController@dashboard` | Dashboard siswa |
| GET | `/api/student/schedules/today` | `StudentApiController@todaySchedule` | Jadwal hari ini |
| GET | `/api/student/attendance-summary` | `StudentApiController@attendanceSummary` | Ringkasan presensi |
| GET | `/api/student/attendance-history` | `StudentApiController@attendanceHistory` | Riwayat presensi (paginated) |
| GET | `/api/student/leave-requests` | `StudentApiController@leaveRequests` | Daftar pengajuan izin |
| POST | `/api/student/leave-requests` | `StudentApiController@storeLeaveRequest` | Ajukan izin baru |

```php
    Route::prefix('student')->group(function () {
        Route::get('/dashboard', [StudentApiController::class, 'dashboard']);
        Route::get('/schedules/today', [StudentApiController::class, 'todaySchedule']);
        Route::get('/attendance-summary', [StudentApiController::class, 'attendanceSummary']);
        Route::get('/attendance-history', [StudentApiController::class, 'attendanceHistory']);
        Route::get('/leave-requests', [StudentApiController::class, 'leaveRequests']);
        Route::post('/leave-requests', [StudentApiController::class, 'storeLeaveRequest']);
    });
```

#### 11e. Guru API

| Method | Endpoint | Controller | Deskripsi |
|--------|----------|------------|-----------|
| GET | `/api/teacher/dashboard` | `Teacher\DashboardController@index` | Dashboard guru |
| GET | `/api/teacher/schedules` | `Teacher\ScheduleController@index` | Jadwal mengajar saya |
| POST | `/api/teacher/schedules` | `Teacher\ScheduleController@store` | Tambah jadwal baru |
| GET | `/api/teacher/schedules/{schedule}/students` | `Teacher\ScheduleController@students` | Daftar siswa per jadwal |
| POST | `/api/teacher/journals` | `Teacher\JournalController@store` | Submit jurnal & presensi |
| GET | `/api/teacher/journals` | `Teacher\JournalController@index` | Riwayat jurnal |

```php
    Route::prefix('teacher')->group(function () {
        Route::get('/dashboard', [TeacherDashboardController::class, 'index']);
        Route::get('/schedules', [TeacherScheduleController::class, 'index']);
        Route::post('/schedules', [TeacherScheduleController::class, 'store']);
        Route::get('/schedules/{schedule}/students', [TeacherScheduleController::class, 'students']);
        Route::post('/journals', [TeacherJournalController::class, 'store']);
        Route::get('/journals', [TeacherJournalController::class, 'index']);
    });
```

#### 11f. Wali Kelas API

| Method | Endpoint | Controller | Deskripsi |
|--------|----------|------------|-----------|
| GET | `/api/homeroom/dashboard` | `HomeroomApiController@dashboard` | Dashboard wali kelas |
| GET | `/api/homeroom/leave-requests` | `HomeroomApiController@leaveRequests` | Daftar izin siswa |
| GET | `/api/homeroom/leave-requests/{id}` | `HomeroomApiController@show` | Detail izin siswa |
| PUT | `/api/homeroom/leave-requests/{id}/verify` | `HomeroomApiController@verify` | Approve/Reject izin |

```php
    Route::prefix('homeroom')->group(function () {
        Route::get('/dashboard', [HomeroomApiController::class, 'dashboard']);
        Route::get('/leave-requests', [HomeroomApiController::class, 'leaveRequests']);
        Route::get('/leave-requests/{leaveRequest}', [HomeroomApiController::class, 'show']);
        Route::put('/leave-requests/{leaveRequest}/verify', [HomeroomApiController::class, 'verify']);
    });

}); // end auth:sanctum
```

---

### LANGKAH 12: Jalankan Migration & Testing

```bash
# 1. Jalankan migration
php artisan migrate

# 2. Clear cache
php artisan config:clear
php artisan route:clear

# 3. Cek semua route terdaftar
php artisan route:list --path=api

# 4. Test manual dengan Postman/Insomnia
# Login → ambil token → test tiap endpoint per role
```

---

## 📁 Ringkasan Semua File

### File Baru yang Perlu Dibuat (12 file)

| # | Path | Deskripsi |
|---|------|-----------|
| 1 | `app/Http/Controllers/Api/Teacher/DashboardController.php` | API Dashboard Guru (`index`) |
| 2 | `app/Http/Controllers/Api/Teacher/ScheduleController.php` | API Jadwal Guru (`index`, `store`, `students`) |
| 3 | `app/Http/Controllers/Api/Teacher/JournalController.php` | API Jurnal & Presensi Guru (`index`, `store`) |
| 4 | `app/Http/Controllers/Api/Homeroom/DashboardController.php` | API Dashboard Wali Kelas (`index`) |
| 5 | `app/Http/Controllers/Api/Homeroom/LeaveRequestController.php` | API Pengajuan Izin Wali Kelas (`index`, `show`, `verify`) |
| 6 | `app/Http/Controllers/Api/ReferenceController.php` | Data reference/dropdown (3 method) |
| 7 | `app/Http/Requests/Api/UpdateProfileRequest.php` | Validasi update profile |
| 8 | `app/Http/Requests/Api/ChangePasswordRequest.php` | Validasi ganti password |
| 9 | `app/Http/Requests/Api/StoreScheduleRequest.php` | Validasi input jadwal + conflict |
| 10 | `app/Http/Requests/Api/VerifyLeaveRequest.php` | Validasi approve/reject izin |
| 11 | `app/Http/Resources/Api/JournalResource.php` | Transformer jurnal |
| 12 | `app/Http/Resources/Api/AttendanceHistoryResource.php` | Transformer riwayat presensi |

### File Existing yang Perlu Dimodifikasi (6 file)

| # | Path | Perubahan |
|---|------|-----------|
| 1 | `database/migrations/0001_01_01_000000_create_users_table.php` | Tambah kolom `profile_photo` |
| 2 | `routes/api.php` | Daftarkan SEMUA 21 route |
| 3 | `app/Models/User.php` | Tambah `profile_photo` ke fillable, accessor |
| 4 | `app/Http/Controllers/Api/AuthController.php` | Tambah `updateProfile()`, `changePassword()` |
| 5 | `app/Http/Controllers/Api/StudentApiController.php` | Tambah `dashboard()`, `todaySchedule()`, `attendanceHistory()` |
| 6 | `app/Http/Resources/Api/UserResource.php` | Tambah `profile_photo_url` |

---

## 🗑️ Yang Sengaja TIDAK Dimasukkan

| Fitur | Alasan |
|-------|--------|
| CRUD User/Siswa/Guru via API | Dikelola via Filament Admin Panel |
| CRUD Kelas/Ruang/Mapel via API | Dikelola via Filament Admin Panel |
| API untuk role Waka Kurikulum | Tidak termasuk scope mobile |
| Notifikasi Push | Tidak ada di prototype |
| Forgot Password API | Bisa ditambahkan di fase selanjutnya |

---

## ❓ Open Questions (Perlu Dijawab Sebelum Mulai)

1. **Penentuan Wali Kelas**: Apakah cukup menggunakan relasi `user_class` (Guru yang terhubung ke kelas = Wali Kelas), atau perlu kolom tambahan seperti `is_homeroom_teacher` di pivot?

2. **Profile Photo**: Kolom `profile_photo` belum ada di tabel `users`. Disetujui untuk buat migration baru?

3. **Middleware Role**: Ingin menambahkan middleware untuk membatasi akses per-role di route group (misal: hanya Siswa bisa akses `/student/*`), atau cukup validasi di controller saja?
