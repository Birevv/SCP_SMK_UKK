<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClassLevel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Permission;

class ClassLevelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if (Permission::where('name', 'ViewAny:ClassLevel')->exists()) {
            return $user->can('ViewAny:ClassLevel');
        }

        return true;
    }

    public function view(User $user, ClassLevel $classLevel): bool
    {
        if (Permission::where('name', 'View:ClassLevel')->exists()) {
            return $user->can('View:ClassLevel');
        }

        return true;
    }

    public function create(User $user): bool
    {
        if (Permission::where('name', 'Create:ClassLevel')->exists()) {
            return $user->can('Create:ClassLevel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function update(User $user, ClassLevel $classLevel): bool
    {
        if (Permission::where('name', 'Update:ClassLevel')->exists()) {
            return $user->can('Update:ClassLevel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function delete(User $user, ClassLevel $classLevel): bool
    {
        if (Permission::where('name', 'Delete:ClassLevel')->exists()) {
            return $user->can('Delete:ClassLevel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function deleteAny(User $user): bool
    {
        if (Permission::where('name', 'DeleteAny:ClassLevel')->exists()) {
            return $user->can('DeleteAny:ClassLevel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }
}
