<?php

namespace Tests\Feature;

use App\Enums\ReasonTypeEnum;
use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\LeaveRequest;
use App\Models\Major;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SystemAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $waliRole = Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);

        foreach (['ViewAny:Journal', 'View:Journal', 'Create:Journal', 'Update:Journal'] as $permName) {
            $p = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            $guruRole->givePermissionTo($p);
            $waliRole->givePermissionTo($p);
        }

        AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);
    }

    public function test_walikelas_can_store_journal_for_their_teaching_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00')); // Rabu

        $teacherRole = Role::where('name', 'Wali Kelas')->first();
        $teacher = User::factory()->create([
            'role_id' => $teacherRole->id,
            'full_name' => 'Budi Santoso, M.Kom',
        ]);
        $teacher->assignRole('Wali Kelas');

        $level = ClassLevel::create(['level' => 10, 'name' => 'X']);
        $major = Major::create(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $rombel = Rombel::create(['name' => '1', 'number' => 1]);

        $classroom = Classroom::create([
            'name' => 'X RPL 1',
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel->id,
        ]);

        $subject = Subject::create(['name' => 'Pemrograman Web', 'code' => 'PWEB']);
        $room = Room::create(['name' => 'Lab RPL 1']);
        $student = User::factory()->create();

        $schedule = Schedule::create([
            'academic_year_id' => AcademicYear::first()->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day_of_week' => 'Rabu',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $photo = UploadedFile::fake()->image('activity.jpg');

        $payload = [
            'schedule_id' => $schedule->id,
            'date' => Carbon::today()->toDateString(),
            'teaching_material' => 'Pengenalan HTML dan CSS Dasar',
            'notes' => 'Siswa sangat antusias mengikuti praktikum.',
            'activity_photo' => $photo,
            'attendances' => [
                [
                    'student_id' => $student->id,
                    'status' => 'Hadir',
                    'notes' => 'Tepat waktu',
                ],
            ],
        ];

        $response = $this->actingAs($teacher, 'sanctum')
            ->postJson('/api/teacher/journals', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('journals', [
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'teaching_material' => 'Pengenalan HTML dan CSS Dasar',
        ]);
    }

    public function test_pure_bk_is_forbidden_from_storing_teaching_journal(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));

        $bkRole = Role::where('name', 'Guru BK')->first();
        $bkTeacher = User::factory()->create([
            'role_id' => $bkRole->id,
            'full_name' => 'Guru BK Sekolah',
        ]);
        $bkTeacher->assignRole('Guru BK');

        $level = ClassLevel::create(['level' => 11, 'name' => 'XI']);
        $major = Major::create(['code' => 'TKJ', 'name' => 'Teknik Komputer']);
        $rombel = Rombel::create(['name' => '1', 'number' => 1]);
        $classroom = Classroom::create([
            'name' => 'XI TKJ 1',
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel->id,
        ]);
        $subject = Subject::create(['name' => 'Bimbingan Konseling', 'code' => 'BK1']);
        $room = Room::create(['name' => 'Ruang BK']);

        $schedule = Schedule::create([
            'academic_year_id' => AcademicYear::first()->id,
            'teacher_id' => $bkTeacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day_of_week' => 'Rabu',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $student = User::factory()->create();
        $photo = UploadedFile::fake()->image('activity.jpg');

        $payload = [
            'schedule_id' => $schedule->id,
            'date' => Carbon::today()->toDateString(),
            'teaching_material' => 'Materi Bimbingan',
            'notes' => 'Catatan BK',
            'activity_photo' => $photo,
            'attendances' => [
                [
                    'student_id' => $student->id,
                    'status' => 'Hadir',
                ],
            ],
        ];

        $response = $this->actingAs($bkTeacher, 'sanctum')
            ->postJson('/api/teacher/journals', $payload);

        $response->assertStatus(403);
    }

    public function test_leave_request_reason_type_and_description_are_properly_saved_and_normalized(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));

        $studentRole = Role::where('name', 'Siswa')->first();
        $student = User::factory()->create([
            'role_id' => $studentRole->id,
            'full_name' => 'Siswa Berprestasi',
        ]);
        $student->assignRole('Siswa');

        $photo = UploadedFile::fake()->image('surat_dokter.jpg');

        $response = $this->actingAs($student, 'sanctum')
            ->postJson('/api/student/leave-requests', [
                'start_date' => Carbon::today()->toDateString(),
                'end_date' => Carbon::today()->addDay()->toDateString(),
                'reason_type' => 'Sakit',
                'description' => 'Sakit demam tinggi dan flu berat',
                'attachment_photo' => $photo,
            ]);

        $response->assertStatus(201);

        $leave = LeaveRequest::first();
        $this->assertNotNull($leave);
        $this->assertEquals('Sakit demam tinggi dan flu berat', $leave->description);
        $this->assertEquals('Sakit', $leave->reason_type);
    }

    public function test_journal_resource_query_isolates_by_teacher_id_for_non_admins(): void
    {
        $waliRole = Role::where('name', 'Wali Kelas')->first();
        $teacherA = User::factory()->create([
            'role_id' => $waliRole->id,
            'full_name' => 'Guru A Merangkap Wali Kelas',
        ]);
        $teacherA->assignRole('Wali Kelas');

        $teacherB = User::factory()->create([
            'role_id' => $waliRole->id,
            'full_name' => 'Guru B Pengajar Lain',
        ]);
        $teacherB->assignRole('Wali Kelas');

        $classroom = Classroom::create(['name' => 'XII RPL 1']);
        $subject = Subject::create(['name' => 'Basis Data', 'code' => 'BD1']);
        $room = Room::create(['name' => 'Lab Database']);

        $schedA = Schedule::create([
            'academic_year_id' => AcademicYear::first()->id,
            'teacher_id' => $teacherA->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day_of_week' => 'Rabu',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $schedB = Schedule::create([
            'academic_year_id' => AcademicYear::first()->id,
            'teacher_id' => $teacherB->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day_of_week' => 'Rabu',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $journalA = Journal::create([
            'schedule_id' => $schedA->id,
            'teacher_id' => $teacherA->id,
            'date' => Carbon::today()->toDateString(),
            'teaching_material' => 'Materi Milik Guru A',
            'notes' => 'Catatan A',
            'activity_photo' => 'journal_photos/photo_a.jpg',
        ]);

        $journalB = Journal::create([
            'schedule_id' => $schedB->id,
            'teacher_id' => $teacherB->id,
            'date' => Carbon::today()->toDateString(),
            'teaching_material' => 'Materi Milik Guru B',
            'notes' => 'Catatan B',
            'activity_photo' => 'journal_photos/photo_b.jpg',
        ]);

        $this->actingAs($teacherA, 'web');
        $queryA = \App\Filament\Resources\Journals\JournalResource::getEloquentQuery();
        $this->assertEquals(1, $queryA->count());
        $this->assertEquals($journalA->id, $queryA->first()->id);

        $this->actingAs($teacherB, 'web');
        $queryB = \App\Filament\Resources\Journals\JournalResource::getEloquentQuery();
        $this->assertEquals(1, $queryB->count());
        $this->assertEquals($journalB->id, $queryB->first()->id);
    }

    public function test_bk_without_classrooms_gets_empty_schedule_query(): void
    {
        $bkRole = Role::where('name', 'Guru BK')->first();
        $bkUser = User::factory()->create([
            'role_id' => $bkRole->id,
            'full_name' => 'Guru BK Baru',
        ]);
        $bkUser->assignRole('Guru BK');

        $this->actingAs($bkUser, 'web');
        $query = \App\Filament\Resources\Schedules\ScheduleResource::getEloquentQuery();
        $this->assertEquals(0, $query->count());
    }
}
