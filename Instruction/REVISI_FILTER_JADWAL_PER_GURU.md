# 📌 Langkah Revisi: Filter Jadwal Mengajar Sesuai User Login

Tujuan: Guru hanya melihat & mengelola jadwal miliknya sendiri, sedangkan Waka Kurikulum dapat melihat & mengelola seluruh jadwal.

---

## 1. Edit `app/Filament/Resources/Schedules/ScheduleResource.php`
- **Lokasi Method:** `getEloquentQuery()`
- **Tindakan:** Tambahkan filter `teacher_id` khusus role `Guru`.

```php
use Illuminate\Support\Facades\Auth;

public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery()
        ->with(['teacher', 'classroom', 'room', 'subject', 'academicYear'])
        ->forActiveCycle();

    $user = Auth::user();

    // Guru hanya melihat jadwal miliknya sendiri
    if ($user && $user->role?->name === 'Guru') {
        $query->where('teacher_id', $user->id);
    }

    return $query;
}
```

---

## 2. Edit `app/Policies/SchedulePolicy.php`
- **Lokasi Method:** `view()`, `update()`, `delete()`
- **Tindakan:** Batasi izin aksi hanya untuk pemilik jadwal (Guru yang bersangkutan) atau Waka Kurikulum.

```php
public function view(User $user, Schedule $schedule): bool
{
    if ($user->role?->name === 'Waka Kurikulum') {
        return true;
    }

    return $user->role?->name === 'Guru' && $schedule->teacher->id === $user->id;
}

public function update(User $user, Schedule $schedule): bool
{
    if ($user->role?->name === 'Waka Kurikulum') {
        return true;
    }

    return $user->role?->name === 'Guru' 
        && $schedule->teacher_id === $user->id 
        && !$this->isLocked();
}

public function delete(User $user, Schedule $schedule): bool
{
    if ($user->role?->name === 'Waka Kurikulum') {
        return true;
    }

    return $user->role?->name === 'Guru' 
        && $schedule->teacher_id === $user->id 
        && !$this->isLocked();
}
```

---

## 3. Pastikan Form Mengunci Akun Guru di `app/Filament/Resources/Schedules/Schemas/ScheduleForm.php`
- **Field:** `teacher_id`
- **Tindakan:** Set default ke ID login & disable dropdown jika rolenya Guru.

```php
Select::make('teacher_id')
    ->relationship(
        name: 'teacher',
        titleAttribute: 'full_name',
        modifyQueryUsing: fn ($query) => $query->whereHas('role', fn ($q) => $q->where('name', 'Guru'))
    )
    ->default(fn () => Auth::user()?->role?->name === 'Guru' ? Auth::id() : null)
    ->disabled(fn () => Auth::user()?->role?->name === 'Guru')
    ->dehydrated()
    ->required()
    ->searchable()
    ->preload()
    ->label('Guru Pengajar'),
```

---

## 4. Pengujian (Testing)
- [ ] Login sebagai `guru1` ➔ Tabel Jadwal hanya menampilkan jadwal `guru1`.
- [ ] Login sebagai `guru2` ➔ Tabel Jadwal hanya menampilkan jadwal `guru2`.
- [ ] Login sebagai `waka` ➔ Tabel Jadwal menampilkan semua jadwal guru.
