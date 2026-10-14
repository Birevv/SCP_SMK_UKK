<?php

namespace Tests\Feature;

use App\Filament\Widgets\HomeroomStatsWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\StudentAttendanceWidget;
use App\Filament\Widgets\SystemConfigWidget;
use App\Filament\Widgets\TeacherStatsWidget;
use App\Filament\Widgets\UnfilledJournalAlertWidget;
use App\Filament\Widgets\WakaStatsWidget;
use App\Models\Role;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertTrue(UnfilledJournalAlertWidget::canView());

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
        $this->assertTrue(UnfilledJournalAlertWidget::canView());

        $this->assertFalse(StatsOverviewWidget::canView());
        $this->assertFalse(WakaStatsWidget::canView());
        $this->assertFalse(HomeroomStatsWidget::canView());
        $this->assertFalse(StudentAttendanceWidget::canView());
    }

    public function test_wali_kelas_dashboard_widgets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Wali Kelas');
        $this->actingAs($user);

        $this->assertTrue(HomeroomStatsWidget::canView());

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

        // BK dan Wali Kelas sama-sama Tim Kesiswaan, menggunakan HomeroomStatsWidget
        $this->assertTrue(HomeroomStatsWidget::canView());

        $this->assertFalse(StatsOverviewWidget::canView());
        $this->assertFalse(WakaStatsWidget::canView());
        $this->assertFalse(TeacherStatsWidget::canView());
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
}
