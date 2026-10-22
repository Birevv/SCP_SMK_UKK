<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
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

        $siswaRole = Role::create(['name' => 'Siswa']);
        $waliRole = Role::create(['name' => 'Wali Kelas']);

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

    public function test_attendance_status_defaults_to_hadir_and_can_be_updated(): void
    {
        $classroom = Classroom::factory()->create();
        $teacher = User::factory()->create();
        $room = Room::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2026/2027 Ganjil', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Basis Data']);

        $siswaRole = Role::create(['name' => 'Siswa']);
        $student = User::factory()->create(['role_id' => $siswaRole->id]);
        $student->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

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
            'teaching_material' => 'Normalisasi Database',
        ]);

        $attendance = $journal->attendances()->where('student_id', $student->id)->first();
        $this->assertNotNull($attendance);
        // Pastikan default kehadiran adalah Hadir
        $this->assertEquals('Hadir', $attendance->status);

        // Status kehadiran bisa diubah menjadi Sakit, Izin, atau Alpa
        $attendance->update([
            'status' => 'Izin',
            'notes' => 'Izin keperluan keluarga',
        ]);

        $this->assertEquals('Izin', $attendance->fresh()->status);
        $this->assertEquals('Izin keperluan keluarga', $attendance->fresh()->notes);

        // Ubah lagi menjadi Alpa
        $attendance->update([
            'status' => 'Alpa',
            'notes' => 'Tanpa keterangan',
        ]);

        $this->assertEquals('Alpa', $attendance->fresh()->status);
    }

    public function test_get_students_attendance_data_helper_returns_students_and_syncs_leave(): void
    {
        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2026/2027 Genap', 'is_active' => true]);
        $teacher = User::factory()->create();
        $room = Room::factory()->create();
        $subject = Subject::create(['name' => 'Pemrograman Berorientasi Objek']);

        $siswaRole = Role::create(['name' => 'Siswa']);
        $student1 = User::factory()->create(['role_id' => $siswaRole->id, 'full_name' => 'Budi Santoso']);
        $student1->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        $student2 = User::factory()->create(['role_id' => $siswaRole->id, 'full_name' => 'Citra Lestari']);
        $student2->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        LeaveRequest::create([
            'student_id' => $student1->id,
            'submitted_by' => $student1->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason_type' => 'Izin',
            'description' => 'Acara keluarga',
            'status' => 'Approved',
        ]);

        $schedule = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Selasa',
            'start_time' => '07:15:00',
            'end_time' => '08:35:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $attendances = \App\Filament\Resources\Journals\Schemas\JournalForm::getStudentsAttendanceData($schedule->id, now()->toDateString());

        $this->assertCount(2, $attendances);
        $budi = collect($attendances)->firstWhere('student_id', $student1->id);
        $citra = collect($attendances)->firstWhere('student_id', $student2->id);

        $this->assertNotNull($budi);
        $this->assertEquals('Izin', $budi['status']);
        $this->assertStringContainsString('Surat Izin', $budi['notes']);

        $this->assertNotNull($citra);
        $this->assertEquals('Hadir', $citra['status']);
        $this->assertNull($citra['notes']);
    }

    public function test_create_journal_page_saves_custom_attendances(): void
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $siswaRole = Role::create(['name' => 'Siswa', 'guard_name' => 'web']);

        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2026/2027 Ganjil', 'is_active' => true]);
        $teacher = User::factory()->create(['role_id' => $superAdminRole->id]);
        $teacher->assignRole('Super Admin');
        $room = Room::factory()->create();
        $subject = Subject::create(['name' => 'Cloud Computing']);

        $student1 = User::factory()->create(['role_id' => $siswaRole->id, 'full_name' => 'Ahmad Fauzi']);
        $student1->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        $student2 = User::factory()->create(['role_id' => $siswaRole->id, 'full_name' => 'Dewi Anggraini']);
        $student2->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        $schedule = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Rabu',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        \Livewire\Livewire::actingAs($teacher)
            ->test(\App\Filament\Resources\Journals\Pages\CreateJournal::class)
            ->fillForm([
                'schedule_id' => $schedule->id,
                'date' => now()->toDateString(),
                'teaching_material' => 'Pengenalan Docker dan Containerization',
                'attendances' => [
                    [
                        'student_id' => $student1->id,
                        'student_name' => $student1->full_name,
                        'status' => 'Alpa',
                        'notes' => 'Tidak hadir tanpa keterangan',
                    ],
                    [
                        'student_id' => $student2->id,
                        'student_name' => $student2->full_name,
                        'status' => 'Hadir',
                        'notes' => null,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $journal = Journal::where('schedule_id', $schedule->id)->first();
        $this->assertNotNull($journal);
        $this->assertEquals('Pengenalan Docker dan Containerization', $journal->teaching_material);

        $this->assertDatabaseHas('attendances', [
            'journal_id' => $journal->id,
            'student_id' => $student1->id,
            'status' => 'Alpa',
            'notes' => 'Tidak hadir tanpa keterangan',
        ]);

        $this->assertDatabaseHas('attendances', [
            'journal_id' => $journal->id,
            'student_id' => $student2->id,
            'status' => 'Hadir',
        ]);
    }

    public function test_edit_journal_displays_attendances_relation_manager_and_updates_status(): void
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $siswaRole = Role::create(['name' => 'Siswa', 'guard_name' => 'web']);

        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2026/2027 Ganjil', 'is_active' => true]);
        $teacher = User::factory()->create(['role_id' => $superAdminRole->id]);
        $teacher->assignRole('Super Admin');
        $room = Room::factory()->create();
        $subject = Subject::create(['name' => 'Pemrograman Framework']);

        $student = User::factory()->create(['role_id' => $siswaRole->id, 'full_name' => 'Ahmad Fauzi']);
        $student->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        $schedule = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Kamis',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'date' => now()->toDateString(),
            'teaching_material' => 'Materi Pemrograman',
        ]);

        $attendance = $journal->attendances()->where('student_id', $student->id)->first();
        $this->assertNotNull($attendance);

        // AttendancesRelationManager table can be tested
        \Livewire\Livewire::actingAs($teacher)
            ->test(\App\Filament\Resources\Journals\RelationManagers\AttendancesRelationManager::class, [
                'ownerRecord' => $journal,
                'pageClass' => \App\Filament\Resources\Journals\Pages\EditJournal::class,
            ])
            ->assertCanSeeTableRecords([$attendance]);
    }

    public function test_create_journal_page_prefills_attendances_when_schedule_id_in_query_param(): void
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $siswaRole = Role::create(['name' => 'Siswa', 'guard_name' => 'web']);

        $classroom = Classroom::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2026/2027 Ganjil', 'is_active' => true]);
        $teacher = User::factory()->create(['role_id' => $superAdminRole->id]);
        $teacher->assignRole('Super Admin');
        $room = Room::factory()->create();
        $subject = Subject::create(['name' => 'Pemrograman Web']);

        $student = User::factory()->create(['role_id' => $siswaRole->id, 'full_name' => 'Rina Nose']);
        $student->classrooms()->attach($classroom->id, ['academic_year_id' => $academicYear->id]);

        $schedule = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Kamis',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        \Livewire\Livewire::withQueryParams(['schedule_id' => $schedule->id])
            ->actingAs($teacher)
            ->test(\App\Filament\Resources\Journals\Pages\CreateJournal::class)
            ->assertSchemaStateSet([
                'schedule_id' => $schedule->id,
            ]);
    }
}
