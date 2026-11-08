<?php

namespace App\Observers;

use App\Models\Schedule;
use App\Models\SchoolSession;

class SchoolSessionObserver
{
    /**
     * Handle the SchoolSession "saved" event.
     */
    public function saved(SchoolSession $schoolSession): void
    {
        Schedule::syncTimesFromSchoolSessions($schoolSession->day_of_week);
    }

    /**
     * Handle the SchoolSession "deleted" event.
     */
    public function deleted(SchoolSession $schoolSession): void
    {
        Schedule::syncTimesFromSchoolSessions($schoolSession->day_of_week);
    }
}
