<?php

declare(strict_types=1);

namespace App\Support;

use Spatie\Permission\PermissionRegistrar;

class PermissionHelper
{
    /**
     * @var array<string, int>|null
     */
    protected static ?array $permissions = null;

    public static function clearCache(): void
    {
        static::$permissions = null;
    }

    /**
     * Determine if a permission exists in the system without hitting the database repeatedly.
     */
    public static function exists(string $permission): bool
    {
        if (static::$permissions === null) {
            static::$permissions = app(PermissionRegistrar::class)
                ->getPermissions()
                ->pluck('name')
                ->flip()
                ->toArray();
        }

        return isset(static::$permissions[$permission]);
    }
}
