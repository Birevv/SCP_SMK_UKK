<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolSession;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SchoolSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->hasRole('Super Admin')
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->can('ViewAny:SchoolSession');
    }

    public function view(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        return $authUser->hasRole('Super Admin')
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->can('View:SchoolSession');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->hasRole('Super Admin')
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->can('Create:SchoolSession');
    }

    public function update(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        return $authUser->hasRole('Super Admin')
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->can('Update:SchoolSession');
    }

    public function delete(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        return $authUser->hasRole('Super Admin')
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->can('Delete:SchoolSession');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->hasRole('Super Admin')
            || $authUser->hasRole('Waka Kurikulum')
            || $authUser->can('DeleteAny:SchoolSession');
    }

    public function restore(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        return $authUser->hasRole('Super Admin')
            || $authUser->can('Restore:SchoolSession');
    }

    public function forceDelete(AuthUser $authUser, SchoolSession $schoolSession): bool
    {
        return $authUser->hasRole('Super Admin')
            || $authUser->can('ForceDelete:SchoolSession');
    }
}
