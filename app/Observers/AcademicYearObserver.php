<?php

namespace App\Observers;

use App\Models\AcademicYear;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;

class AcademicYearObserver
{
    /**
     * Handle the AcademicYear "saved" event.
     *
     * Jika sebuah Tahun Ajaran disimpan dengan status aktif (is_active = true):
     * 1. Nonaktifkan status aktif pada semua Tahun Ajaran lainnya (hanya 1 aktif).
     * 2. Otomatis isi / sinkronkan kolom academic_year_id di tabel schedules.
     * 3. Otomatis isi / sinkronkan kolom academic_year_id di tabel user_class untuk anggota kelas aktif.
     */
    public function saved(AcademicYear $academicYear): void
    {
        if ($academicYear->is_active) {
            // 1. Nonaktifkan tahun ajaran lainnya
            AcademicYear::where('id', '!=', $academicYear->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // 2. Otomatis update kolom academic_year_id di tabel schedules
            Schedule::query()->update([
                'academic_year_id' => $academicYear->id,
            ]);

            // 3. Otomatis update kolom academic_year_id di tabel user_class
            DB::table('user_class')
                ->where('status', 'Aktif')
                ->orWhereNull('academic_year_id')
                ->update([
                    'academic_year_id' => $academicYear->id,
                ]);
        }
    }
}
