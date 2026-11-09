<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClassLevel;
use App\Models\User;
use App\Support\PermissionHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClassLevelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if (PermissionHelper::exists('ViewAny:ClassLevel')) {
            return $user->can('ViewAny:ClassLevel');
        }

        return true;
    }

    public function view(User $user, ClassLevel $classLevel): bool
    {
        if (PermissionHelper::exists('View:ClassLevel')) {
            return $user->can('View:ClassLevel');
        }

        return true;
    }

    public function create(User $user): bool
    {
        if (PermissionHelper::exists('Create:ClassLevel')) {
            return $user->can('Create:ClassLevel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function update(User $user, ClassLevel $classLevel): bool
    {
        if (PermissionHelper::exists('Update:ClassLevel')) {
            return $user->can('Update:ClassLevel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function delete(User $user, ClassLevel $classLevel): bool
    {
        if (PermissionHelper::exists('Delete:ClassLevel')) {
            return $user->can('Delete:ClassLevel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function deleteAny(User $user): bool
    {
        if (PermissionHelper::exists('DeleteAny:ClassLevel')) {
            return $user->can('DeleteAny:ClassLevel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }
}
