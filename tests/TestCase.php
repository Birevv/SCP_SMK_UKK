<?php

namespace Tests;

use App\Models\SystemConfiguration;
use App\Support\PermissionHelper;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        SystemConfiguration::clearCache();
        PermissionHelper::clearCache();

        parent::tearDown();
    }
}
