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
}
