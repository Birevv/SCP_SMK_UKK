<?php

namespace Tests\Feature;

use App\Filament\Widgets\UnfilledJournalAlertWidget;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Journal;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\SystemConfiguration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UnfilledJournalAlertTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_displays_alert_when_schedule_ended_more_than_30_minutes_ago_without_journal(): void
    {
        // 1. Setup Master Data
        $wakaRole = Role::create(['name' => 'Waka Kurikulum']);
        $guruRole = Role::create(['name' => 'Guru']);

        $waka = User::factory()->create(['role_id' => $wakaRole->id]);
        $teacher = User::factory()->create(['role_id' => $guruRole->id]);

        $classroom = Classroom::factory()->create();
        $room = Room::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Pemrograman Web']);

        SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        $today = match (Carbon::now()->dayOfWeekIso) {
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        };

        // Buat jadwal yang sudah selesai 1 jam yang lalu (> 30 menit yang lalu)
        $pastStartTime = Carbon::now()->subHours(3)->format('H:i:s');
        $pastEndTime = Carbon::now()->subMinutes(45)->format('H:i:s');

        $schedule = Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher->id,
            'classroom_id' => $classroom->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => $today,
            'start_time' => $pastStartTime,
            'end_time' => $pastEndTime,
            'cycle_type' => 'Putaran 1',
        ]);

        // 2. Test: Guru login dan melihat widget -> Jadwal harus muncul di alert
        $this->actingAs($teacher);

        Livewire::test(UnfilledJournalAlertWidget::class)
            ->assertSee('Peringatan: Jurnal Mengajar Belum Diisi!')
            ->assertSee($subject->name);

        // 3. Test: Guru mengisi jurnal hari ini -> Peringatan harus hilang
        Journal::create([
            'schedule_id' => $schedule->id,
            'teacher_id' => $teacher->id,
            'date' => Carbon::today()->toDateString(),
            'teaching_material' => 'Materi Pemrograman Selesai Diisi',
        ]);

        Livewire::test(UnfilledJournalAlertWidget::class)
            ->assertDontSee('Peringatan: Jurnal Mengajar Belum Diisi!');
    }
}
