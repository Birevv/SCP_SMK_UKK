<?php

namespace Tests\Feature;

use App\Filament\Widgets\WakaScheduleWidget;
use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
use App\Models\Major;
use App\Models\Role;
use App\Models\Rombel;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolSession;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WakaScheduleWidgetDynamicSessionTest extends TestCase
{
    use RefreshDatabase;

    protected User $wakaUser;

    protected AcademicYear $academicYear;

    protected Classroom $classroom;

    protected Subject $subject;

    protected Room $room;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Waka Kurikulum', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);

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

        $this->wakaUser = User::factory()->create([
            'role_id' => Role::where('name', 'Waka Kurikulum')->first()->id,
        ]);
        $this->wakaUser->assignRole('Waka Kurikulum');

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
            'name' => 'Pemrograman Web',
            'code' => 'PW',
        ]);

        $this->room = Room::create([
            'name' => 'Lab 1',
        ]);
    }

    public function test_waka_schedule_widget_dynamically_reflects_school_session_changes(): void
    {
        // 1. Seed konfigurasi sesi default (Senin: Jam 1: 07:15 - 07:55, Jam 3: 08:35 - 09:15)
        SchoolSession::seedDefaults();

        // 2. Buat jadwal sesi 1 s/d sesi 3 (awal: 07:15 - 09:15)
        $schedule = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->wakaUser->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 1,
            'end_session' => 3,
            'start_time' => '07:15:00',
            'end_time' => '09:15:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->actingAs($this->wakaUser);

        // Widget harus menampilkan 07:15 - 09:15
        $component = Livewire::test(WakaScheduleWidget::class)
            ->set('selectedDay', 'Senin');

        $component->assertSuccessful();
        $component->assertSee('07:15 - 09:15');
        $component->assertSee('Jam ke 1 - 3');

        // 3. Sekarang ubah Konfigurasi Jam Sesi Senin (misal jam masuk digeser ke 08:00 dan jam ke-3 selesai 09:40)
        $session1 = SchoolSession::where('day_of_week', 'Senin')->where('session_number', 1)->first();
        $session1->update([
            'start_time' => '08:00',
            'end_time' => '08:40',
        ]);

        $session3 = SchoolSession::where('day_of_week', 'Senin')->where('session_number', 3)->first();
        $session3->update([
            'start_time' => '09:00',
            'end_time' => '09:40',
        ]);

        // 4. Pastikan Schedule di database otomatis tersinkronisasi via observer
        $schedule->refresh();
        $this->assertEquals('08:00:00', $schedule->start_time);
        $this->assertEquals('09:40:00', $schedule->end_time);

        // 5. Pastikan Widget di dashboard waka otomatis berubah secara dinamis
        $component2 = Livewire::test(WakaScheduleWidget::class)
            ->set('selectedDay', 'Senin');

        $component2->assertSuccessful();
        $component2->assertSee('08:00 - 09:40');
        $component2->assertDontSee('07:15 - 09:15');
    }

    public function test_schedule_sync_method_syncs_all_matching_schedules(): void
    {
        SchoolSession::seedDefaults();

        $schedule = Schedule::create([
            'academic_year_id' => $this->academicYear->id,
            'teacher_id' => $this->wakaUser->id,
            'classroom_id' => $this->classroom->id,
            'room_id' => $this->room->id,
            'subject_id' => $this->subject->id,
            'day_of_week' => 'Senin',
            'start_session' => 4,
            'end_session' => 6,
            'start_time' => '00:00:00', // waktu usang
            'end_time' => '00:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Default Senin sesi 4 adalah 09:30, sesi 6 selesai 11:30
        Schedule::syncTimesFromSchoolSessions('Senin');

        $schedule->refresh();
        $this->assertEquals('09:30:00', $schedule->start_time);
        $this->assertEquals('11:30:00', $schedule->end_time);
    }

    public function test_generate_sessions_for_day_supports_dot_and_single_digit_formats(): void
    {
        // Format dengan titik (07.15)
        SchoolSession::generateSessionsForDay(
            dayOfWeek: 'Senin',
            durationMinutes: 40,
            firstStartTime: '07.15',
            totalSessions: 5
        );

        $session1 = SchoolSession::where('day_of_week', 'Senin')->where('session_number', 1)->first();
        $this->assertEquals('07:15', substr((string) $session1->start_time, 0, 5));
        $this->assertEquals('07:55', substr((string) $session1->end_time, 0, 5));

        // Format single digit (8:00)
        SchoolSession::generateSessionsForDay(
            dayOfWeek: 'Selasa',
            durationMinutes: 35,
            firstStartTime: '8:00',
            totalSessions: 3
        );

        $selasa1 = SchoolSession::where('day_of_week', 'Selasa')->where('session_number', 1)->first();
        $this->assertEquals('08:00', substr((string) $selasa1->start_time, 0, 5));
        $this->assertEquals('08:35', substr((string) $selasa1->end_time, 0, 5));
    }
}
