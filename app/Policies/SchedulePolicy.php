<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SchedulePolicy
{
    private function isLocked(): bool
    {
        return (bool) SystemConfiguration::first()->is_schedule_locked;
    }

    public function create(User $user): bool
    {
        return !$this->isLocked();
    }
    public function update(User $user, Schedule $schedule): bool
    {
        return !$this->isLocked();
    }
    public function delete(User $user, Schedule $schedule): bool
    {
        return !$this->isLocked();
    }
}
