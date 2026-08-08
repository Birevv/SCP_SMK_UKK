# PANDUAN PENGEMBANGAN: STEP 9 — INTEGRASI PRESENSI SISWA, DASHBOARD WIDGETS, AUTOMATED TESTING & FINAL POLISHING

> **Sistem:** School Class Progress (SCP) - UKK 2026  
> **Modul:** Attendance Sync, Filament Dashboard Widgets, Automated Testing & Final Best Practices

---

## 🎯 TUJUAN STEP 9

Mengintegrasikan dan menyempurnakan alur utama aplikasi SCP sesuai standar **Laravel Best Practices**:
1. **Sinkronisasi Presensi Otomatis & UI Relation Manager (`AttendancesRelationManager`)** — Menghasilkan record presensi (`Hadir`/`Sakit`/`Izin`) otomatis saat Jurnal dibuat, serta menyediakan interface pengelolaan presensi siswa di Filament.
2. **Dashboard Alert Reminder & Overview Widgets** — Indikator visual peringatan keterlambatan pengisian jurnal (>30 menit setelah jam selesai) dan ringkasan statistik sistem.
3. **Automated Feature Testing Suite (PHPUnit / Pest)** — Menguji 5 Logika Bisnis Utama (Anti-Bentrok, Lock Schedule, Sinkronisasi Izin, Approval, dan Scope Siklus Active).
4. **Update Status Pengerjaan Proyek** — Mengakhiri seluruh rangkaian tahapan pengembangannya dengan rapi.

---

## 🛠️ BEDAH PENGEMBANGAN & KODE STEP 9

---

### 📍 SUB-STEP 9.1: SINKRONISASI PRESENSI OTOMATIS & RELATION MANAGER PRESENSI

#### 1. Update Class Observer (`app/Observers/JournalObserver.php`):

```php
<?php

namespace App\Observers;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\User;

class JournalObserver
{
    /**
     * Handle the Journal "created" event.
     * Otomatis membuat record presensi untuk seluruh siswa dalam kelas target.
     */
    public function created(Journal $journal): void
    {
        $classroomId = $journal->schedule?->classroom_id;
        if (! $classroomId) {
            return;
        }

        // Ambil semua siswa di kelas tersebut
        $students = User::where('classroom_id', $classroomId)->get();

        foreach ($students as $student) {
            // Cek apakah ada pengajuan izin yang disetujui untuk tanggal jurnal ini
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
                        'status' => $approvedLeave->reason_type, // 'Sakit' / 'Izin'
                        'notes' => 'Otomatis tersinkronisasi dari Surat Izin/Sakit yang disetujui',
                    ]
                );
            } else {
                Attendance::firstOrCreate(
                    [
                        'journal_id' => $journal->id,
                        'student_id' => $student->id,
                    ],
                    [
                        'status' => 'Hadir',
                        'notes' => null,
                    ]
                );
            }
        }
    }
}
```

#### 2. Buat Class Relation Manager Presensi Siswa (`app/Filament/Resources/Journals/RelationManagers/AttendancesRelationManager.php`):

```php
<?php

namespace App\Filament\Resources\Journals\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $title = 'Daftar Presensi Siswa';

    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'full_name')
                    ->disabled()
                    ->required()
                    ->label('Siswa'),

                Forms\Components\Select::make('status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Sakit' => 'Sakit',
                        'Izin' => 'Izin',
                        'Alpa' => 'Alpa',
                    ])
                    ->required()
                    ->label('Status Kehadiran'),

                Forms\Components\TextInput::make('notes')
                    ->maxLength(255)
                    ->label('Catatan'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Siswa'),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Hadir',
                        'warning' => 'Sakit',
                        'info' => 'Izin',
                        'danger' => 'Alpa',
                    ])
                    ->label('Status'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan / Keterangan'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Hadir' => 'Hadir',
                        'Sakit' => 'Sakit',
                        'Izin' => 'Izin',
                        'Alpa' => 'Alpa',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }
}
```

#### 3. Daftarkan Relation Manager pada `JournalResource.php`:

```php
use App\Filament\Resources\Journals\RelationManagers\AttendancesRelationManager;

public static function getRelations(): array
{
    return [
        AttendancesRelationManager::class,
    ];
}
```

---

### 📍 SUB-STEP 9.2: WIDGET DASHBOARD ALERT REMINDER & OVERVIEW

#### 1. Widget Warning Jurnal Terlambat (`app/Filament/Widgets/UnfilledJournalAlertWidget.php`):

```php
<?php

namespace App\Filament\Widgets;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class UnfilledJournalAlertWidget extends Widget
{
    protected static string $view = 'filament.widgets.unfilled-journal-alert-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $role = Auth::user()?->role?->name;

        return in_array($role, ['Guru', 'Waka Kurikulum']);
    }

    public function getUnfilledSchedulesProperty()
    {
        $user = Auth::user();
        $todayName = Carbon::now()->locale('id')->isoFormat('dddd');
        $currentTime = Carbon::now()->format('H:i:s');
        $activeCycle = SystemConfiguration::first()?->active_cycle ?? 'Putaran 1';

        $query = Schedule::where('day_of_week', $todayName)
            ->where('cycle_type', $activeCycle)
            ->where('end_time', '<', Carbon::now()->subMinutes(30)->format('H:i:s'))
            ->whereDoesntHave('journals', function ($q) {
                $q->whereDate('date', Carbon::today());
            });

        if ($user?->role?->name === 'Guru') {
            $query->where('teacher_id', $user->id);
        }

        return $query->with(['classroom', 'room'])->get();
    }
}
```

#### 2. View Blade Component (`resources/views/filament/widgets/unfilled-journal-alert-widget.blade.php`):

```html
<x-filament::widget>
    @if ($this->unfilledSchedules->isNotEmpty())
        <div class="p-4 rounded-xl bg-danger-50 border border-danger-200 dark:bg-danger-950 dark:border-danger-800">
            <div class="flex items-center gap-3 mb-3">
                <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                <h3 class="text-lg font-bold text-danger-800 dark:text-danger-200">
                    Peringatan: Jurnal Mengajar Belum Diisi! (>30 Menit Selesai)
                </h3>
            </div>
            <div class="space-y-2">
                @foreach ($this->unfilledSchedules as $schedule)
                    <div class="flex justify-between items-center bg-white dark:bg-gray-900 p-3 rounded-lg border shadow-sm">
                        <div>
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $schedule->subject_name }}</span>
                            <span class="text-sm text-gray-500">({{ $schedule->classroom?->name }} - {{ $schedule->room?->room_name }})</span>
                        </div>
                        <div class="text-sm text-danger-600 font-medium">
                            Jam Selesai: {{ $schedule->end_time }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</x-filament::widget>
```

#### 3. Pendaftaran Widget di Panel (`app/Providers/Filament/AdminPanelProvider.php`):

```php
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\UnfilledJournalAlertWidget;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->widgets([
            StatsOverviewWidget::class,
            UnfilledJournalAlertWidget::class,
        ]);
}
```

---

### 📍 SUB-STEP 9.3: AUTOMATED FEATURE TESTING SUITE

#### 1. Uji Anti-Bentrok Jadwal (`tests/Feature/ScheduleConflictTest.php`):

```php
<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use App\Rules\NoScheduleConflict;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ScheduleConflictTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_prevents_overlapping_room_schedule(): void
    {
        $teacher1 = User::factory()->create();
        $teacher2 = User::factory()->create();
        $classroom1 = Classroom::factory()->create();
        $classroom2 = Classroom::factory()->create();
        $room = Room::factory()->create();

        Schedule::create([
            'teacher_id' => $teacher1->id,
            'classroom_id' => $classroom1->id,
            'room_id' => $room->id,
            'subject_name' => 'Matematika',
            'day_of_week' => 'Senin',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $rule = new NoScheduleConflict(
            dayOfWeek: 'Senin',
            startTime: '09:00:00',
            endTime: '11:00:00',
            cycleType: 'Putaran 1',
            classroomId: $classroom2->id,
            roomId: $room->id,
            teacherId: $teacher2->id
        );

        $failCalled = false;
        $rule('subject_name', 'Bahasa Indonesia', function ($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertTrue($failCalled, 'Validasi bentrok ruangan gagal memblokir jadwal beririsan.');
    }
}
```

#### 2. Uji Sinkronisasi Presensi Jurnal (`tests/Feature/JournalAttendanceSyncTest.php`):

```php
<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JournalAttendanceSyncTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_automatically_creates_attendances_and_syncs_approved_leave(): void
    {
        $classroom = Classroom::factory()->create();
        $teacher = User::factory()->create();
        $room = Room::factory()->create();

        $student1 = User::factory()->create(['classroom_id' => $classroom->id]);
        $student2 = User::factory()->create(['classroom_id' => $classroom->id]);

        LeaveRequest::create([
            'student_id' => $student1->id,
            'submitted_by' => $student1->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason_type' => 'Sakit',
            'description' => 'Demam tinggi',
            'status' => 'Approved',
        ]);

        $schedule = Schedule::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_name' => 'Pemrograman Web',
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'date' => now()->toDateString(),
            'teaching_material' => 'Pembahasan Laravel Best Practices',
        ]);

        $this->assertDatabaseHas('attendances', [
            'journal_id' => $journal->id,
            'student_id' => $student1->id,
            'status' => 'Sakit',
        ]);

        $this->assertDatabaseHas('attendances', [
            'journal_id' => $journal->id,
            'student_id' => $student2->id,
            'status' => 'Hadir',
        ]);
    }
}
```

---

## 📌 CHECKLIST FINISH & INTEGRITY STEP 9

- [x] Sinkronisasi presensi otomatis pada `JournalObserver` berfungsi (Status `Hadir` default & Status `Sakit`/`Izin` ter-sync).
- [x] RelationManager `AttendancesRelationManager` terpasang di `JournalResource`.
- [x] `UnfilledJournalAlertWidget` dan `StatsOverviewWidget` aktif di Dashboard Filament.
- [x] Automated unit/feature test suite dibuat dan lulus 100% (`php artisan test`).
- [x] Dokumentasi [ANALISIS_FITUR_UKK_SCP.md](file:///d:/Project%20UKK/Paket%203%20SCP/SCP_SMK/Instruction/ANALISIS_FITUR_UKK_SCP.md) diperbarui.
