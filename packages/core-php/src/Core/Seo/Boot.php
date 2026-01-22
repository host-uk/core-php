<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Seo;

use Core\Seo\Services\SchemaBuilderService;
use Core\Seo\Services\ServiceOgImageService;
use Illuminate\Support\ServiceProvider;

/**
 * SEO Module Service Provider.
 *
 * Provides SEO-related functionality:
 * - Schema.org structured data generation (Schema, SchemaBuilderService)
 * - Open Graph image generation
 */
class Boot extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register services as singletons
        $this->app->singleton(Schema::class);
        $this->app->singleton(SchemaBuilderService::class);
        $this->app->singleton(ServiceOgImageService::class);

        // Register backward compatibility aliases
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
     * Register backward compatibility class aliases.
     *
     * These allow existing code using old namespaces to continue working
     * while we migrate to the new Core structure.
     */
    protected function registerBackwardCompatAliases(): void
    {
        // Schema (high-level JSON-LD generator)
        if (! class_exists(\App\Services\Content\SchemaService::class)) {
            class_alias(Schema::class, \App\Services\Content\SchemaService::class);
        }

        // Services
        if (! class_exists(\App\Services\SchemaBuilderService::class)) {
            class_alias(SchemaBuilderService::class, \App\Services\SchemaBuilderService::class);
        }

        if (! class_exists(\App\Services\ServiceOgImageService::class)) {
            class_alias(ServiceOgImageService::class, \App\Services\ServiceOgImageService::class);
        }
    }
}
