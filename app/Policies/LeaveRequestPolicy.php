<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeaveRequest;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class LeaveRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:LeaveRequest');
    }

    public function view(AuthUser $authUser, LeaveRequest $leaveRequest): bool
    {
        return $authUser->can('View:LeaveRequest');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:LeaveRequest');
    }

    public function update(AuthUser $authUser, LeaveRequest $leaveRequest): bool
    {
        return $authUser->can('Update:LeaveRequest');
    }

    public function delete(AuthUser $authUser, LeaveRequest $leaveRequest): bool
    {
        /** @var \App\Models\User $authUser */
        if ($authUser->hasRole('Super Admin') || $authUser->role?->name === 'Super Admin') {
            return true;
        }

        // Siswa bisa menghapus ijin yang dibuat sendiri
        if ($authUser->hasRole('Siswa') || $authUser->role?->name === 'Siswa') {
            return $leaveRequest->student_id === $authUser->id || $leaveRequest->submitted_by === $authUser->id;
        }

        return $authUser->can('Delete:LeaveRequest');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        /** @var \App\Models\User $authUser */
        if ($authUser->hasRole('Super Admin') || $authUser->role?->name === 'Super Admin') {
            return true;
        }

        if ($authUser->hasRole('Siswa') || $authUser->role?->name === 'Siswa') {
            return true;
        }

        return $authUser->can('DeleteAny:LeaveRequest');
    }

    public function restore(AuthUser $authUser, LeaveRequest $leaveRequest): bool
    {
        return $authUser->can('Restore:LeaveRequest');
    }

    public function forceDelete(AuthUser $authUser, LeaveRequest $leaveRequest): bool
    {
        return $authUser->can('ForceDelete:LeaveRequest');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:LeaveRequest');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:LeaveRequest');
    }

    public function replicate(AuthUser $authUser, LeaveRequest $leaveRequest): bool
    {
        return $authUser->can('Replicate:LeaveRequest');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:LeaveRequest');
    }
}
