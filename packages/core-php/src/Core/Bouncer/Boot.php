<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Bouncer;

use Illuminate\Support\ServiceProvider;

/**
 * Core Bouncer - Early-exit middleware for security and SEO.
 *
 * Two responsibilities:
 * 1. Block bad actors (honeypot critical hits) before wasting CPU
 * 2. Handle SEO redirects before Laravel routing
 */
class Boot extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BlocklistService::class);
        $this->app->singleton(RedirectService::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
    }
}
