<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rombel;
use App\Models\User;
use App\Support\PermissionHelper;
use Illuminate\Auth\Access\HandlesAuthorization;

class RombelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        if (PermissionHelper::exists('ViewAny:Rombel')) {
            return $user->can('ViewAny:Rombel');
        }

        return true;
    }

    public function view(User $user, Rombel $rombel): bool
    {
        if (PermissionHelper::exists('View:Rombel')) {
            return $user->can('View:Rombel');
        }

        return true;
    }

    public function create(User $user): bool
    {
        if (PermissionHelper::exists('Create:Rombel')) {
            return $user->can('Create:Rombel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function update(User $user, Rombel $rombel): bool
    {
        if (PermissionHelper::exists('Update:Rombel')) {
            return $user->can('Update:Rombel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function delete(User $user, Rombel $rombel): bool
    {
        if (PermissionHelper::exists('Delete:Rombel')) {
            return $user->can('Delete:Rombel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }

    public function deleteAny(User $user): bool
    {
        if (PermissionHelper::exists('DeleteAny:Rombel')) {
            return $user->can('DeleteAny:Rombel');
        }

        return $user->hasRole('Super Admin')
            || $user->role?->name === 'Super Admin'
            || $user->hasRole('Waka Kurikulum')
            || $user->role?->name === 'Waka Kurikulum';
    }
}
