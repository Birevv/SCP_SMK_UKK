<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:User')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public function view(AuthUser $authUser, ?User $user = null): bool
    {
        return $authUser->can('View:User')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:User')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public function update(AuthUser $authUser, ?User $user = null): bool
    {
        if ($user && ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin')) {
            return $authUser->hasRole('Super Admin') || $authUser->role?->name === 'Super Admin';
        }

        return $authUser->can('Update:User')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public function delete(AuthUser $authUser, ?User $user = null): bool
    {
        if ($user && ($user->hasRole('Super Admin') || $user->role?->name === 'Super Admin')) {
            return false;
        }

        return $authUser->can('Delete:User')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:User')
            || $authUser->hasAnyRole(['Super Admin', 'Waka Kurikulum'])
            || in_array($authUser->role?->name, ['Super Admin', 'Waka Kurikulum']);
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:User');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:User');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:User');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:User');
    }
}
