<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Major;
use App\Models\User;
use App\Support\PermissionHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class MajorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if (PermissionHelper::exists('ViewAny:Major')) {
            return $user->can('ViewAny:Major');
        }

        return true;
    }

    public function view(User $user, Major $major): bool
    {
        if (PermissionHelper::exists('View:Major')) {
            return $user->can('View:Major');
        }

        return true;
    }

    public function create(User $user): bool
    {
        if (PermissionHelper::exists('Create:Major')) {
            return $user->can('Create:Major');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function update(User $user, Major $major): bool
    {
        if (PermissionHelper::exists('Update:Major')) {
            return $user->can('Update:Major');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function delete(User $user, Major $major): bool
    {
        if (PermissionHelper::exists('Delete:Major')) {
            return $user->can('Delete:Major');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function deleteAny(User $user): bool
    {
        if (PermissionHelper::exists('DeleteAny:Major')) {
            return $user->can('DeleteAny:Major');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }
}
