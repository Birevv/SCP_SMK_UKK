<?php

namespace Tests\Feature;

use App\Filament\Resources\Journals\Pages\CreateJournal;
use App\Filament\Resources\Journals\Pages\EditJournal;
use App\Models\AcademicYear;
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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JournalTodayScheduleFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher;
    protected AcademicYear $academicYear;
    protected Classroom $classroom;
    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        foreach (['ViewAny:Journal', 'View:Journal', 'Create:Journal', 'Update:Journal'] as $permName) {
            $p = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            $guruRole->givePermissionTo($p);
        }

        $this->academicYear = AcademicYear::create([
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
            'full_name' => 'Guru Pengajar, S.Kom',
        ]);
        $this->teacher->assignRole('Guru');

        $level = ClassLevel::create(['level' => 10, 'name' => 'X']);
        $major = Major::create(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $rombel = Rombel::create(['name' => '2', 'number' => 2]);

        $this->classroom = Classroom::create([
            'name' => 'X RPL 2',
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel->id,
        ]);

        $this->room = Room::create(['name' => 'Lab RPL']);
    }

    public function test_create_journal_only_shows_today_schedules(): void
    {
        // Misalkan hari ini adalah Rabu, 11 November 2026
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));

        $subSenin = Subject::create(['name' => 'DDPK 4 RPL Dasar Web dan jaringan Komputer', 'code' => 'DDPK4']);
        $subSelasa = Subject::create(['name' => 'Kreatifitas, Inovasi dan kewirausahaan', 'code' => 'KIK']);
        $subRabu = Subject::create(['name' => 'Pemrograman Web Lanjut', 'code' => 'PWL']);

        // Jadwal Senin (sebelum hari ini)
        $schedSenin = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $subSenin->id,
            'day_of_week' => 'Senin',
            'start_time' => '10:35:00',
            'end_time' => '15:10:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Jadwal Selasa (sebelum hari ini)
        $schedSelasa = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $subSelasa->id,
            'day_of_week' => 'Selasa',
            'start_time' => '11:05:00',
            'end_time' => '15:10:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Jadwal Rabu (hari ini)
        $schedRabu = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $subRabu->id,
            'day_of_week' => 'Rabu',
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Render form CreateJournal
        $component = Livewire::actingAs($this->teacher)
            ->test(CreateJournal::class)
            ->assertSuccessful();

        // Verifikasi bahwa opsi yang dapat dipilih HANYA jadwal hari Rabu
        $component->assertSchemaStateSet([
            'date' => '2026-11-11',
        ]);

        // Jadwal hari ini (Rabu) dapat diisi
        $component->fillForm([
            'schedule_id' => $schedRabu->id,
            'date' => '2026-11-11',
            'teaching_material' => 'Materi Hari Rabu',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

        $this->assertDatabaseHas('journals', [
            'schedule_id' => $schedRabu->id,
            'teaching_material' => 'Materi Hari Rabu',
        ]);
    }

    public function test_cannot_save_journal_with_mismatched_day_schedule(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00')); // Rabu

        $subSenin = Subject::create(['name' => 'DDPK 4 RPL', 'code' => 'DDPK4']);
        $schedSenin = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $subSenin->id,
            'day_of_week' => 'Senin',
            'start_time' => '10:35:00',
            'end_time' => '15:10:00',
            'cycle_type' => 'Putaran 1',
        ]);

        Livewire::actingAs($this->teacher)
            ->test(CreateJournal::class)
            ->fillForm([
                'schedule_id' => $schedSenin->id,
                'date' => '2026-11-11', // Rabu
                'teaching_material' => 'Materi Tidak Sesuai Hari',
            ])
            ->call('create')
            ->assertHasFormErrors(['schedule_id']);
    }

    public function test_edit_journal_keeps_existing_schedule_loaded(): void
    {
        // Journal dibuat hari Senin
        $subSenin = Subject::create(['name' => 'DDPK 4 RPL', 'code' => 'DDPK4']);
        $schedSenin = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $subSenin->id,
            'day_of_week' => 'Senin',
            'start_time' => '10:35:00',
            'end_time' => '15:10:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $journal = Journal::create([
            'schedule_id' => $schedSenin->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-09', // Senin
            'teaching_material' => 'Materi Senin',
        ]);

        // Dibuka saat hari Rabu
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00'));

        Livewire::actingAs($this->teacher)
            ->test(EditJournal::class, ['record' => $journal->id])
            ->assertSuccessful()
            ->assertSchemaStateSet([
                'schedule_id' => $schedSenin->id,
            ]);
    }

    public function test_schedule_already_filled_today_cannot_be_filled_again(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00')); // Rabu

        $subRabu = Subject::create(['name' => 'Bimbingan dan Konseling', 'code' => 'BK']);
        $schedRabu = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $subRabu->id,
            'day_of_week' => 'Rabu',
            'start_time' => '09:30:00',
            'end_time' => '10:50:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Jurnal pertama sudah terisi hari ini
        Journal::create([
            'schedule_id' => $schedRabu->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-11',
            'teaching_material' => 'Materi Pertemuan 1',
        ]);

        // Percobaan kedua untuk membuat jurnal dengan jadwal yang sama pada hari yang sama harus ditolak
        Livewire::actingAs($this->teacher)
            ->test(CreateJournal::class)
            ->fillForm([
                'schedule_id' => $schedRabu->id,
                'date' => '2026-11-11',
                'teaching_material' => 'Materi Pertemuan Duplikat',
            ])
            ->call('create')
            ->assertHasFormErrors(['schedule_id']);

        // Pastikan hanya ada 1 record di database
        $this->assertEquals(1, Journal::where('schedule_id', $schedRabu->id)->whereDate('date', '2026-11-11')->count());
    }

    public function test_api_rejects_duplicate_journal_for_same_schedule_on_same_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 10:00:00')); // Rabu

        $subRabu = Subject::create(['name' => 'Pemrograman Berorientasi Objek', 'code' => 'PBO']);
        $schedRabu = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $subRabu->id,
            'day_of_week' => 'Rabu',
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        Journal::create([
            'schedule_id' => $schedRabu->id,
            'teacher_id' => $this->teacher->id,
            'date' => '2026-11-11',
            'teaching_material' => 'Materi PBO Sesi 1',
        ]);

        $response = $this->actingAs($this->teacher, 'sanctum')->postJson('/api/teacher/journals', [
            'schedule_id' => $schedRabu->id,
            'date' => '2026-11-11',
            'teaching_material' => 'Materi PBO Duplikat',
            'notes' => 'Catatan',
            'attendances' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['schedule_id']);
    }
}
