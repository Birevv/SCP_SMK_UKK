<?php

namespace Tests\Feature;

use App\Filament\Resources\Journals\JournalResource;
use App\Filament\Widgets\TeacherScheduleWidget;
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
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TeacherScheduleWidgetFilledViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_schedule_widget_renders_successfully_when_journal_is_filled(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $guruRole = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $teacher = User::factory()->create(['role_id' => $guruRole->id]);
        $teacher->assignRole('Guru');

        Permission::firstOrCreate(['name' => 'ViewAny:Journal', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'View:Journal', 'guard_name' => 'web']);
        $teacher->givePermissionTo(['ViewAny:Journal', 'View:Journal']);

        AcademicYear::firstOrCreate([
            'name' => '2026/2027',
            'is_active' => true,
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
        ]);

        SystemConfiguration::firstOrCreate([], [
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        $level10 = ClassLevel::firstOrCreate(['level' => 10, 'name' => 'X']);
        $majorRpl = Major::firstOrCreate(['code' => 'RPL', 'name' => 'Rekayasa Perangkat Lunak']);
        $rombel1 = Rombel::firstOrCreate(['name' => '1', 'number' => 1]);
        $classroom = Classroom::create(['name' => 'X RPL 1', 'class_level_id' => $level10->id, 'major_id' => $majorRpl->id, 'rombel_id' => $rombel1->id]);

        $subject = Subject::create(['name' => 'Pemrograman Web', 'code' => 'PW']);
        $room = Room::create(['name' => 'Lab Komputer 1', 'code' => 'LAB-1']);

        $now = Carbon::now();
        $dayName = match ($now->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            default => 'Senin',
        };

        $schedule = Schedule::create([
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'room_id' => $room->id,
            'day_of_week' => $dayName,
            'start_time' => '07:15',
            'end_time' => '08:35',
            'start_session' => 1,
            'end_session' => 2,
            'cycle_type' => 'Putaran 1',
        ]);

        // Buat jurnal yang sudah terisi
        $journal = Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'date' => $now->toDateString(),
            'teaching_material' => 'Pengenalan HTML dan CSS Dasar',
            'notes' => 'Siswa mengikuti dengan baik',
        ]);

        // Test render widget: sebelumnya memicu RouteNotFoundException untuk route filament.admin.resources.journals.view
        Livewire::actingAs($teacher)
            ->test(TeacherScheduleWidget::class)
            ->set('selectedDay', $dayName)
            ->assertSuccessful()
            ->assertSee('Sudah Diisi')
            ->assertSee('Lihat Jurnal')
            ->assertSee(JournalResource::getUrl('view', ['record' => $journal->id]));

        // Test akses ke view page jurnal
        $response = $this->actingAs($teacher)->get(JournalResource::getUrl('view', ['record' => $journal->id]));
        $response->assertSuccessful();
    }
}
