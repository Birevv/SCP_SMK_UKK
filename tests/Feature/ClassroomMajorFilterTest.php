<?php

namespace Tests\Feature;

use App\Filament\Resources\Schedules\Pages\ListSchedules;
use App\Filament\Widgets\HomeroomScheduleWidget;
use App\Filament\Widgets\TeacherScheduleWidget;
use App\Filament\Widgets\WakaScheduleWidget;
use App\Models\AcademicYear;
use App\Models\ClassLevel;
use App\Models\Classroom;
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
use Tests\TestCase;

class ClassroomMajorFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $waka;
    protected User $teacher;
    protected User $homeroom;
    protected Classroom $class10Rpl;
    protected Classroom $class11Rpl;
    protected Classroom $class12Rpl;
    protected Classroom $class10Akl;
    protected Schedule $schedule10Rpl;
    protected Schedule $schedule11Rpl;
    protected Schedule $schedule12Rpl;
    protected Schedule $schedule10Akl;

    protected function setUp(): void
    {
        parent::setUp();

        $wakaRole = Role::firstOrCreate(['name' => 'Waka Kurikulum']);
        $guruRole = Role::firstOrCreate(['name' => 'Guru']);
        $waliRole = Role::firstOrCreate(['name' => 'Wali Kelas']);

        $this->waka = User::factory()->create(['role_id' => $wakaRole->id]);
        $this->waka->syncRoles(['Waka Kurikulum']);

        $this->teacher = User::factory()->create(['role_id' => $guruRole->id]);
        $this->teacher->syncRoles(['Guru']);

        $this->homeroom = User::factory()->create(['role_id' => $waliRole->id]);
        $this->homeroom->syncRoles(['Wali Kelas']);

        SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        $ay = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $sub = Subject::create(['name' => 'Pemrograman']);
        $room = Room::create(['name' => 'Lab 1']);

        // Create Classrooms across grades 10, 11, 12 and majors RPL and AKL
        $this->class10Rpl = Classroom::create(['name' => 'X RPL 1']);
        $this->class11Rpl = Classroom::create(['name' => 'XI RPL 1']);
        $this->class12Rpl = Classroom::create(['name' => 'XII RPL 1']);
        $this->class10Akl = Classroom::create(['name' => 'X AKL 1']);

        $this->homeroom->classrooms()->attach($this->class12Rpl->id, ['status' => 'Aktif']);

        // Teacher teaches class 10, 11, and 12
        $this->schedule10Rpl = Schedule::create([
            'academic_year_id' => $ay->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->class10Rpl->id,
            'room_id' => $room->id,
            'subject_id' => $sub->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->schedule11Rpl = Schedule::create([
            'academic_year_id' => $ay->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->class11Rpl->id,
            'room_id' => $room->id,
            'subject_id' => $sub->id,
            'day_of_week' => 'Senin',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $this->schedule12Rpl = Schedule::create([
            'academic_year_id' => $ay->id,
            'teacher_id' => $this->teacher->id,
            'classroom_id' => $this->class12Rpl->id,
            'room_id' => $room->id,
            'subject_id' => $sub->id,
            'day_of_week' => 'Senin',
            'start_time' => '10:15:00',
            'end_time' => '11:45:00',
            'cycle_type' => 'Putaran 1',
        ]);

        // Another teacher teaches AKL
        $otherTeacher = User::factory()->create(['role_id' => $guruRole->id]);
        $this->schedule10Akl = Schedule::create([
            'academic_year_id' => $ay->id,
            'teacher_id' => $otherTeacher->id,
            'classroom_id' => $this->class10Akl->id,
            'room_id' => $room->id,
            'subject_id' => $sub->id,
            'day_of_week' => 'Senin',
            'start_time' => '07:00:00',
            'end_time' => '08:30:00',
            'cycle_type' => 'Putaran 1',
        ]);
    }

    public function test_schedule_scopes_for_grade_and_major(): void
    {
        // Test scope for Grade 10 / X
        $grade10 = Schedule::forGrade('10')->get();
        $this->assertCount(2, $grade10); // 10 RPL and 10 AKL
        $this->assertTrue($grade10->contains($this->schedule10Rpl));
        $this->assertTrue($grade10->contains($this->schedule10Akl));
        $this->assertFalse($grade10->contains($this->schedule11Rpl));
        $this->assertFalse($grade10->contains($this->schedule12Rpl));

        // Test scope for Grade 11 / XI
        $grade11 = Schedule::forGrade('XI')->get();
        $this->assertCount(1, $grade11);
        $this->assertTrue($grade11->contains($this->schedule11Rpl));

        // Test scope for Grade 12 / XII
        $grade12 = Schedule::forGrade('XII')->get();
        $this->assertCount(1, $grade12);
        $this->assertTrue($grade12->contains($this->schedule12Rpl));

        // Test scope for Major RPL
        $rplSchedules = Schedule::forMajor('RPL')->get();
        $this->assertCount(3, $rplSchedules);
        $this->assertFalse($rplSchedules->contains($this->schedule10Akl));

        // Test scope for Major AKL
        $aklSchedules = Schedule::forMajor('AKL')->get();
        $this->assertCount(1, $aklSchedules);
        $this->assertTrue($aklSchedules->contains($this->schedule10Akl));
    }

    public function test_teacher_can_filter_schedules_by_grade_via_api(): void
    {
        // Teacher fetches only grade 10 schedules
        $response10 = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/teacher/schedules?grade=10');

        $response10->assertOk()
            ->assertJsonPath('data.schedules.Senin.0.classroom_name', 'X RPL 1');
        $this->assertCount(1, $response10->json('data.schedules.Senin'));

        // Teacher fetches only grade 12 schedules
        $response12 = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/teacher/schedules?grade=12');

        $response12->assertOk()
            ->assertJsonPath('data.schedules.Senin.0.classroom_name', 'XII RPL 1');
        $this->assertCount(1, $response12->json('data.schedules.Senin'));
    }

    public function test_teacher_can_filter_dashboard_by_grade_via_api(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 08:00:00')); // Monday (Senin)

        $response = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/teacher/dashboard?grade=XI');

        $response->assertOk()
            ->assertJsonPath('data.today_schedules.0.classroom_name', 'XI RPL 1');
        $this->assertCount(1, $response->json('data.today_schedules'));

        Carbon::setTestNow();
    }

    public function test_waka_schedule_widget_filters_by_major_and_grade(): void
    {
        $this->actingAs($this->waka);

        // Filter by RPL
        Livewire::test(WakaScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->set('selectedCycle', 'Putaran 1')
            ->call('setMajor', 'RPL')
            ->assertSee('X RPL 1')
            ->assertSee('XI RPL 1')
            ->assertSee('XII RPL 1')
            ->assertDontSee('X AKL 1');

        // Filter by AKL
        Livewire::test(WakaScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->set('selectedCycle', 'Putaran 1')
            ->call('setMajor', 'AKL')
            ->assertSee('X AKL 1')
            ->assertDontSee('XII RPL 1');

        // Filter by Grade 12
        Livewire::test(WakaScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->set('selectedCycle', 'Putaran 1')
            ->call('setMajor', 'ALL')
            ->call('setGrade', 'XII')
            ->assertSee('XII RPL 1')
            ->assertDontSee('X RPL 1')
            ->assertDontSee('XI RPL 1');
    }

    public function test_teacher_schedule_widget_filters_by_grade(): void
    {
        $this->actingAs($this->teacher);

        // Filter by Grade X
        Livewire::test(TeacherScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->call('setGrade', 'X')
            ->assertSee('X RPL 1')
            ->assertDontSee('XI RPL 1')
            ->assertDontSee('XII RPL 1');

        // Filter by Grade XI
        Livewire::test(TeacherScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->call('setGrade', 'XI')
            ->assertSee('XI RPL 1')
            ->assertDontSee('X RPL 1')
            ->assertDontSee('XII RPL 1');

        // Filter by Grade XII
        Livewire::test(TeacherScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->call('setGrade', 'XII')
            ->assertSee('XII RPL 1')
            ->assertDontSee('X RPL 1')
            ->assertDontSee('XI RPL 1');
    }

    public function test_homeroom_schedule_widget_displays_and_filters(): void
    {
        $this->actingAs($this->homeroom);

        Livewire::test(HomeroomScheduleWidget::class)
            ->set('selectedDay', 'Senin')
            ->assertSee('XII RPL 1')
            ->assertDontSee('X AKL 1');
    }
}
