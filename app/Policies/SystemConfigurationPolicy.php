<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SystemConfiguration;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class SystemConfigurationPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SystemConfiguration');
    }

    public function view(AuthUser $authUser, SystemConfiguration $systemConfiguration): bool
    {
        return $authUser->can('View:SystemConfiguration');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SystemConfiguration');
    }

    public function update(AuthUser $authUser, SystemConfiguration $systemConfiguration): bool
    {
        return $authUser->can('Update:SystemConfiguration');
    }

    public function delete(AuthUser $authUser, SystemConfiguration $systemConfiguration): bool
    {
        return $authUser->can('Delete:SystemConfiguration');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SystemConfiguration');
    }

    public function restore(AuthUser $authUser, SystemConfiguration $systemConfiguration): bool
    {
        return $authUser->can('Restore:SystemConfiguration');
    }

    public function forceDelete(AuthUser $authUser, SystemConfiguration $systemConfiguration): bool
    {
        return $authUser->can('ForceDelete:SystemConfiguration');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SystemConfiguration');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SystemConfiguration');
    }

    public function replicate(AuthUser $authUser, SystemConfiguration $systemConfiguration): bool
    {
        return $authUser->can('Replicate:SystemConfiguration');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SystemConfiguration');
    }
}
