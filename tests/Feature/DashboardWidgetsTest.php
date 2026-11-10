<?php

namespace Tests\Feature;

use App\Filament\Resources\Journals\Pages\ListJournals;
use App\Filament\Widgets\HomeroomScheduleWidget;
use App\Filament\Widgets\HomeroomStatsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\StudentAttendanceWidget;
use App\Filament\Widgets\SystemConfigWidget;
use App\Filament\Widgets\TeacherScheduleWidget;
use App\Filament\Widgets\TeacherStatsWidget;
use App\Filament\Widgets\UnfilledJournalAlertWidget;
use App\Filament\Widgets\WakaScheduleWidget;
use App\Filament\Widgets\WakaStatsWidget;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        foreach (['Super Admin', 'Waka Kurikulum', 'Guru', 'Wali Kelas', 'Guru BK', 'Siswa'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }

    public function test_super_admin_dashboard_widgets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $this->actingAs($user);

        $this->assertTrue(StatsOverviewWidget::canView());
        $this->assertTrue(SystemConfigWidget::canView());
        $this->assertTrue(UnfilledJournalAlertWidget::canView());

        $this->assertFalse(TeacherStatsWidget::canView());
        $this->assertFalse(HomeroomStatsWidget::canView());
        $this->assertFalse(StudentAttendanceWidget::canView());
    }

    public function test_waka_kurikulum_dashboard_widgets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Waka Kurikulum');
        $this->actingAs($user);

        $this->assertTrue(WakaStatsWidget::canView());
        $this->assertTrue(SystemConfigWidget::canView());
        $this->assertFalse(UnfilledJournalAlertWidget::canView());

        $this->assertFalse(StatsOverviewWidget::canView());
        $this->assertFalse(TeacherStatsWidget::canView());
        $this->assertFalse(HomeroomStatsWidget::canView());
        $this->assertFalse(StudentAttendanceWidget::canView());
    }

    public function test_guru_dashboard_widgets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Guru');
        $this->actingAs($user);

        $this->assertTrue(TeacherStatsWidget::canView());
        $this->assertTrue(TeacherScheduleWidget::canView());
        $this->assertTrue(UnfilledJournalAlertWidget::canView());

        $this->assertFalse(StatsOverviewWidget::canView());
        $this->assertFalse(WakaStatsWidget::canView());
        $this->assertFalse(HomeroomStatsWidget::canView());
        $this->assertFalse(HomeroomScheduleWidget::canView());
        $this->assertFalse(StudentAttendanceWidget::canView());
    }

    public function test_wali_kelas_dashboard_widgets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Wali Kelas');
        $this->actingAs($user);

        $this->assertTrue(HomeroomStatsWidget::canView());
        $this->assertTrue(HomeroomScheduleWidget::canView());
        $this->assertTrue(TeacherScheduleWidget::canView());

        $this->assertFalse(StatsOverviewWidget::canView());
        $this->assertFalse(WakaStatsWidget::canView());
        $this->assertFalse(TeacherStatsWidget::canView());
        $this->assertFalse(StudentAttendanceWidget::canView());
    }

    public function test_guru_bk_dashboard_widgets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Guru BK');
        $this->actingAs($user);

        // BK dan Wali Kelas sama-sama Tim Kesiswaan, menggunakan HomeroomStatsWidget dan HomeroomScheduleWidget
        $this->assertTrue(HomeroomStatsWidget::canView());
        $this->assertTrue(HomeroomScheduleWidget::canView());

        $this->assertFalse(StatsOverviewWidget::canView());
        $this->assertFalse(WakaStatsWidget::canView());
        $this->assertFalse(TeacherStatsWidget::canView());
        $this->assertFalse(TeacherScheduleWidget::canView());
        $this->assertFalse(StudentAttendanceWidget::canView());
    }

    public function test_siswa_dashboard_widgets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Siswa');
        $this->actingAs($user);

        $this->assertTrue(StudentAttendanceWidget::canView());

        $this->assertFalse(StatsOverviewWidget::canView());
        $this->assertFalse(WakaStatsWidget::canView());
        $this->assertFalse(TeacherStatsWidget::canView());
        $this->assertFalse(HomeroomStatsWidget::canView());
        $this->assertFalse(UnfilledJournalAlertWidget::canView());
    }

    public function test_unfilled_journal_alert_widget_respects_permissions_dynamically(): void
    {
        $perm = Permission::firstOrCreate([
            'name' => 'View:UnfilledJournalAlertWidget',
            'guard_name' => 'web',
        ]);

        $waka = User::factory()->create();
        $waka->assignRole('Waka Kurikulum');
        $this->actingAs($waka);

        // Without permission, Waka cannot view
        $this->assertFalse(UnfilledJournalAlertWidget::canView());

        // Grant permission via Super Admin action
        $wakaRole = Role::findByName('Waka Kurikulum', 'web');
        $wakaRole->givePermissionTo($perm);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $waka->load('roles.permissions');

        // With permission, Waka can view
        $this->assertTrue(UnfilledJournalAlertWidget::canView());

        // Revoke permission via Super Admin action
        $wakaRole->revokePermissionTo($perm);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $waka->load('roles.permissions');

        // Without permission again, Waka cannot view
        $this->assertFalse(UnfilledJournalAlertWidget::canView());
    }

    public function test_waka_schedule_widget_and_journal_table_filters_by_date_and_classroom(): void
    {
        $viewAnyJournalPerm = Permission::firstOrCreate([
            'name' => 'ViewAny:Journal',
            'guard_name' => 'web',
        ]);
        $wakaRole = Role::findByName('Waka Kurikulum', 'web');
        $wakaRole->givePermissionTo($viewAnyJournalPerm);

        $waka = User::factory()->create();
        $waka->assignRole('Waka Kurikulum');
        $this->actingAs($waka);

        $teacher = User::factory()->create(['full_name' => 'Budi Santoso']);
        $teacher->assignRole('Guru');

        $classA = Classroom::factory()->create(['name' => 'XII RPL 1']);
        $classB = Classroom::factory()->create(['name' => 'XI TKJ 1']);
        $room = Room::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2026/2027', 'is_active' => true]);
        $subjectA = Subject::create(['name' => 'Pemrograman Web']);
        $subjectB = Subject::create(['name' => 'Jaringan Dasar']);

        $scheduleA = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classA->id,
            'room_id' => $room->id,
            'subject_id' => $subjectA->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '09:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $scheduleB = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classB->id,
            'room_id' => $room->id,
            'subject_id' => $subjectB->id,
            'day_of_week' => 'Senin',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $journalA = Journal::create([
            'schedule_id' => $scheduleA->id,
            'teacher_id' => $teacher->id,
            'date' => '2026-11-02', // Monday
            'teaching_material' => 'Materi Laravel Filter',
        ]);

        $journalB = Journal::create([
            'schedule_id' => $scheduleB->id,
            'teacher_id' => $teacher->id,
            'date' => '2026-11-03', // Tuesday
            'teaching_material' => 'Materi Mikrotik Dasar',
        ]);

        // 1. Test WakaScheduleWidget filters (date, classroom, status)
        Livewire::test(WakaScheduleWidget::class)
            ->set('selectedDate', '2026-11-02')
            ->assertSet('selectedDay', 'Senin')
            ->assertSee('XII RPL 1')
            ->assertSee('XI TKJ 1')
            ->set('selectedClassroom', (string) $classA->id)
            ->assertSee('Pemrograman Web')
            ->assertDontSee('Jaringan Dasar')
            ->set('selectedClassroom', 'ALL')
            ->set('selectedStatus', 'filled')
            ->assertSee('Pemrograman Web')
            ->assertDontSee('Jaringan Dasar')
            ->call('resetFilters')
            ->assertSet('selectedClassroom', 'ALL')
            ->assertSet('selectedStatus', 'ALL');

        // 2. Test ListJournals table filters (date, classroom)
        Livewire::test(ListJournals::class)
            ->assertCanSeeTableRecords([$journalA, $journalB])
            ->filterTable('classroom_id', $classA->id)
            ->assertCanSeeTableRecords([$journalA])
            ->assertCanNotSeeTableRecords([$journalB])
            ->resetTableFilters()
            ->filterTable('date', ['date' => '2026-11-03'])
            ->assertCanSeeTableRecords([$journalB])
            ->assertCanNotSeeTableRecords([$journalA]);
    }
}
