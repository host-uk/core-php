<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Automatically load migrations from packages.
     */
    protected function defineDatabaseMigrations(): void
    {
        // Load core-php migrations
        $this->loadMigrationsFrom(__DIR__.'/../packages/core-php/src/Mod/Tenant/Migrations');
        $this->loadMigrationsFrom(__DIR__.'/../packages/core-php/src/Mod/Social/Migrations');
    }
}
