<?php

declare(strict_types=1);

namespace Core\Headers;

use Illuminate\Support\ServiceProvider;

/**
 * Headers Module Service Provider.
 *
 * Provides HTTP header parsing functionality:
 * - Device detection (User-Agent parsing)
 * - GeoIP lookups (from headers or database)
 */
class Boot extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
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
