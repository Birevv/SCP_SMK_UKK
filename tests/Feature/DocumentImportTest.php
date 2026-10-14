<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Services\DocumentParserService;
use App\Services\ScheduleImportService;
use App\Services\UserImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Wali Kelas', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru BK', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);

        AcademicYear::firstOrCreate(['name' => '2024/2025', 'is_active' => true]);
    }

    public function test_document_parser_can_parse_csv_content(): void
    {
        $csvContent = "guru,nip,mata_pelajaran,kelas,ruangan,hari,jam_mulai,jam_selesai,putaran\n"
            ."Budi Santoso,198002022005011002,Pemrograman Web,XII RPL 1,Lab Komputer 1,Senin,07:30,09:30,Putaran 1\n";

        $tmpFile = tempnam(sys_get_temp_dir(), 'test_csv_').'.csv';
        file_put_contents($tmpFile, $csvContent);

        $parser = new DocumentParserService;
        $rows = $parser->parse($tmpFile);

        @unlink($tmpFile);

        $this->assertCount(1, $rows);
        $this->assertEquals('Budi Santoso', $rows[0]['guru']);
        $this->assertEquals('Pemrograman Web', $rows[0]['mata_pelajaran']);
        $this->assertEquals('XII RPL 1', $rows[0]['kelas']);
        $this->assertEquals('07:30', $rows[0]['jam_mulai']);
        $this->assertEquals('09:30', $rows[0]['jam_selesai']);
    }

    public function test_schedule_import_auto_creates_teacher_and_schedules(): void
    {
        $rows = [
            [
                'guru' => 'Pak Joko Susilo, M.Kom',
                'nip' => '198205052008011005',
                'mata_pelajaran' => 'Kecerdasan Buatan',
                'kelas' => 'XII RPL 2',
                'ruangan' => 'Lab AI',
                'hari' => 'Senin',
                'jam_mulai' => '07:30',
                'jam_selesai' => '09:30',
                'putaran' => 'Putaran 1',
            ],
            [
                'guru' => 'Pak Joko Susilo, M.Kom',
                'nip' => '198205052008011005',
                'mata_pelajaran' => 'Kecerdasan Buatan',
                'kelas' => 'XII RPL 3',
                'ruangan' => 'Lab AI',
                'hari' => 'Selasa',
                'jam_mulai' => '10:00',
                'jam_selesai' => '12:00',
                'putaran' => 'Putaran 1',
            ],
        ];

        $wakaUser = User::factory()->create();
        $wakaUser->assignRole('Waka Kurikulum');

        $importer = new ScheduleImportService;
        $result = $importer->import($rows, $wakaUser);

        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(1, $result['teachers_created']);
        $this->assertEquals(0, $result['skipped']);

        // Assert teacher user was auto-created in users table with Guru role
        $teacher = User::where('full_name', 'Pak Joko Susilo, M.Kom')->first();
        $this->assertNotNull($teacher);
        $this->assertEquals('198205052008011005', $teacher->identity_number);
        $this->assertTrue($teacher->hasRole('Guru'));

        // Assert schedules were created in database
        $this->assertDatabaseHas('schedules', [
            'teacher_id' => $teacher->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:30',
            'end_time' => '09:30',
        ]);
        $this->assertDatabaseHas('schedules', [
            'teacher_id' => $teacher->id,
            'day_of_week' => 'Selasa',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        // Assert master data auto-created
        $this->assertDatabaseHas('subjects', ['name' => 'Kecerdasan Buatan']);
        $this->assertDatabaseHas('classrooms', ['name' => 'XII RPL 2']);
        $this->assertDatabaseHas('rooms', ['name' => 'Lab AI']);
    }

    public function test_schedule_import_detects_conflict(): void
    {
        $teacher = User::factory()->create(['full_name' => 'Guru Tetap']);
        $teacher->assignRole('Guru');

        $subject = Subject::create(['name' => 'Algoritma']);
        $classroom1 = Classroom::create(['name' => 'X RPL 1']);
        $classroom2 = Classroom::create(['name' => 'X RPL 2']);
        $room = Room::create(['name' => 'Lab 1']);
        $year = AcademicYear::first();

        // Existing schedule
        Schedule::create([
            'academic_year_id' => $year->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom1->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Senin',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Row attempting to double-book the same teacher at 08:30-09:30
        $rows = [
            [
                'guru' => 'Guru Tetap',
                'mata_pelajaran' => 'Algoritma',
                'kelas' => 'X RPL 2',
                'ruangan' => 'Lab 2',
                'hari' => 'Senin',
                'jam_mulai' => '08:30',
                'jam_selesai' => '09:30',
                'putaran' => 'Putaran 1',
            ],
        ];

        $importer = new ScheduleImportService;
        $result = $importer->import($rows);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_user_import_creates_users_with_roles_and_classrooms(): void
    {
        $rows = [
            [
                'nama_lengkap' => 'Bintang Siswa Teladan',
                'username' => 'bintang123',
                'role' => 'Siswa',
                'nisn_nip' => '0098765432',
                'email' => 'bintang@scp.sch.id',
                'telepon' => '08123456789',
                'kelas' => 'X RPL 1',
                'status' => 'Aktif',
            ],
            [
                'nama_lengkap' => 'Ibu Sri Rahayu, S.Pd',
                'role' => 'Wali Kelas',
                'nisn_nip' => '198701012011012007',
                'kelas' => 'X RPL 1',
                'status' => 'Aktif',
            ],
        ];

        $wakaUser = User::factory()->create();
        $wakaUser->assignRole('Waka Kurikulum');

        $importer = new UserImportService;
        $result = $importer->import($rows, $wakaUser);

        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['skipped']);

        // Assert Siswa created
        $student = User::where('username', 'bintang123')->first();
        $this->assertNotNull($student);
        $this->assertEquals('Bintang Siswa Teladan', $student->full_name);
        $this->assertTrue($student->hasRole('Siswa'));
        $this->assertTrue($student->classrooms()->where('name', 'X RPL 1')->exists());

        // Assert Wali Kelas created
        $wali = User::where('identity_number', '198701012011012007')->first();
        $this->assertNotNull($wali);
        $this->assertTrue($wali->hasRole('Wali Kelas'));
        $this->assertTrue($wali->classrooms()->where('name', 'X RPL 1')->exists());
    }
}
