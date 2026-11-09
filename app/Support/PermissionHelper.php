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
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Determine if a permission exists in the system without hitting the database repeatedly.
     */
    public static function exists(string $permission): bool
    {
        return app(PermissionRegistrar::class)
            ->getPermissions()
            ->contains('name', $permission);
    }
}
