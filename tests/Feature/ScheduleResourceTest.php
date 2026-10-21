<?php

namespace Tests\Feature;

use App\Filament\Resources\Schedules\ScheduleResource;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_schedule_resource_can_be_instantiated()
    {
        $this->assertEquals(Schedule::class, ScheduleResource::getModel());
    }

    /** @test */
    public function test_schedule_sessions_default_to_10_sessions()
    {
        $sessions = \App\Filament\Resources\Schedules\Schemas\ScheduleForm::getSessions();
        $this->assertCount(10, $sessions);
        $this->assertEquals('07:15', $sessions[1]['start']);
        $this->assertEquals('07:55', $sessions[1]['end']);
        $this->assertEquals('09:15', $sessions[3]['end']); // Sebelum Istirahat 1 (09:15 - 09:30)
        $this->assertEquals('09:30', $sessions[4]['start']); // Setelah Istirahat 1
        $this->assertEquals('11:30', $sessions[6]['end']); // Sebelum Istirahat 2 (11:30 - 12:30)
        $this->assertEquals('12:30', $sessions[7]['start']); // Setelah Istirahat 2
        $this->assertEquals('15:10', $sessions[10]['end']); // Berakhir pukul 15:10

        $startOptions = \App\Filament\Resources\Schedules\Schemas\ScheduleForm::getStartTimeOptions(dayOfWeek: 'Senin');
        $this->assertCount(10, $startOptions);
        $this->assertArrayHasKey('14:30', $startOptions); // Jam ke-10 start
    }

    /** @test */
    public function test_schedule_sessions_on_friday_limited_to_8_sessions()
    {
        $jumatSessions = \App\Filament\Resources\Schedules\Schemas\ScheduleForm::getSessions('Jumat');
        $this->assertCount(8, $jumatSessions);
        $this->assertEquals('07:15', $jumatSessions[1]['start']);
        $this->assertEquals('09:15', $jumatSessions[3]['end']); // Sebelum Istirahat 1 (09:15 - 09:30)
        $this->assertEquals('09:30', $jumatSessions[4]['start']); // Setelah Istirahat 1
        $this->assertEquals('11:25', $jumatSessions[6]['end']); // Sebelum Istirahat 2 (11:25 - 12:40)
        $this->assertEquals('12:40', $jumatSessions[7]['start']); // Setelah Istirahat 2
        $this->assertEquals('13:50', $jumatSessions[8]['end']); // Berakhir pukul 13:50
        $this->assertArrayNotHasKey(9, $jumatSessions);
        $this->assertArrayNotHasKey(10, $jumatSessions);

        $startOptions = \App\Filament\Resources\Schedules\Schemas\ScheduleForm::getStartTimeOptions(dayOfWeek: 'Jumat');
        $this->assertCount(8, $startOptions);
        $this->assertArrayHasKey('13:15', $startOptions); // Jam ke-8 start
        $this->assertArrayNotHasKey('13:50', $startOptions);
        $this->assertArrayNotHasKey('14:30', $startOptions);

        $endOptions = \App\Filament\Resources\Schedules\Schemas\ScheduleForm::getEndTimeOptions(dayOfWeek: 'Jumat');
        $this->assertCount(8, $endOptions);
        $this->assertArrayHasKey('13:50', $endOptions); // Jam ke-8 end
        $this->assertArrayNotHasKey('14:30', $endOptions);
        $this->assertArrayNotHasKey('15:10', $endOptions);
    }
}
