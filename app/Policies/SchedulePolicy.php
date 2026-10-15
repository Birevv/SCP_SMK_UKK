<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Schedule;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SchedulePolicy
{
    use HandlesAuthorization;

    public function before(AuthUser $authUser, string $ability): ?bool
    {
        if ($authUser->hasRole('Siswa') || $authUser->role?->name === 'Siswa') {
            return false;
        }

        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Schedule')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum', 'Guru'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum', 'Guru']);
    }

    public function view(AuthUser $authUser, Schedule $schedule): bool
    {
        return $authUser->can('View:Schedule')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum', 'Guru'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum', 'Guru']);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Schedule')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum', 'Guru'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum', 'Guru']);
    }

    public function update(AuthUser $authUser, Schedule $schedule): bool
    {
        return $authUser->can('Update:Schedule')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum', 'Guru'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum', 'Guru']);
    }

    public function delete(AuthUser $authUser, Schedule $schedule): bool
    {
        return $authUser->can('Delete:Schedule')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum', 'Guru'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum', 'Guru']);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Schedule')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public function restore(AuthUser $authUser, Schedule $schedule): bool
    {
        return $authUser->can('Restore:Schedule')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public function forceDelete(AuthUser $authUser, Schedule $schedule): bool
    {
        return $authUser->can('ForceDelete:Schedule')
            || $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Schedule')
            || $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Schedule')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public function replicate(AuthUser $authUser, Schedule $schedule): bool
    {
        return $authUser->can('Replicate:Schedule')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum', 'Guru'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum', 'Guru']);
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Schedule')
            || $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }
}
