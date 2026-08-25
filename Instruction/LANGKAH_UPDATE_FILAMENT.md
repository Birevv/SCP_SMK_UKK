    # Panduan Update Halaman Filament (Form & Table)

Setelah kita berhasil merapikan struktur database (Migration), langkah selanjutnya adalah menyesuaikan antarmuka admin (Filament) agar selaras dengan perubahan database tersebut. 

Berikut adalah langkah-langkah yang perlu Anda ikuti untuk memperbarui Resource di Filament:

---

## 1. Membuat Resource Baru (Academic Year & Subject)

Karena kita menambahkan dua tabel master baru, kita perlu membuat halaman CRUD (Create, Read, Update, Delete) untuk keduanya di Filament.

Jalankan perintah ini di terminal:
```bash
php artisan make:filament-resource AcademicYear
php artisan make:filament-resource Subject
```

### A. Konfigurasi `AcademicYearResource.php`
Buka `app/Filament/Resources/AcademicYearResource.php` dan sesuaikan form serta table-nya:

**Form:**
```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->label('Tahun Ajaran (Semester)')
                ->required()
                ->placeholder('Contoh: 2026/2027 Ganjil')
                ->maxLength(255),
            Forms\Components\Toggle::make('is_active')
                ->label('Status Aktif')
                ->default(false),
        ]);
}
```

**Table:**
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Tahun Ajaran')
                ->searchable()
                ->sortable(),
            Tables\Columns\IconColumn::make('is_active')
                ->label('Aktif')
                ->boolean(),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ]);
}
``` 

### B. Konfigurasi `SubjectResource.php`
Buka `app/Filament/Resources/SubjectResource.php`:

**Form:**
```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\TextInput::make('name')
                ->label('Nama Mata Pelajaran')
                ->required()
                ->maxLength(255),
        ]);
}
```

**Table:**
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('name')
                ->label('Nama Mata Pelajaran')
                ->searchable()
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ]);
}
```

---

## 2. Memperbarui `UserResource` (Atau Resource Siswa/Guru)

Buka resource yang mengelola User (misalnya `UserResource.php`, `TeacherResource.php`, atau `StudentResource.php`). Kita perlu menambahkan input untuk `identity_number`, `phone`, dan `status`.

**Pada Form Schema, tambahkan:**
```php
Forms\Components\TextInput::make('identity_number')
    ->label('Nomor Identitas (NIP/NISN)')
    ->maxLength(255)
    ->unique(ignoreRecord: true),
    
Forms\Components\TextInput::make('phone')
    ->label('Nomor Telepon')
    ->tel()
    ->maxLength(255),
    
Forms\Components\Select::make('status')
    ->label('Status Siswa/Guru')
    ->options([
        'Aktif' => 'Aktif',
        'Lulus' => 'Lulus',
        'Keluar' => 'Keluar',
        'DO' => 'Drop Out (DO)',
    ])
    ->default('Aktif')
    ->required(),
```

**Pada Table Columns, tambahkan:**
```php
Tables\Columns\TextColumn::make('identity_number')
    ->label('NIP/NISN')
    ->searchable(),
    
Tables\Columns\TextColumn::make('phone')
    ->label('Telepon')
    ->searchable(),
    
Tables\Columns\BadgeColumn::make('status') // Atau TextColumn::make('status')->badge() jika Filament 3
    ->label('Status')
    ->colors([
        'success' => 'Aktif',
        'primary' => 'Lulus',
        'danger' => 'Keluar',
        'warning' => 'DO',
    ]),
```

---

## 3. Memperbarui `ScheduleResource`

Buka `app/Filament/Resources/ScheduleResource.php`. Karena kita mengganti `subject_name` menjadi `subject_id` dan menambahkan `academic_year_id`, form dan table harus diubah.

**Pada Form Schema:**
1. Tambahkan input Tahun Ajaran:
```php
Forms\Components\Select::make('academic_year_id')
    ->label('Tahun Ajaran')
    ->relationship('academicYear', 'name')
    ->required(),
```

2. Ubah input Mata Pelajaran:
- **Hapus** `TextInput::make('subject_name')`
- **Ganti dengan:**
```php
Forms\Components\Select::make('subject_id')
    ->label('Mata Pelajaran')
    ->relationship('subject', 'name')
    ->required(),
```

**Pada Table Columns:**
1. Tambahkan kolom Tahun Ajaran:
```php
Tables\Columns\TextColumn::make('academicYear.name')
    ->label('Tahun Ajaran')
    ->sortable()
    ->searchable(),
```

2. Ubah kolom Mata Pelajaran:
- **Hapus** `TextColumn::make('subject_name')`
- **Ganti dengan:**
```php
Tables\Columns\TextColumn::make('subject.name')
    ->label('Mata Pelajaran')
    ->sortable()
    ->searchable(),
```

---

## 4. Memperbarui Custom Page (seperti `AttendanceReport.php`)

Jika Anda memiliki halaman custom (misal: Laporan Presensi) yang menampilkan data berdasarkan tahun ajaran atau mata pelajaran, pastikan Anda juga menyesuaikan *query* nya.

Contoh jika sebelumnya Anda mengambil data berdasarkan `subject_name`, sekarang Anda harus mengambilnya menggunakan `$schedule->subject->name` atau menggunakan `where('subject_id', ...)`.
