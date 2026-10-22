<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolSession;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Permission;

class SchoolSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        if (Permission::where('name', 'ViewAny:SchoolSession')->exists()) {
            return $authUser->can('ViewAny:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function view(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        if (Permission::where('name', 'View:SchoolSession')->exists()) {
            return $authUser->can('View:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function create(AuthUser $authUser): bool
    {
        if (Permission::where('name', 'Create:SchoolSession')->exists()) {
            return $authUser->can('Create:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function update(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        if (Permission::where('name', 'Update:SchoolSession')->exists()) {
            return $authUser->can('Update:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function delete(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        if (Permission::where('name', 'Delete:SchoolSession')->exists()) {
            return $authUser->can('Delete:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        if (Permission::where('name', 'DeleteAny:SchoolSession')->exists()) {
            return $authUser->can('DeleteAny:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin'
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->role?->name === 'Waka Kurikulum';
    }

    public function restore(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        if (Permission::where('name', 'Restore:SchoolSession')->exists()) {
            return $authUser->can('Restore:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }

    public function forceDelete(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        if (Permission::where('name', 'ForceDelete:SchoolSession')->exists()) {
            return $authUser->can('ForceDelete:SchoolSession');
        }

        return $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }
}
