<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\Major;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherJournalUpdateApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;

    protected User $otherTeacher;

    protected User $student1;

    protected User $student2;

    protected Schedule $schedule;

    protected Journal $journal;

    protected function setUp(): void
    {
        parent::setUp();

        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $siswaRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);

        $academicYear = AcademicYear::create([
            'name' => '2026/2027 - Ganjil',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        $this->teacher = User::factory()->create([
            'role_id' => $guruRole->id,
            'full_name' => 'Budi Santoso, S.Kom',
        ]);
        $this->teacher->assignRole('Guru');

        $this->otherTeacher = User::factory()->create([
            'role_id' => $guruRole->id,
            'full_name' => 'Siti Aminah, M.Pd',
        ]);
        $this->otherTeacher->assignRole('Guru');

        $this->student1 = User::factory()->create([
            'role_id' => $siswaRole->id,
            'full_name' => 'Ahmad Fauzan',
        ]);
        $this->student1->assignRole('Siswa');

        $this->student2 = User::factory()->create([
            'role_id' => $siswaRole->id,
            'full_name' => 'Bunga Citra',
        ]);
        $this->student2->assignRole('Siswa');

        $level = ClassLevel::create(['level' => 12, 'name' => 'XII']);
        $major = Major::create(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $rombel = Rombel::create(['name' => '1', 'number' => 1]);

        $classroom = Classroom::create([
            'name' => 'XII RPL 1',
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel->id,
        ]);

        $classroom->users()->attach([
            $this->student1->id => ['status' => 'Aktif', 'academic_year_id' => $academicYear->id],
            $this->student2->id => ['status' => 'Aktif', 'academic_year_id' => $academicYear->id],
        ]);

        $subject = Subject::create([
            'code' => 'PPLG-01',
            'name' => 'Pemrograman Web',
        ]);

        $room = Room::create([
            'code' => 'LAB-RPL-1',
            'name' => 'Lab Komputer RPL 1',
            'type' => 'Lab',
        ]);

        $this->schedule = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day_of_week' => 'Kamis',
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->journal = Journal::create([
            'schedule_id' => $this->schedule->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-19',
            'teaching_material' => 'Materi Awal Laravel',
            'notes' => 'Catatan awal',
            'activity_photo' => 'journal_photos/old_photo.jpg',
        ]);

        Attendance::updateOrCreate(
            ['journal_id' => $this->journal->id, 'student_id' => $this->student1->id],
            ['status' => 'Hadir', 'date' => '2026-11-19', 'notes' => null]
        );
        Attendance::updateOrCreate(
            ['journal_id' => $this->journal->id, 'student_id' => $this->student2->id],
            ['status' => 'Hadir', 'date' => '2026-11-19', 'notes' => null]
        );
    }

    public function test_teacher_can_fetch_own_journal_detail_with_attendances(): void
    {
        Sanctum::actingAs($this->teacher);

        $response = $this->getJson("/api/teacher/journals/{$this->journal->id}");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $this->journal->id)
            ->assertJsonPath('data.schedule_id', $this->schedule->id)
            ->assertJsonPath('data.teaching_material', 'Materi Awal Laravel')
            ->assertJsonPath('data.time_slot', '07:30 - 09:00 WIB')
            ->assertJsonCount(2, 'data.attendances');
    }

    public function test_teacher_can_update_own_journal_material_notes_and_attendances(): void
    {
        Sanctum::actingAs($this->teacher);

        $response = $this->putJson("/api/teacher/journals/{$this->journal->id}", [
            'teaching_material' => 'Materi Diperbarui: REST API & Sanctum',
            'notes' => 'Semua siswa aktif mengerjakan praktik',
            'attendances' => [
                [
                    'student_id' => $this->student1->id,
                    'status' => 'Hadir',
                    'notes' => null,
                ],
                [
                    'student_id' => $this->student2->id,
                    'status' => 'Sakit',
                    'notes' => 'Demam ringan',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.teaching_material', 'Materi Diperbarui: REST API & Sanctum')
            ->assertJsonPath('data.notes', 'Semua siswa aktif mengerjakan praktik')
            ->assertJsonPath('data.present_count', 1)
            ->assertJsonPath('data.sick_count', 1);

        $this->assertDatabaseHas('journals', [
            'id' => $this->journal->id,
            'teaching_material' => 'Materi Diperbarui: REST API & Sanctum',
            'notes' => 'Semua siswa aktif mengerjakan praktik',
            'activity_photo' => 'journal_photos/old_photo.jpg',
        ]);

        $this->assertDatabaseHas('attendances', [
            'journal_id' => $this->journal->id,
            'student_id' => $this->student2->id,
            'status' => 'Sakit',
            'notes' => 'Demam ringan',
        ]);
    }

    public function test_teacher_can_replace_activity_photo_via_multipart_post(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('journal_photos/old_photo.jpg', 'old-image-content');

        Sanctum::actingAs($this->teacher);

        $newPhoto = UploadedFile::fake()->image('kegiatan_baru.jpg', 800, 600);

        $response = $this->post("/api/teacher/journals/{$this->journal->id}", [
            'teaching_material' => 'Materi dengan foto baru',
            'notes' => 'Foto diperbarui',
            'activity_photo' => $newPhoto,
            'attendances' => [
                [
                    'student_id' => $this->student1->id,
                    'status' => 'Hadir',
                ],
            ],
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.teaching_material', 'Materi dengan foto baru');

        $this->journal->refresh();
        $this->assertNotEquals('journal_photos/old_photo.jpg', $this->journal->activity_photo);
        Storage::disk('public')->assertExists($this->journal->activity_photo);
        Storage::disk('public')->assertMissing('journal_photos/old_photo.jpg');
    }

    public function test_other_teacher_cannot_view_or_update_journal(): void
    {
        Sanctum::actingAs($this->otherTeacher);

        $this->getJson("/api/teacher/journals/{$this->journal->id}")
            ->assertStatus(403);

        $this->putJson("/api/teacher/journals/{$this->journal->id}", [
            'teaching_material' => 'Percobaan ubah ilegal',
            'notes' => '-',
        ])->assertStatus(403);
    }

    public function test_update_validates_required_teaching_material(): void
    {
        Sanctum::actingAs($this->teacher);

        $this->putJson("/api/teacher/journals/{$this->journal->id}", [
            'teaching_material' => '',
            'notes' => 'Kosong',
        ])->assertStatus(422);
    }
}
