# PANDUAN PENGEMBANGAN: STEP 8 — IMPLEMENTASI FILAMENT RESOURCES UI (JADWAL, JURNAL, PRESENSI, PERIZINAN, & KONFIGURASI SISTEM)

> **Sistem:** School Class Progress (SCP) - UKK 2026  
> **Modul:** Resource & User Interface (Filament Admin Panel)

---

## 🎯 TUJUAN STEP 8

Membuat dan mengonfigurasi Resource UI Filament v3 untuk 4 modul utama aplikasi SCP:
1. **`ScheduleResource`** — CRUD Jadwal Mengajar Guru + Validasi Anti-Bentrok (`NoScheduleConflict`) & Scope Putaran Aktif.
2. **`JournalResource`** — Input Jurnal Harian + Upload Bukti Foto Realtime + Form Presensi Siswa (`attendances` repeater/relation).
3. **`LeaveRequestResource`** — Pengajuan Surat Izin/Sakit Siswa & Ortu + Approval / Reject oleh Wali Kelas & Guru BK.
4. **`SystemConfigurationResource`** — Control Panel Waka Kurikulum (Toggle Putaran 1/2 & Saklar Lock Schedule).

---

## 🛠️ BEDAH PENGEMBANGAN & KODE STEP 8

---

### 📍 RESOURCE 1: MASTER JADWAL MENGAJAR (`ScheduleResource`)

#### 1. Perintah Artisan Membuat Resource:
```bash
php artisan make:filament-resource Schedule -n
```

#### 2. Kode Class Resource (`app/Filament/Resources/ScheduleResource.php`):

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScheduleResource\Pages;
use App\Models\Schedule;
use App\Rules\NoScheduleConflict;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ScheduleResource extends Resource
{
    protected static ?string $model = Schedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Manajemen Akademik';
    protected static ?string $navigationLabel = 'Jadwal Mengajar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('teacher_id')
                    ->relationship('teacher', 'full_name')
                    ->required()
                    ->searchable()
                    ->label('Guru Pengajar'),

                Forms\Components\Select::make('classroom_id')
                    ->relationship('classroom', 'name')
                    ->required()
                    ->label('Kelas Target'),

                Forms\Components\Select::make('room_id')
                    ->relationship('room', 'room_name')
                    ->required()
                    ->label('Ruangan'),

                Forms\Components\TextInput::make('subject_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Mata Pelajaran')
                    ->rules([
                        fn (Get $get, ?Schedule $record): NoScheduleConflict => new NoScheduleConflict(
                            dayOfWeek: (string) $get('day_of_week'),
                            startTime: (string) $get('start_time'),
                            endTime: (string) $get('end_time'),
                            cycleType: (string) $get('cycle_type'),
                            classroomId: $get('classroom_id') ? (int) $get('classroom_id') : null,
                            roomId: $get('room_id') ? (int) $get('room_id') : null,
                            teacherId: $get('teacher_id') ? (int) $get('teacher_id') : null,
                            ignoreId: $record?->id
                        ),
                    ]),

                Forms\Components\Select::make('day_of_week')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                    ])
                    ->required()
                    ->label('Hari'),

                Forms\Components\TimePicker::make('start_time')
                    ->required()
                    ->seconds(false)
                    ->label('Jam Mulai'),

                Forms\Components\TimePicker::make('end_time')
                    ->required()
                    ->seconds(false)
                    ->label('Jam Selesai'),

                Forms\Components\Select::make('cycle_type')
                    ->options([
                        'Putaran 1' => 'Putaran 1',
                        'Putaran 2' => 'Putaran 2',
                    ])
                    ->default('Putaran 1')
                    ->required()
                    ->label('Putaran Siklus'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('subject_name')->searchable()->sortable()->label('Mata Pelajaran'),
                Tables\Columns\TextColumn::make('teacher.full_name')->searchable()->label('Guru'),
                Tables\Columns\TextColumn::make('classroom.name')->sortable()->label('Kelas'),
                Tables\Columns\TextColumn::make('room.room_name')->sortable()->label('Ruangan'),
                Tables\Columns\BadgeColumn::make('day_of_week')->color('info')->label('Hari'),
                Tables\Columns\TextColumn::make('start_time')->label('Mulai'),
                Tables\Columns\TextColumn::make('end_time')->label('Selesai'),
                Tables\Columns\BadgeColumn::make('cycle_type')
                    ->colors([
                        'primary' => 'Putaran 1',
                        'warning' => 'Putaran 2',
                    ])
                    ->label('Siklus'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('day_of_week')
                    ->options([
                        'Senin' => 'Senin',
                        'Selasa' => 'Selasa',
                        'Rabu' => 'Rabu',
                        'Kamis' => 'Kamis',
                        'Jumat' => 'Jumat',
                        'Sabtu' => 'Sabtu',
                    ]),
                Tables\Filters\SelectFilter::make('cycle_type')
                    ->options([
                        'Putaran 1' => 'Putaran 1',
                        'Putaran 2' => 'Putaran 2',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forActiveCycle();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
```

---

### 📍 RESOURCE 2: JURNAL MENGAJAR & PRESENSI (`JournalResource`)

#### 1. Perintah Artisan Membuat Resource:
```bash
php artisan make:filament-resource Journal -n
```

#### 2. Kode Class Resource (`app/Filament/Resources/JournalResource.php`):

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\JournalResource\Pages;
use App\Models\Journal;
use App\Models\Schedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class JournalResource extends Resource
{
    protected static ?string $model = Journal::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Manajemen Akademik';
    protected static ?string $navigationLabel = 'Jurnal Mengajar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('schedule_id')
                    ->relationship('schedule', 'subject_name', fn (Builder $query) => $query->where('teacher_id', auth()->id())->forActiveCycle())
                    ->required()
                    ->label('Jadwal Mengajar'),

                Forms\Components\Hidden::make('teacher_id')
                    ->default(auth()->id()),

                Forms\Components\DatePicker::make('date')
                    ->default(now())
                    ->required()
                    ->label('Tanggal Jurnal'),

                Forms\Components\Textarea::make('teaching_material')
                    ->required()
                    ->columnSpanFull()
                    ->label('Materi & Ringkasan Pembelajaran'),

                Forms\Components\FileUpload::make('activity_photo')
                    ->image()
                    ->directory('journal-photos')
                    ->label('Bukti Foto Kegiatan (Realtime)'),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull()
                    ->label('Catatan Tambahan / Kendala'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable()->label('Tanggal'),
                Tables\Columns\TextColumn::make('teacher.full_name')->label('Guru'),
                Tables\Columns\TextColumn::make('schedule.subject_name')->label('Mata Pelajaran'),
                Tables\Columns\TextColumn::make('schedule.classroom.name')->label('Kelas'),
                Tables\Columns\ImageColumn::make('activity_photo')->label('Foto Bukti'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Dibuat Pada'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJournals::route('/'),
            'create' => Pages\CreateJournal::route('/create'),
            'edit' => Pages\EditJournal::route('/{record}/edit'),
        ];
    }
}
```

---

### 📍 RESOURCE 3: PENGAJUAN PERIZINAN SISWA (`LeaveRequestResource`)

#### 1. Perintah Artisan Membuat Resource:
```bash
php artisan make:filament-resource LeaveRequest -n
```

#### 2. Kode Class Resource (`app/Filament/Resources/LeaveRequestResource.php`):

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static ?string $navigationGroup = 'Kesiswaan';
    protected static ?string $navigationLabel = 'Surat Izin / Sakit';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('student_id')
                    ->relationship('student', 'full_name')
                    ->required()
                    ->searchable()
                    ->label('Siswa'),

                Forms\Components\Hidden::make('submitted_by')
                    ->default(auth()->id()),

                Forms\Components\DatePicker::make('start_date')
                    ->required()
                    ->label('Tanggal Mulai'),

                Forms\Components\DatePicker::make('end_date')
                    ->required()
                    ->label('Tanggal Selesai'),

                Forms\Components\Select::make('reason_type')
                    ->options([
                        'Sakit' => 'Sakit',
                        'Izin' => 'Izin',
                    ])
                    ->required()
                    ->label('Alasan'),

                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull()
                    ->label('Keterangan Alasan'),

                Forms\Components\FileUpload::make('attachment_photo')
                    ->image()
                    ->directory('leave-attachments')
                    ->label('Lampiran Surat / Surat Dokter'),

                Forms\Components\Select::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                    ])
                    ->default('Pending')
                    ->required()
                    ->label('Status Verifikasi'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')->searchable()->label('Nama Siswa'),
                Tables\Columns\TextColumn::make('start_date')->date()->label('Mulai'),
                Tables\Columns\TextColumn::make('end_date')->date()->label('Selesai'),
                Tables\Columns\BadgeColumn::make('reason_type')
                    ->colors([
                        'warning' => 'Sakit',
                        'info' => 'Izin',
                    ])
                    ->label('Jenis'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'Pending',
                        'success' => 'Approved',
                        'danger' => 'Rejected',
                    ])
                    ->label('Status'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'status' => 'Approved',
                        'verified_by' => auth()->id(),
                    ]))
                    ->visible(fn (LeaveRequest $record) => $record->status === 'Pending'),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->action(fn (LeaveRequest $record) => $record->update([
                        'status' => 'Rejected',
                        'verified_by' => auth()->id(),
                    ]))
                    ->visible(fn (LeaveRequest $record) => $record->status === 'Pending'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
```

---

### 📍 RESOURCE 4: PANEL KONTROL WAKA KURIKULUM (`SystemConfigurationResource`)

#### Kode Class Resource (`app/Filament/Resources/SystemConfigurationResource.php`):

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SystemConfigurationResource\Pages;
use App\Models\SystemConfiguration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SystemConfigurationResource extends Resource
{
    protected static ?string $model = SystemConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Pengaturan Sistem';
    protected static ?string $navigationLabel = 'Konfigurasi Sistem';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('active_cycle')
                    ->options([
                        'Putaran 1' => 'Putaran 1',
                        'Putaran 2' => 'Putaran 2',
                    ])
                    ->required()
                    ->label('Putaran Siklus Aktif'),

                Forms\Components\Toggle::make('is_schedule_locked')
                    ->label('Kunci Jadwal Mengajar (Lock Schedule)')
                    ->helperText('Jika diaktifkan, Guru tidak dapat menambah, mengedit, atau menghapus jadwal mengajar.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\BadgeColumn::make('active_cycle')->color('success')->label('Siklus Aktif'),
                Tables\Columns\IconColumn::make('is_schedule_locked')->boolean()->label('Status Kunci Jadwal'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->label('Terakhir Diubah'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSystemConfigurations::route('/'),
            'edit' => Pages\EditSystemConfiguration::route('/{record}/edit'),
        ];
    }
}
```

---

## 📌 CHECKLIST SELESAI STEP 8

- [ ] `ScheduleResource` dibuat & dikonfigurasi dengan validasi anti-bentrok.
- [ ] `JournalResource` dibuat dengan upload foto realtime & form presensi.
- [ ] `LeaveRequestResource` dibuat dengan tombol approval Wali Kelas/BK.
- [ ] `SystemConfigurationResource` dikonfigurasi untuk Waka Kurikulum.
