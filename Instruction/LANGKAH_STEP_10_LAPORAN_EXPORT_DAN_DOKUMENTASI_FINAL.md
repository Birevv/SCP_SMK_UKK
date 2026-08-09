# PANDUAN PENGEMBANGAN: STEP 10 — LAPORAN EXPORT, STUDENT OVERVIEW, & DOKUMENTASI FINAL

> **Sistem:** School Class Progress (SCP) - UKK 2026  
> **Modul:** Attendance Reporting (Printable), Student & Parent Widgets, and Final Documentation
> **Target Pengguna:** Waka Kurikulum, Wali Kelas, Guru BK, Siswa, Orang Tua

---

## 🎯 TUJUAN STEP 10

Tahap 10 adalah tahap pamungkas dalam pengerjaan proyek Uji Kompetensi Keahlian (UKK) 2026 ini. Pada tahap ini, kita melengkapi fitur-fitur penyajian data (laporan) dan menyempurnakan dokumentasi proyek:
1. **Laporan & Rekapitulasi Presensi (Printable View):** Membuat halaman Filament khusus (Custom Page) bagi Waka Kurikulum, Wali Kelas, dan Guru BK untuk memfilter kehadiran siswa berdasarkan kelas dan rentang tanggal, serta menyediakan fitur **Cetak Laporan / PDF** dengan kop surat resmi.
2. **Dashboard Overview Siswa & Orang Tua:** Membuat Widget khusus (Mobile-First UI) di dashboard Filament untuk memvisualisasikan ringkasan kehadiran siswa (Hadir, Sakit, Izin, Alpa) yang relevan bagi Siswa dan Orang Tua.
3. **Dokumentasi Proyek Akhir:** Memperbarui README dan status proyek sebagai syarat kelengkapan administrasi teknis UKK.

---

## 🛠️ BEDAH PENGEMBANGAN & KODE STEP 10

---

### 📍 SUB-STEP 10.1: HALAMAN LAPORAN REKAP PRESENSI & JURNAL

Halaman ini memungkinkan Waka Kurikulum, Wali Kelas, dan Guru BK melihat rekap absensi per kelas dalam rentang tanggal tertentu, lalu mencetaknya.

#### 1. Buat Custom Page Filament (`app/Filament/Pages/AttendanceReport.php`)

Jalankan perintah (atau buat manual):
```bash
php artisan make:filament-page AttendanceReport
```

Isi dengan kode berikut:
```php
<?php

namespace App\Filament\Pages;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AttendanceReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationLabel = 'Laporan Presensi';
    protected static ?string $title = 'Laporan Rekapitulasi Presensi Siswa';
    protected static ?string $navigationGroup = 'Laporan & Rekap';
    protected static int $navigationSort = 1;

    protected static string $view = 'filament.pages.attendance-report';

    public ?int $classroom_id = null;
    public ?string $start_date = null;
    public ?string $end_date = null;

    public function mount(): void
    {
        $user = Auth::user();

        // Set default rentang tanggal: Bulan berjalan
        $this->start_date = Carbon::now()->startOfMonth()->toDateString();
        $this->end_date = Carbon::now()->endOfMonth()->toDateString();

        // Default kelas
        if ($user?->classroom_id) {
            $this->classroom_id = $user->classroom_id;
        } else {
            $this->classroom_id = Classroom::first()?->id;
        }   

        $this->form->fill([
            'classroom_id' => $this->classroom_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ]);
    }

    public static function canView(): bool
    {
        $role = Auth::user()?->role?->name;
        // Hanya Waka, Guru (Opsional), Wali Kelas, dan Guru BK yang bisa melihat laporan ini.
        return in_array($role, ['Waka Kurikulum', 'Guru', 'Wali Kelas', 'Guru BK']);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('classroom_id')
                    ->label('Pilih Kelas')
                    ->options(Classroom::query()->pluck('name', 'id'))
                    ->required()
                    ->live(),

                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->required()
                    ->live(),

                DatePicker::make('end_date')
                    ->label('Tanggal Selesai')
                    ->required()
                    ->live(),
            ])
            ->columns(3);
    }

    public function getReportDataProperty(): array
    {
        if (! $this->classroom_id || ! $this->start_date || ! $this->end_date) {
            return [];
        }

        $students = User::where('classroom_id', $this->classroom_id)->get();
        $selectedClassroom = Classroom::find($this->classroom_id);
        $report = [];

        foreach ($students as $student) {
            $attendances = Attendance::where('student_id', $student->id)
                ->whereHas('journal', function ($query) {
                    $query->whereBetween('date', [$this->start_date, $this->end_date]);
                })
                ->get();

            $hadir = $attendances->where('status', 'Hadir')->count();
            $sakit = $attendances->where('status', 'Sakit')->count();
            $izin = $attendances->where('status', 'Izin')->count();
            $alpa = $attendances->where('status', 'Alpa')->count();
            $totalPertemuan = $attendances->count();

            $percentage = $totalPertemuan > 0 ? round(($hadir / $totalPertemuan) * 100, 1) : 100;

            $report[] = [
                'student_id' => $student->id,
                'full_name' => $student->full_name,
                'username' => $student->username,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total' => $totalPertemuan,
                'percentage' => $percentage,
            ];
        }

        return [
            'classroom' => $selectedClassroom,
            'start_date' => Carbon::parse($this->start_date)->isoFormat('D MMMM Y'),
            'end_date' => Carbon::parse($this->end_date)->isoFormat('D MMMM Y'),
            'students' => $report,
        ];
    }
}
```

#### 2. Buat View & Template Cetak (Printable HTML) (`resources/views/filament/pages/attendance-report.blade.php`)

Kustomisasi Blade template dengan filter dan fitur cetak menggunakan CSS `@media print`:
```html
<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Area Form Filter (Disembunyikan saat mencetak) -->
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl border shadow-sm print:hidden">
            <h2 class="text-base font-semibold mb-4 text-gray-800 dark:text-gray-200">Filter Laporan Presensi</h2>
            <form wire:submit.prevent="$refresh" class="space-y-4">
                {{ $this->form }}

                <div class="flex justify-end gap-3 pt-2">
                    <x-filament::button type="button" icon="heroicon-o-printer" onclick="window.print()" color="success">
                        Cetak Laporan
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Area Laporan yang akan Dicetak -->
        @php
            $data = $this->reportData;
        @endphp

        @if (!empty($data) && count($data['students']) > 0)
            <div id="printable-report" class="p-8 bg-white dark:bg-gray-900 rounded-xl border shadow-sm text-gray-900 dark:text-white print:p-0 print:border-none print:shadow-none">
                
                <!-- Kop Laporan Resmi -->
                <div class="border-b-2 border-gray-800 pb-4 mb-6 text-center">
                    <h1 class="text-2xl font-bold uppercase tracking-wide text-gray-900 dark:text-white">
                        SCHOOL CLASS PROGRESS (SCP)
                    </h1>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        LAPORAN REKAPITULASI KEHADIRAN SISWA
                    </p>
                    <div class="mt-3 flex justify-between text-xs text-gray-600 dark:text-gray-400 font-semibold px-2">
                        <span>KELAS: {{ strtoupper($data['classroom']?->name ?? '-') }}</span>
                        <span>PERIODE: {{ $data['start_date'] }} s/d {{ $data['end_date'] }}</span>
                    </div>
                </div>

                <!-- Tabel Data -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse border border-gray-300 dark:border-gray-700">
                        <thead>
                            <tr class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                <th class="border border-gray-300 dark:border-gray-700 p-2.5 text-center w-12">No</th>
                                <th class="border border-gray-300 dark:border-gray-700 p-2.5">NIS / Username</th>
                                <th class="border border-gray-300 dark:border-gray-700 p-2.5">Nama Siswa</th>
                                <th class="border border-gray-300 dark:border-gray-700 p-2.5 text-center bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300">Hadir</th>
                                <th class="border border-gray-300 dark:border-gray-700 p-2.5 text-center bg-yellow-50 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300">Sakit</th>
                                <th class="border border-gray-300 dark:border-gray-700 p-2.5 text-center bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">Izin</th>
                                <th class="border border-gray-300 dark:border-gray-700 p-2.5 text-center bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300">Alpa</th>
                                <th class="border border-gray-300 dark:border-gray-700 p-2.5 text-center font-bold">Total</th>
                                <th class="border border-gray-300 dark:border-gray-700 p-2.5 text-center font-bold">% Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data['students'] as $index => $student)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                    <td class="border border-gray-300 dark:border-gray-700 p-2 text-center">{{ $index + 1 }}</td>
                                    <td class="border border-gray-300 dark:border-gray-700 p-2">{{ $student['username'] }}</td>
                                    <td class="border border-gray-300 dark:border-gray-700 p-2 font-medium">{{ $student['full_name'] }}</td>
                                    <td class="border border-gray-300 dark:border-gray-700 p-2 text-center text-green-600 font-semibold">{{ $student['hadir'] }}</td>
                                    <td class="border border-gray-300 dark:border-gray-700 p-2 text-center text-yellow-600 font-semibold">{{ $student['sakit'] }}</td>
                                    <td class="border border-gray-300 dark:border-gray-700 p-2 text-center text-blue-600 font-semibold">{{ $student['izin'] }}</td>
                                    <td class="border border-gray-300 dark:border-gray-700 p-2 text-center text-red-600 font-semibold">{{ $student['alpa'] }}</td>
                                    <td class="border border-gray-300 dark:border-gray-700 p-2 text-center font-bold">{{ $student['total'] }}</td>
                                    <td class="border border-gray-300 dark:border-gray-700 p-2 text-center font-bold {{ $student['percentage'] < 75 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ $student['percentage'] }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Bagian Tanda Tangan (Hanya muncul saat dicetak) -->
                <div class="mt-12 hidden print:flex justify-between text-xs text-gray-800 px-4">
                    <div class="text-center">
                        <p>Mengetahui,</p>
                        <p class="font-bold">Waka Kurikulum</p>
                        <br><br><br>
                        <p class="font-bold underline">(..........................................)</p>
                    </div>
                    <div class="text-center">
                        <p>Dicetak Pada: {{ now()->isoFormat('D MMMM Y, HH:mm') }}</p>
                        <p class="font-bold">Wali Kelas / Guru BK</p>
                        <br><br><br>
                        <p class="font-bold underline">(..........................................)</p>
                    </div>
                </div>
            </div>
        @else
            <div class="p-8 text-center bg-white dark:bg-gray-900 rounded-xl border">
                <p class="text-gray-500 dark:text-gray-400">Tidak ada data presensi yang ditemukan untuk kriteria filter ini.</p>
            </div>
        @endif
    </div>

    <!-- Inject CSS khusus Print -->
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #printable-report, #printable-report * {
                visibility: visible;
            }
            #printable-report {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            /* Sembunyikan elemen UI Filament */
            .fi-sidebar, .fi-topbar, .fi-header, .print\:hidden {
                display: none !important;
            }
        }
    </style>
</x-filament-panels::page>
```

---

### 📍 SUB-STEP 10.2: WIDGET DASHBOARD SISWA & ORANG TUA

Untuk pengalaman pengguna (UI/UX) yang ramah di perangkat seluler (Mobile-First Design), Siswa & Orang Tua akan disambut oleh visualisasi metrik absensi mereka tanpa perlu membuka banyak menu.

#### 1. Buat Widget Khusus (`app/Filament/Widgets/StudentAttendanceWidget.php`)

Buat widget:
```bash
php artisan make:filament-widget StudentAttendanceWidget
```

Isi kode widget-nya:
```php
<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\User;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class StudentAttendanceWidget extends Widget
{
    protected static string $view = 'filament.widgets.student-attendance-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $role = Auth::user()?->role?->name;
        // Widget ini khusus untuk Siswa dan Orang Tua
        return in_array($role, ['Siswa', 'Orang Tua']);
    }

    protected function getViewData(): array
    {
        $user = Auth::user();
        $studentId = null;
        $studentName = '';

        if ($user->role->name === 'Siswa') {
            $studentId = $user->id;
            $studentName = $user->full_name;
        } elseif ($user->role->name === 'Orang Tua') {
            // Relasi ke anak (siswa)
            $child = User::find($user->parent_id);
            if ($child) {
                $studentId = $child->id;
                $studentName = $child->full_name;
            }
        }

        if (!$studentId) {
            return ['stats' => null];
        }

        $attendances = Attendance::where('student_id', $studentId)->get();

        $hadir = $attendances->where('status', 'Hadir')->count();
        $sakit = $attendances->where('status', 'Sakit')->count();
        $izin = $attendances->where('status', 'Izin')->count();
        $alpa = $attendances->where('status', 'Alpa')->count();
        $total = $attendances->count();

        $percentage = $total > 0 ? round(($hadir / $total) * 100, 1) : 100;

        return [
            'stats' => [
                'student_name' => $studentName,
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpa' => $alpa,
                'total' => $total,
                'percentage' => $percentage,
            ]
        ];
    }
}
```

#### 2. Buat Tampilan Widget (`resources/views/filament/widgets/student-attendance-widget.blade.php`)

Isi desain UI widget-nya:
```html
<x-filament-widgets::widget>
    @if(isset($stats) && $stats !== null)
        <x-filament::section>
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ringkasan Kehadiran</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Siswa: <span class="font-semibold text-primary-600">{{ $stats['student_name'] }}</span></p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-black {{ $stats['percentage'] < 75 ? 'text-danger-600' : 'text-success-600' }}">
                        {{ $stats['percentage'] }}%
                    </div>
                    <div class="text-xs text-gray-500">Persentase Kehadiran</div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-success-50 dark:bg-success-950/30 border border-success-200 dark:border-success-800 rounded-xl p-4 text-center">
                    <div class="text-success-600 dark:text-success-400 text-3xl font-bold">{{ $stats['hadir'] }}</div>
                    <div class="text-success-800 dark:text-success-300 text-sm font-medium mt-1">Hadir</div>
                </div>
                
                <div class="bg-warning-50 dark:bg-warning-950/30 border border-warning-200 dark:border-warning-800 rounded-xl p-4 text-center">
                    <div class="text-warning-600 dark:text-warning-400 text-3xl font-bold">{{ $stats['sakit'] }}</div>
                    <div class="text-warning-800 dark:text-warning-300 text-sm font-medium mt-1">Sakit</div>
                </div>
                
                <div class="bg-info-50 dark:bg-info-950/30 border border-info-200 dark:border-info-800 rounded-xl p-4 text-center">
                    <div class="text-info-600 dark:text-info-400 text-3xl font-bold">{{ $stats['izin'] }}</div>
                    <div class="text-info-800 dark:text-info-300 text-sm font-medium mt-1">Izin</div>
                </div>
                
                <div class="bg-danger-50 dark:bg-danger-950/30 border border-danger-200 dark:border-danger-800 rounded-xl p-4 text-center">
                    <div class="text-danger-600 dark:text-danger-400 text-3xl font-bold">{{ $stats['alpa'] }}</div>
                    <div class="text-danger-800 dark:text-danger-300 text-sm font-medium mt-1">Alpa</div>
                </div>
            </div>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="text-center py-6">
                <p class="text-gray-500 dark:text-gray-400">Data kehadiran belum tersedia.</p>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
```

#### 3. Daftarkan Widget ke AdminPanelProvider (`app/Providers/Filament/AdminPanelProvider.php`)
Jangan lupa pastikan widget `StudentAttendanceWidget::class` diregistrasikan atau biarkan Filament meng-autodiscovernya (berkat `$panel->discoverWidgets(...)`).

---

## 📌 CHECKLIST FINISH & INTEGRITY STEP 10

- [ ] Laporan Presensi dapat diakses Waka/Wali Kelas/Guru BK dan filter berjalan sesuai tabel absensi per siswa.
- [ ] Template Laporan (Printable Mode) bekerja lancar menyembunyikan navigasi Filament.
- [ ] Widget Siswa/Orang Tua aktif di Dashboard khusus role tersebut dan menampilkan angka yang akurat.
- [ ] Dokumentasi Proyek Akhir dan README di-update dengan status final dan petunjuk uji komprehensif.

🎉 **SELAMAT! Aplikasi SCP UKK 2026 Anda sudah lengkap dan memenuhi 100% persyaratan spesifikasi Soal Ujian Kompetensi Keahlian (UKK).**
