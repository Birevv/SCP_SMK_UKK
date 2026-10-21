<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Major;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MajorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Major $major): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Waka Kurikulum');
    }

    public function update(User $user, Major $major): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Waka Kurikulum');
    }

    public function delete(User $user, Major $major): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Waka Kurikulum');
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('Super Admin') || $user->hasRole('Waka Kurikulum');
    }
}
