<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rombel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Spatie\Permission\Models\Permission;

class RombelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if (Permission::where('name', 'ViewAny:Rombel')->exists()) {
            return $user->can('ViewAny:Rombel');
        }

        return true;
    }

    public function view(User $user, Rombel $rombel): bool
    {
        if (Permission::where('name', 'View:Rombel')->exists()) {
            return $user->can('View:Rombel');
        }

        return true;
    }

    public function create(User $user): bool
    {
        if (Permission::where('name', 'Create:Rombel')->exists()) {
            return $user->can('Create:Rombel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function update(User $user, Rombel $rombel): bool
    {
        if (Permission::where('name', 'Update:Rombel')->exists()) {
            return $user->can('Update:Rombel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function delete(User $user, Rombel $rombel): bool
    {
        if (Permission::where('name', 'Delete:Rombel')->exists()) {
            return $user->can('Delete:Rombel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function deleteAny(User $user): bool
    {
        if (Permission::where('name', 'DeleteAny:Rombel')->exists()) {
            return $user->can('DeleteAny:Rombel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }
}
