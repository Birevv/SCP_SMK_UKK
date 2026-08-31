<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use App\Models\User;

class SchedulePolicy
{
    /**
     * Cek apakah jadwal sedang dikunci oleh Waka Kurikulum.
     */
    private function isLocked(): bool
    {
        return SystemConfiguration::current()->is_schedule_locked;
    }

    /**
     * Hanya Guru dan Waka Kurikulum yang bisa melihat jadwal.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Waka Kurikulum', 'Guru']);
    }

    public function view(User $user, Schedule $schedule): bool
    {
        if ($user->role?->name === 'Waka Kurikulum') {
            return true;
        }

        return $user->role?->name === 'Guru' && $schedule->teacher->id === $user->id;
    }

    /**
     * Guru tidak bisa membuat jadwal jika locked.
     * Waka Kurikulum selalu bisa membuat jadwal.
     */
    public function create(User $user): bool
    {
        if ($user->role?->name === 'Waka Kurikulum') {
            return true;
        }

        return $user->role?->name === 'Guru' && !$this->isLocked();
    }

    public function update(User $user, Schedule $schedule): bool
    {
        if ($user->role?->name === 'Waka Kurikulum') {
            return true;
        }

        return $user->role?->name === 'Guru' 
            && $schedule->teacher->id === $user->id
            && !$this->isLocked();
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        if ($user->role?->name === 'Waka Kurikulum') {
            return true;
        }

        return $user->role?->name === 'Guru' 
            && $schedule->teacher->id === $user->id 
            && !$this->isLocked();
    }
}
