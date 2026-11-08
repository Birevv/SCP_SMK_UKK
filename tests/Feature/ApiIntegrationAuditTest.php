<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiIntegrationAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $homeroom;

    private User $student;

    private Classroom $classroom;

    private Classroom $otherClassroom;

    private Subject $subject;

    private Room $room;

    private AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);

        $this->academicYear = AcademicYear::create([
            'name' => '2025/2026',
            'is_active' => true,
        ]);

        $this->classroom = Classroom::create(['name' => 'XII RPL 1']);
        $this->otherClassroom = Classroom::create(['name' => 'XII RPL 2']);
        $this->subject = Subject::create(['name' => 'Pemrograman Berorientasi Objek']);
        $this->room = Room::create(['name' => 'Lab Komputer 1']);

        // Teacher
        $this->teacher = User::factory()->create(['role_id' => Role::where('name', 'Guru')->first()->id]);
        $this->teacher->assignRole('Guru');

        // Homeroom teacher
        $this->homeroom = User::factory()->create(['role_id' => Role::where('name', 'Wali Kelas')->first()->id]);
        $this->homeroom->assignRole('Wali Kelas');
        $this->homeroom->classrooms()->attach($this->classroom->id, [
            'academic_year_id' => $this->academicYear->id,
        ]);

        // Student
        $this->student = User::factory()->create(['role_id' => Role::where('name', 'Siswa')->first()->id]);
        $this->student->assignRole('Siswa');
        $this->student->classrooms()->attach($this->classroom->id, [
            'academic_year_id' => $this->academicYear->id,
        ]);
    }

    /**
     * Test P0: StoreScheduleRequest validation fixes for classrooms and subjects tables
     */
    public function test_teacher_can_add_schedule_with_valid_classroom_and_subject(): void
    {
        $this->actingAs($this->teacher, 'sanctum');

        $payload = [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'room_id' => $this->room->id,
            'day_of_week' => 'Senin',
            'cycle_type' => 'Putaran 1',
            'start_time' => '07:15',
            'end_time' => '09:15',
        ];

        $response = $this->postJson('/api/teacher/schedules', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.day_of_week', 'Senin')
            ->assertJsonPath('data.classroom_name', 'XII RPL 1')
            ->assertJsonPath('data.subject_name', 'Pemrograman Berorientasi Objek');

        $this->assertDatabaseHas('schedules', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'day_of_week' => 'Senin',
        ]);
    }

    public function test_store_schedule_fails_when_classroom_or_subject_invalid(): void
    {
        $this->actingAs($this->teacher, 'sanctum');

        $response = $this->postJson('/api/teacher/schedules', [
            'classroom_id' => 99999, // non-existent
            'subject_id' => 88888,   // non-existent
            'room_id' => $this->room->id,
            'day_of_week' => 'Senin',
            'cycle_type' => 'Putaran 1',
            'start_time' => '07:15',
            'end_time' => '09:15',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['classroom_id', 'subject_id']);
    }

    public function test_store_schedule_prevents_conflicts(): void
    {
        Schedule::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->academicYear->id,
            'day_of_week' => 'Senin',
            'cycle_type' => 'Putaran 1',
            'start_time' => '07:15',
            'end_time' => '09:15',
        ]);

        $this->actingAs($this->teacher, 'sanctum');

        // Conflicting time for same classroom & teacher
        $response = $this->postJson('/api/teacher/schedules', [
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'room_id' => $this->room->id,
            'day_of_week' => 'Senin',
            'cycle_type' => 'Putaran 1',
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.conflict_type', 'schedule');
    }

    /**
     * Test P1: Student weekly schedules API (GET /api/student/schedules)
     */
    public function test_student_can_fetch_weekly_schedules_with_cycle_and_day_filters(): void
    {
        // Schedule 1: Senin, Putaran 1 for student classroom
        $sched1 = Schedule::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->academicYear->id,
            'day_of_week' => 'Senin',
            'cycle_type' => 'Putaran 1',
            'start_time' => '07:15',
            'end_time' => '09:15',
        ]);

        // Schedule 2: Selasa, Putaran 1 for student classroom
        $sched2 = Schedule::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->academicYear->id,
            'day_of_week' => 'Selasa',
            'cycle_type' => 'Putaran 1',
            'start_time' => '09:30',
            'end_time' => '11:30',
        ]);

        // Schedule 3: Senin, Putaran 2 for student classroom
        Schedule::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->academicYear->id,
            'day_of_week' => 'Senin',
            'cycle_type' => 'Putaran 2',
            'start_time' => '07:15',
            'end_time' => '09:15',
        ]);

        // Schedule 4: Other classroom schedule (must be isolated)
        Schedule::create([
            'classroom_id' => $this->otherClassroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->academicYear->id,
            'day_of_week' => 'Senin',
            'cycle_type' => 'Putaran 1',
            'start_time' => '07:15',
            'end_time' => '09:15',
        ]);

        $this->actingAs($this->student, 'sanctum');

        // All Putaran 1 schedules for this student
        $resAll = $this->getJson('/api/student/schedules?cycle_type=Putaran%201');
        $resAll->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.teacher_name', $this->teacher->full_name);

        // Filter by day: Senin only
        $resSenin = $this->getJson('/api/student/schedules?cycle_type=Putaran%201&day_of_week=Senin');
        $resSenin->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sched1->id);

        // Filter by day: Selasa only
        $resSelasa = $this->getJson('/api/student/schedules?cycle_type=Putaran%201&day_of_week=Selasa');
        $resSelasa->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sched2->id);
    }

    /**
     * Test P1: Homeroom students API (GET /api/homeroom/students)
     */
    public function test_homeroom_teacher_can_fetch_roster_with_attendance_stats(): void
    {
        // Add second student to homeroom class
        $student2 = User::factory()->create(['role_id' => Role::where('name', 'Siswa')->first()->id]);
        $student2->assignRole('Siswa');
        $student2->classrooms()->attach($this->classroom->id, [
            'academic_year_id' => $this->academicYear->id,
        ]);

        // Add schedule, teaching journal and attendance for student 1
        $schedule = Schedule::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->academicYear->id,
            'day_of_week' => 'Senin',
            'cycle_type' => 'Putaran 1',
            'start_time' => '07:15',
            'end_time' => '09:15',
        ]);

        // Journal creation triggers JournalObserver which auto-generates attendance (Hadir) for classroom students
        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $this->teacher->id,
            'date' => Carbon::today('Asia/Jakarta')->toDateString(),
            'teaching_material' => 'Materi Pertemuan 1',
        ]);

        // Update student 2 to 'Sakit'
        Attendance::where('journal_id', $journal->id)
            ->where('student_id', $student2->id)
            ->update(['status' => 'Sakit']);

        $this->actingAs($this->homeroom, 'sanctum');

        $response = $this->getJson('/api/homeroom/students');
        $response->assertOk()
            ->assertJsonPath('data.total_students', 2)
            ->assertJsonPath('data.classroom.name', 'XII RPL 1');

        $students = collect($response->json('data.students'));
        $s1 = $students->firstWhere('id', $this->student->id);
        $this->assertEquals(1, $s1['hadir']);
        $this->assertEquals('Hadir', $s1['today_status']);

        $s2 = $students->firstWhere('id', $student2->id);
        $this->assertEquals(1, $s2['sakit']);
        $this->assertEquals('Sakit', $s2['today_status']);
    }

    public function test_non_homeroom_user_cannot_access_homeroom_students(): void
    {
        $this->actingAs($this->teacher, 'sanctum');

        $response = $this->getJson('/api/homeroom/students');
        $response->assertStatus(403);
    }

    /**
     * Test P2: Homeroom dashboard percentage key compatibility
     */
    public function test_homeroom_dashboard_returns_standardized_and_compat_percentage(): void
    {
        $this->actingAs($this->homeroom, 'sanctum');

        $response = $this->getJson('/api/homeroom/dashboard');
        $response->assertOk();

        $data = $response->json('data');
        $this->assertArrayHasKey('attendance_today', $data);
        $this->assertArrayHasKey('percentage', $data['attendance_today']);
        $this->assertArrayHasKey('precentage', $data['attendance_today']); // compat key
        $this->assertEquals($data['attendance_today']['percentage'], $data['attendance_today']['precentage']);
    }

    /**
     * Test P2: Leave request detail duration_days standardization and duartion_days compat
     */
    public function test_homeroom_leave_request_detail_returns_duration_days_and_alias(): void
    {
        $leave = LeaveRequest::create([
            'student_id' => $this->student->id,
            'submitted_by' => $this->student->id,
            'reason_type' => 'Izin',
            'start_date' => Carbon::today('Asia/Jakarta'),
            'end_date' => Carbon::today('Asia/Jakarta')->addDays(2),
            'description' => 'Acara keluarga penting',
            'status' => 'Pending',
        ]);

        $this->actingAs($this->homeroom, 'sanctum');

        $response = $this->getJson("/api/homeroom/leave-requests/{$leave->id}");
        $response->assertOk();

        $data = $response->json('data.leave_info');
        $this->assertEquals(3, $data['duration_days']);
        $this->assertEquals(3, $data['duartion_days']);
    }

    /**
     * Test P2: Teacher dashboard delayed journal threshold triggers after 30 minutes
     */
    public function test_teacher_dashboard_delayed_journal_alert_threshold_is_30_minutes(): void
    {
        // Mock Monday at 10:00
        Carbon::setTestNow(Carbon::parse('2026-11-09 10:00:00', 'Asia/Jakarta'));

        // System config for active cycle
        SystemConfiguration::updateOrCreate(
            ['key' => 'active_cycle'],
            ['value' => 'Putaran 1']
        );

        // Create schedule on Senin ending at 09:15 (45 minutes ago) without journal
        Schedule::create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'room_id' => $this->room->id,
            'academic_year_id' => $this->academicYear->id,
            'day_of_week' => 'Senin',
            'cycle_type' => 'Putaran 1',
            'start_time' => '07:15',
            'end_time' => '09:15',
        ]);

        $this->actingAs($this->teacher, 'sanctum');

        $response = $this->getJson('/api/teacher/dashboard');
        $response->assertOk();

        $pending = $response->json('data.pending_journals');
        $this->assertNotEmpty($pending);
        $this->assertEquals(30, $pending[0]['minutes_late']);
        $this->assertStringContainsString('>30 menit terlambat', $pending[0]['message']);

        Carbon::setTestNow(); // Reset mock
    }

    public function test_alumni_student_cannot_login_via_api(): void
    {
        $this->student->update([
            'status' => 'Lulus',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'login' => $this->student->username,
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Akun Anda berstatus Alumni (Sudah Lulus). Hak akses ke portal telah dinonaktifkan.',
        ]);
    }
}
