<?php

namespace Tests;

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The SystemSetting value cache is a static array — clear it so settings
        // written by one test don't leak into the next.
        SystemSetting::flushCache();
    }
}
