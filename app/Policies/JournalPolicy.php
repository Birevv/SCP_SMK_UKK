<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Journal;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class JournalPolicy
{
    use HandlesAuthorization;

    public function before(AuthUser $authUser, string $ability): ?bool
    {
        return null;
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Journal');
    }

    public function view(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('View:Journal');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Journal');
    }

    public function update(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('Update:Journal');
    }

    public function delete(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('Delete:Journal');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Journal')
            || $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }

    public function restore(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('Restore:Journal')
            || $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }

    public function forceDelete(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('ForceDelete:Journal')
            || $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Journal')
            || $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Journal')
            || $authUser->hasRole('Super Admin')
            || $authUser->role?->name === 'Super Admin';
    }

    public function replicate(AuthUser $authUser, Journal $journal): bool
    {
        return $authUser->can('Replicate:Journal');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Journal');
    }
}
