<?php

declare(strict_types=1);

namespace App\Support;

class ImportHelper
{
    /**
     * Dispatch an import job to run immediately (synchronously).
     * This avoids any dependency on background queue workers, supervisor,
     * or custom server queue configurations.
     */
    public static function dispatch(object $job): void
    {
        dispatch_sync($job);
    }
}
