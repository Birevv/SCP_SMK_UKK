<?php

namespace Tests\Feature;

use App\Filament\Resources\Schedules\Schemas\ScheduleForm;
use App\Models\Role;
use App\Models\SchoolSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolSessionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_default_sessions_have_40_minutes_and_correct_endpoints()
    {
        // 1. Regular day (Senin)
        $seninSessions = SchoolSession::getDefaultSessions('Senin');
        $this->assertCount(10, $seninSessions);
        $this->assertEquals('07:15', $seninSessions[1]['start']);
        $this->assertEquals('07:55', $seninSessions[1]['end']);
        $this->assertEquals(40, $seninSessions[1]['duration']);
        $this->assertEquals('15:30', $seninSessions[10]['end']);
        $this->assertEquals(40, $seninSessions[10]['duration']);

        // Check each regular session duration
        foreach ($seninSessions as $num => $session) {
            $diff = (strtotime("2000-01-01 {$session['end']}") - strtotime("2000-01-01 {$session['start']}")) / 60;
            $this->assertEquals(40, $diff, "Session {$num} duration should be 40 minutes");
        }

        // 2. Friday (Jumat)
        $jumatSessions = SchoolSession::getDefaultSessions('Jumat');
        $this->assertCount(8, $jumatSessions);
        $this->assertEquals('07:15', $jumatSessions[1]['start']);
        $this->assertEquals('07:55', $jumatSessions[1]['end']);
        $this->assertEquals(40, $jumatSessions[1]['duration']);
        $this->assertEquals('14:00', $jumatSessions[8]['end']);
        $this->assertEquals(40, $jumatSessions[8]['duration']);

        // Check each Friday session duration
        foreach ($jumatSessions as $num => $session) {
            $diff = (strtotime("2000-01-01 {$session['end']}") - strtotime("2000-01-01 {$session['start']}")) / 60;
            $this->assertEquals(40, $diff, "Friday Session {$num} duration should be 40 minutes");
        }
    }

    /** @test */
    public function test_school_sessions_can_be_seeded_to_database()
    {
        $this->assertEquals(0, SchoolSession::count());

        SchoolSession::seedDefaults();

        // 4 days x 10 sessions + 1 day (Jumat) x 8 sessions = 48 sessions
        $this->assertEquals(48, SchoolSession::count());

        $seninSessions = SchoolSession::getSessionsForDay('Senin');
        $this->assertCount(10, $seninSessions);
        $this->assertEquals('07:15', $seninSessions[1]['start']);
        $this->assertEquals('15:30', $seninSessions[10]['end']);

        $jumatSessions = SchoolSession::getSessionsForDay('Jumat');
        $this->assertCount(8, $jumatSessions);
        $this->assertEquals('07:15', $jumatSessions[1]['start']);
        $this->assertEquals('14:00', $jumatSessions[8]['end']);
    }

    /** @test */
    public function test_can_generate_custom_session_duration_automatically()
    {
        // Kasus: Bulan Ramadhan, durasi per sesi diubah menjadi 30 menit, jam masuk 07:30
        SchoolSession::generateSessionsForDay(
            dayOfWeek: 'Senin',
            durationMinutes: 30,
            firstStartTime: '07:30',
            totalSessions: 10,
            break1After: 3,
            break1Minutes: 15,
            break2After: 6,
            break2Minutes: 30,
            break3After: 8,
            break3Minutes: 10
        );

        $seninSessions = SchoolSession::getSessionsForDay('Senin');
        $this->assertCount(10, $seninSessions);

        // Jam ke-1: 07:30 - 08:00 (30 menit)
        $this->assertEquals('07:30', $seninSessions[1]['start']);
        $this->assertEquals('08:00', $seninSessions[1]['end']);
        $this->assertEquals(30, $seninSessions[1]['duration']);

        // ScheduleForm otomatis mengambil data 30 menit ini
        $scheduleFormSessions = ScheduleForm::getSessions('Senin');
        $this->assertEquals('07:30', $scheduleFormSessions[1]['start']);
        $this->assertEquals('08:00', $scheduleFormSessions[1]['end']);
    }

    /** @test */
    public function test_super_admin_and_waka_can_authorize_session_management()
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $wakaRole = Role::create(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        $guruRole = Role::create(['name' => 'Guru', 'guard_name' => 'web']);

        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $waka = User::factory()->create(['role_id' => $wakaRole->id]);
        $guru = User::factory()->create(['role_id' => $guruRole->id]);

        $policy = new \App\Policies\SchoolSessionPolicy();

        // Super Admin & Waka Kurikulum can manage
        $this->assertTrue($policy->viewAny($superAdmin));
        $this->assertTrue($policy->create($superAdmin));
        $this->assertTrue($policy->viewAny($waka));
        $this->assertTrue($policy->create($waka));

        // Guru cannot manage session configuration
        $this->assertFalse($policy->viewAny($guru));
        $this->assertFalse($policy->create($guru));
    }

    /** @test */
    public function test_admin_can_render_school_session_list_page()
    {
        $superAdminRole = Role::create(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin = User::factory()->create(['role_id' => $superAdminRole->id]);
        $superAdmin->assignRole('Super Admin');

        SchoolSession::seedDefaults();

        $response = $this->actingAs($superAdmin)->get('/admin/school-sessions');
        $response->assertOk();

        \Livewire\Livewire::actingAs($superAdmin)
            ->test(\App\Filament\Resources\SchoolSessions\Pages\ListSchoolSessions::class)
            ->assertSuccessful();
    }
}
