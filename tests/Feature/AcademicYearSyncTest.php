<?php

namespace Tests\Feature;

use App\Filament\Resources\Schedules\Schemas\ScheduleForm;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademicYearSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\ShieldSeeder::class);
    }

    public function test_activating_academic_year_automatically_updates_other_tables(): void
    {
        // 1. Buat dua tahun ajaran
        $year1 = AcademicYear::create(['name' => '2025/2026 - Genap', 'is_active' => true]);
        $year2 = AcademicYear::create(['name' => '2026/2027 - Ganjil', 'is_active' => false]);

        $this->assertTrue($year1->fresh()->is_active);
        $this->assertFalse($year2->fresh()->is_active);

        // Buat data pendukung
        $teacherRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $subject = Subject::create(['name' => 'Matematika']);
        $classroom = Classroom::create(['name' => 'XII RPL 1']);
        $room = Room::create(['name' => 'Lab Komputer']);

        // 2. Buat jadwal dengan year1
        $schedule = Schedule::create([
            'academic_year_id' => $year1->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:30',
            'end_time' => '09:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Buat penempatan kelas di pivot user_class
        $studentRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
        $student = User::factory()->create(['role_id' => $studentRole->id]);
        $student->classrooms()->attach($classroom->id, [
            'academic_year_id' => $year1->id,
            'status' => 'Aktif',
        ]);

        $this->assertEquals($year1->id, $schedule->fresh()->academic_year_id);
        $this->assertEquals($year1->id, DB::table('user_class')->where('user_id', $student->id)->value('academic_year_id'));

        // 3. SEKARANG AKTIFKAN YEAR 2 (Simulasi edit form atau action Set Aktif)
        $year2->update(['is_active' => true]);

        // Verifikasi hanya Year 2 yang aktif, Year 1 otomatis nonaktif
        $this->assertFalse($year1->fresh()->is_active);
        $this->assertTrue($year2->fresh()->is_active);

        // Verifikasi kolom academic_year_id di tabel schedules OTOMATIS terisi year2
        $this->assertEquals($year2->id, $schedule->fresh()->academic_year_id);

        // Verifikasi kolom academic_year_id di tabel user_class OTOMATIS terisi year2
        $this->assertEquals($year2->id, DB::table('user_class')->where('user_id', $student->id)->value('academic_year_id'));
    }

    public function test_new_schedule_without_academic_year_id_automatically_gets_active_academic_year(): void
    {
        $activeYear = AcademicYear::create(['name' => '2026/2027 - Ganjil', 'is_active' => true]);

        $teacherRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $teacher = User::factory()->create(['role_id' => $teacherRole->id]);
        $subject = Subject::create(['name' => 'Bahasa Indonesia']);
        $classroom = Classroom::create(['name' => 'X RPL 1']);
        $room = Room::create(['name' => 'Teori 1']);

        // Buat schedule tanpa mengirim academic_year_id
        $schedule = Schedule::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Selasa',
            'start_time' => '07:30',
            'end_time' => '09:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Otomatis terisi activeYear id
        $this->assertEquals($activeYear->id, $schedule->academic_year_id);
    }
}
