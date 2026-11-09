<?php

declare(strict_types=1);

namespace App\Support;

class ImportHelper
{
    /**
     * Dispatch an import job to run in the background.
     * When the queue driver is 'sync', afterResponse() ensures that the modal
     * closes and the response returns immediately to the client before processing.
     */
    public static function dispatch(object $job): void
    {
        if (config('queue.default') === 'sync') {
            dispatch($job)->afterResponse();
        } else {
            dispatch($job);
        }
    }
}
