<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use App\Rules\NoScheduleConflict;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ScheduleConflictTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_prevents_overlapping_room_schedule(): void
    {
        $teacher1 = User::factory()->create();
        $teacher2 = User::factory()->create();
        $classroom1 = Classroom::factory()->create();
        $classroom2 = Classroom::factory()->create();
        $room = Room::factory()->create();
        $academicYear = AcademicYear::create(['name' => '2025/2026', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Matematika']);

        Schedule::create([
            'academic_year_id' => $academicYear->id,
            'teacher_id' => $teacher1->id,
            'classroom_id' => $classroom1->id,
            'room_id' => $room->id,
            'subject_id' => $subject->id,
            'day_of_week' => 'Senin',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'cycle_type' => 'Putaran 1',
        ]);

        $rule = new NoScheduleConflict(
            dayOfWeek: 'Senin',
            startTime: '09:00:00',
            endTime: '11:00:00',
            cycleType: 'Putaran 1',
            classroomId: $classroom2->id,
            roomId: $room->id,
            teacherId: $teacher2->id
        );

        $failCalled = false;
        $rule->validate('subject_id', $subject->id, function ($message) use (&$failCalled) {
            $failCalled = true;
        });

        $this->assertTrue($failCalled, 'Validasi bentrok ruangan gagal memblokir jadwal beririsan.');
    }
}
