<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ClassLevel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClassLevelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ClassLevel $classLevel): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Waka Kurikulum');
    }

    public function update(User $user, ClassLevel $classLevel): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Waka Kurikulum');
    }

    public function delete(User $user, ClassLevel $classLevel): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Waka Kurikulum');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Waka Kurikulum');
    }
}
