<?php

declare(strict_types=1);

namespace Core\Media;

use Illuminate\Support\ServiceProvider;

/**
 * Media Module Service Provider.
 *
 * Provides media processing functionality:
 * - Image optimization and compression
 * - Media conversions (thumbnails, resizing)
 */
class Boot extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register configuration
        $this->mergeConfigFrom(__DIR__.'/config.php', 'images');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
