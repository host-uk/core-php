<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Search;

use Illuminate\Support\ServiceProvider;

/**
 * Search module service provider.
 *
 * Unified search across all system components.
 */
class Boot extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(Unified::class);

        $this->registerBackwardCompatAliases();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register backward compatibility aliases for old namespaces.
     */
    protected function registerBackwardCompatAliases(): void
    {
        if (! class_exists(\App\Services\Search\UnifiedSearchService::class)) {
            class_alias(Unified::class, \App\Services\Search\UnifiedSearchService::class);
        }
    }
}
