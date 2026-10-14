<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;

class SchedulePolicy
{
    /**
     * Cek apakah jadwal sedang dikunci.
     */
    private function isLocked(): bool
    {
        return SystemConfiguration::current()->is_schedule_locked;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->role?->name === 'Super Admin';
    }

    private function isGuru(User $user): bool
    {
        return $user->hasRole('Guru') || $user->role?->name === 'Guru';
    }

    /**
     * Hanya Guru dan Super Admin yang bisa melihat jadwal. Waka tidak mengelola jadwal.
     */
    public function viewAny(User $user): bool
    {
        return $this->isSuperAdmin($user) || $this->isGuru($user);
    }

    public function view(User $user, Schedule $schedule): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->isGuru($user) && $schedule->teacher_id === $user->id;
    }

    /**
     * Guru tidak bisa membuat jadwal jika locked.
     * Super Admin selalu bisa membuat jadwal.
     */
    public function create(User $user): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->isGuru($user) && !$this->isLocked();
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->isGuru($user) 
            && $schedule->teacher_id === $user->id
            && !$this->isLocked();
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        if ($this->isSuperAdmin($user)) {
            return true;
        }

        return $this->isGuru($user) 
            && $schedule->teacher_id === $user->id 
            && !$this->isLocked();
    }
}
