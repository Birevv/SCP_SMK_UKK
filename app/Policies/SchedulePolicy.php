<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Schedule;
use App\Models\SystemConfiguration;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SchedulePolicy
{
    use HandlesAuthorization;

    public function before(AuthUser $authUser, string $ability): ?bool
    {
        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Schedule');
    }

    public function view(AuthUser $authUser, Schedule $schedule): bool
    {
        return $authUser->can('View:Schedule');
    }

    public function create(AuthUser $authUser): bool
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            return false;
        }

        return $authUser->can('Create:Schedule');
    }

    public function update(AuthUser $authUser, Schedule $schedule): bool
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            return false;
        }

        if (! $authUser->can('Update:Schedule')) {
            return false;
        }

        // Guru hanya dapat mengedit jadwal miliknya sendiri
        $isSchoolAdmin = $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';

        if (! $isSchoolAdmin && ($authUser->hasRole('Guru') || $authUser->role?->name === 'Guru')) {
            return $schedule->teacher_id === $authUser->id;
        }

        return true;
    }

    public function delete(AuthUser $authUser, Schedule $schedule): bool
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            return false;
        }

        if (! $authUser->can('Delete:Schedule')) {
            return false;
        }

        // Guru hanya dapat menghapus jadwal miliknya sendiri
        $isSchoolAdmin = $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';

        if (! $isSchoolAdmin && ($authUser->hasRole('Guru') || $authUser->role?->name === 'Guru')) {
            return $schedule->teacher_id === $authUser->id;
        }

        return true;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            return false;
        }

        return $authUser->can('DeleteAny:Schedule');
    }

    public function restore(AuthUser $authUser, Schedule $schedule): bool
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            return false;
        }

        return $authUser->can('Restore:Schedule');
    }

    public function forceDelete(AuthUser $authUser, Schedule $schedule): bool
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            return false;
        }

        return $authUser->can('ForceDelete:Schedule');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            return false;
        }

        return $authUser->can('ForceDeleteAny:Schedule');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            return false;
        }

        return $authUser->can('RestoreAny:Schedule');
    }

    public function replicate(AuthUser $authUser, Schedule $schedule): bool
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            return false;
        }

        return $authUser->can('Replicate:Schedule');
    }

    public function reorder(AuthUser $authUser): bool
    {
        if (SystemConfiguration::current()->is_schedule_locked) {
            return false;
        }

        return $authUser->can('Reorder:Schedule');
    }
}
