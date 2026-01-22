<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Headers;

use Illuminate\Support\ServiceProvider;

/**
 * Headers Module Service Provider.
 *
 * Provides HTTP header parsing and security header functionality:
 * - Device detection (User-Agent parsing)
 * - GeoIP lookups (from headers or database)
 * - Configurable security headers (CSP, Permissions-Policy, etc.)
 */
class Boot extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config.php', 'headers');

        $this->app->singleton(DetectDevice::class);
        $this->app->singleton(DetectLocation::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
