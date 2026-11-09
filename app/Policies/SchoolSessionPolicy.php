<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolSession;
use App\Support\PermissionHelper;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SchoolSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        if (PermissionHelper::exists('ViewAny:SchoolSession')) {
            return $authUser->can('ViewAny:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function view(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        if (PermissionHelper::exists('View:SchoolSession')) {
            return $authUser->can('View:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function create(AuthUser $authUser): bool
    {
        if (PermissionHelper::exists('Create:SchoolSession')) {
            return $authUser->can('Create:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function update(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        if (PermissionHelper::exists('Update:SchoolSession')) {
            return $authUser->can('Update:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function delete(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        if (PermissionHelper::exists('Delete:SchoolSession')) {
            return $authUser->can('Delete:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        if (PermissionHelper::exists('DeleteAny:SchoolSession')) {
            return $authUser->can('DeleteAny:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function restore(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        if (PermissionHelper::exists('Restore:SchoolSession')) {
            return $authUser->can('Restore:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }

    public function forceDelete(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        if (PermissionHelper::exists('ForceDelete:SchoolSession')) {
            return $authUser->can('ForceDelete:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }
}
