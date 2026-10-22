<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Major;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Permission;

class MajorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if (Permission::where('name', 'ViewAny:Major')->exists()) {
            return $user->can('ViewAny:Major');
        }

        return true;
    }

    public function view(User $user, Major $major): bool
    {
        if (Permission::where('name', 'View:Major')->exists()) {
            return $user->can('View:Major');
        }

        return true;
    }

    public function create(User $user): bool
    {
        if (Permission::where('name', 'Create:Major')->exists()) {
            return $user->can('Create:Major');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function update(User $user, Major $major): bool
    {
        if (Permission::where('name', 'Update:Major')->exists()) {
            return $user->can('Update:Major');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function delete(User $user, Major $major): bool
    {
        if (Permission::where('name', 'Delete:Major')->exists()) {
            return $user->can('Delete:Major');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function deleteAny(User $user): bool
    {
        if (Permission::where('name', 'DeleteAny:Major')->exists()) {
            return $user->can('DeleteAny:Major');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }
}
