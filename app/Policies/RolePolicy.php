<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Contracts\Role;

class RolePolicy
{
    /**
     * Hanya Super Admin yang berhak melihat menu dan data Role / Filament Shield.
     * Waka Kurikulum dan role lainnya memiliki fitur manajemen akademik tersendiri.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasRole('Super Admin');
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasRole('Super Admin');
    }
}
