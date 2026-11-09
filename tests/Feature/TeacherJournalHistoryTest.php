<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\JournalHistory;
use App\Filament\Pages\Widgets\JournalHistoryOverview;
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
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeacherJournalHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacher1;

    protected User $teacher2;

    protected User $student;

    protected Classroom $classroom;

    protected Subject $subject;

    protected Schedule $schedule1;

    protected Schedule $schedule2;

    protected function setUp(): void
    {
        parent::setUp();

        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $siswaRole = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);

        $journalPerm = Permission::firstOrCreate(['name' => 'View:JournalHistory', 'guard_name' => 'web']);
        $guruRole->givePermissionTo($journalPerm);

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

        $this->teacher1 = User::factory()->create([
            'role_id' => $guruRole->id,
            'full_name' => 'Budi Santoso, S.Kom',
        ]);
        $this->teacher1->assignRole('Guru');

        $this->teacher2 = User::factory()->create([
            'role_id' => $guruRole->id,
            'full_name' => 'Siti Aminah, M.Pd',
        ]);
        $this->teacher2->assignRole('Guru');

        $this->student = User::factory()->create([
            'role_id' => $siswaRole->id,
            'full_name' => 'Ahmad Fauzan',
        ]);
        $this->student->assignRole('Siswa');

        $level = ClassLevel::create(['level' => 12, 'name' => 'XII']);
        $major = Major::create(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $rombel = Rombel::create(['name' => '1', 'number' => 1]);

        $this->classroom = Classroom::create([
            'name' => 'XII RPL 1',
            'class_level_id' => $level->id,
            'major_id' => $major->id,
            'rombel_id' => $rombel->id,
        ]);

        $this->subject = Subject::create([
            'name' => 'Pemrograman Web Lanjut',
            'code' => 'PWL',
        ]);

        $room = Room::create(['name' => 'Lab RPL 1']);

        $this->schedule1 = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $this->teacher1->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->schedule2 = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $this->teacher2->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Selasa',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_teacher_can_access_journal_history_page(): void
    {
        $this->actingAs($this->teacher1);

        $response = $this->get(JournalHistory::getUrl());
        $response->assertOk();
        $response->assertSee('Riwayat Isi Jurnal Mengajar');
    }

    public function test_student_cannot_access_journal_history_page(): void
    {
        $this->actingAs($this->student);

        $response = $this->get(JournalHistory::getUrl());
        $response->assertForbidden();
    }

    public function test_journal_history_table_only_shows_authenticated_teacher_journals(): void
    {
        // Journal milik Guru 1
        $journal1 = Journal::create([
            'schedule_id' => $this->schedule1->id,
            'teacher_id' => $this->teacher1->id,
            'date' => Carbon::parse('2026-11-02')->toDateString(),
            'teaching_material' => 'Materi Guru 1: Belajar Vue.js',
        ]);

        Attendance::create([
            'journal_id' => $journal1->id,
            'student_id' => $this->student->id,
            'status' => 'Hadir',
            'date' => $journal1->date,
        ]);

        // Journal milik Guru 2
        Journal::create([
            'schedule_id' => $this->schedule2->id,
            'teacher_id' => $this->teacher2->id,
            'date' => Carbon::parse('2026-11-03')->toDateString(),
            'teaching_material' => 'Materi Guru 2: Belajar React.js',
        ]);

        $this->actingAs($this->teacher1);

        Livewire::test(JournalHistory::class)
            ->assertCanSeeTableRecords([$journal1])
            ->assertSee('Belajar Vue.js')
            ->assertDontSee('Belajar React.js')
            ->assertSee('XII RPL 1')
            ->assertSee('Pemrograman Web Lanjut');
    }

    public function test_journal_history_stats_calculate_accurately(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-09 10:00:00'));

        $journal = Journal::create([
            'schedule_id' => $this->schedule1->id,
            'teacher_id' => $this->teacher1->id,
            'date' => '2026-11-09',
            'teaching_material' => 'Statistik Testing Materi',
        ]);

        Attendance::create([
            'journal_id' => $journal->id,
            'student_id' => $this->student->id,
            'status' => 'Hadir',
            'date' => $journal->date,
        ]);

        $this->actingAs($this->teacher1);

        $component = Livewire::test(JournalHistory::class);
        $stats = $component->instance()->getStats();

        $this->assertEquals(1, $stats['totalJournals']);
        $this->assertEquals(1, $stats['thisMonthJournals']);
        $this->assertEquals(1, $stats['uniqueClassesCount']);

        $widget = Livewire::test(JournalHistoryOverview::class);
        $widget->assertSee('Total Jurnal Terisi')
            ->assertSee('Jurnal Bulan Ini')
            ->assertSee('Kelas Diajar')
            ->assertDontSee('Rata-rata Kehadiran');

        Carbon::setTestNow();
    }

    public function test_waka_cannot_access_journal_history_page_by_default(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        $waka = User::factory()->create(['role_id' => $wakaRole->id]);
        $waka->assignRole('Waka Kurikulum');

        $this->actingAs($waka);

        $this->assertFalse(JournalHistory::canAccess());
        $response = $this->get(JournalHistory::getUrl());
        $response->assertForbidden();
    }

    public function test_super_admin_cannot_access_journal_history_page_when_permission_inactive(): void
    {
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $admin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $admin->assignRole('Super Admin');

        $this->actingAs($admin);

        $this->assertFalse(JournalHistory::canAccess());
        $response = $this->get(JournalHistory::getUrl());
        $response->assertForbidden();
    }

    public function test_super_admin_can_access_journal_history_page_when_permission_is_granted(): void
    {
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $superAdminRole->givePermissionTo('View:JournalHistory');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $admin->assignRole('Super Admin');

        $this->actingAs($admin);

        $this->assertTrue(JournalHistory::canAccess());
        $response = $this->get(JournalHistory::getUrl());
        $response->assertOk();
    }

    public function test_waka_can_access_journal_history_page_when_permission_is_granted(): void
    {
        $wakaRole = Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        $wakaRole->givePermissionTo('View:JournalHistory');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $waka = User::factory()->create(['role_id' => $wakaRole->id]);
        $waka->assignRole('Waka Kurikulum');

        $this->actingAs($waka);

        $this->assertTrue(JournalHistory::canAccess());
        $response = $this->get(JournalHistory::getUrl());
        $response->assertOk();
    }

    public function test_guru_cannot_access_journal_history_page_when_permission_revoked(): void
    {
        $guruRole = Role::where('name', 'Guru')->first();
        $guruRole->revokePermissionTo('View:JournalHistory');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($this->teacher1);

        $this->assertFalse(JournalHistory::canAccess());
        $response = $this->get(JournalHistory::getUrl());
        $response->assertForbidden();
    }
}
