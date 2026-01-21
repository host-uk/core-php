<?php

declare(strict_types=1);

namespace Core\Mod\Api;

use Core\Events\ApiRoutesRegistering;
use Core\Events\ConsoleBooting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * API Module Boot.
 *
 * This module provides shared API controllers and middleware.
 * Routes are registered centrally in routes/api.php rather than
 * per-module, as API endpoints span multiple service modules.
 */
class Boot extends ServiceProvider
{
    /**
     * The module name.
     */
    protected string $moduleName = 'api';

    /**
     * Events this module listens to for lazy loading.
     *
     * @var array<class-string, string>
     */
    public static array $listens = [
        ApiRoutesRegistering::class => 'onApiRoutes',
        ConsoleBooting::class => 'onConsole',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config.php',
            $this->moduleName
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/Migrations');
    }

    // -------------------------------------------------------------------------
    // Event-driven handlers
    // -------------------------------------------------------------------------

    public function onApiRoutes(ApiRoutesRegistering $event): void
    {
        // Middleware aliases registered via event
        $event->middleware('api.auth', Middleware\AuthenticateApiKey::class);
        $event->middleware('api.scope', Middleware\CheckApiScope::class);
        $event->middleware('api.rate', Middleware\RateLimitApi::class);
        $event->middleware('auth.api', Middleware\AuthenticateApiKey::class);

        // Core API routes (SEO, Pixel, Entitlements, MCP)
        if (file_exists(__DIR__.'/Routes/api.php')) {
            $event->routes(fn () => Route::middleware('api')->group(__DIR__.'/Routes/api.php'));
        }
    }

    public function onConsole(ConsoleBooting $event): void
    {
        // Register middleware aliases for CLI context (artisan route:list etc)
        $event->middleware('api.auth', Middleware\AuthenticateApiKey::class);
        $event->middleware('api.scope', Middleware\CheckApiScope::class);
        $event->middleware('api.rate', Middleware\RateLimitApi::class);
        $event->middleware('auth.api', Middleware\AuthenticateApiKey::class);
    }
}
