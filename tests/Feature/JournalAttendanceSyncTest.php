<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
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
        $academicYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Pemrograman Web']);

        $siswaRole = \App\Models\Role::create(['name' => 'Siswa']);
        $waliRole = \App\Models\Role::create(['name' => 'Wali Kelas']);

        $student1 = User::factory()->create(['role_id' => $siswaRole->id]);
        $student1->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);
        
        $student2 = User::factory()->create(['role_id' => $siswaRole->id]);
        $student2->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        $waliKelas = User::factory()->create(['role_id' => $waliRole->id]);
        $waliKelas->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

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
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
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

        // Pastikan Wali Kelas yang terdaftar di kelas tidak terinput ke daftar absensi
        $this->assertDatabaseMissing('attendances', [
            'journal_id' => $journal->id,
            'student_id' => $waliKelas->id,
        ]);
    }
}