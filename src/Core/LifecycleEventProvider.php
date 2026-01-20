<?php

declare(strict_types=1);

namespace Core;

use Core\Events\AdminPanelBooting;
use Core\Events\ApiRoutesRegistering;
use Core\Events\ClientRoutesRegistering;
use Core\Events\ConsoleBooting;
use Core\Events\McpToolsRegistering;
use Core\Events\WebRoutesRegistering;
use Illuminate\Support\Facades\Route;

/**
 * Fires lifecycle events and processes their requests.
 *
 * This class provides static methods for frontage service providers
 * to fire events and process the collected requests from modules.
 *
 * Usage:
 *     // In a web service provider:
 *     LifecycleEventProvider::fireWebRoutes();
 */
class LifecycleEventProvider
{
    /**
     * Fire WebRoutesRegistering and process requests.
     *
     * Called by your web frontage when web routes are being set up.
     */
    public static function fireWebRoutes(): void
    {
        $event = new WebRoutesRegistering;
        event($event);

        // Process view namespace requests
        foreach ($event->viewRequests() as [$namespace, $path]) {
            if (is_dir($path)) {
                view()->addNamespace($namespace, $path);
            }
        }

        // Process Livewire component requests if Livewire is available
        if (class_exists(\Livewire\Livewire::class)) {
            foreach ($event->livewireRequests() as [$alias, $class]) {
                \Livewire\Livewire::component($alias, $class);
            }
        }

        // Process route requests
        foreach ($event->routeRequests() as $callback) {
            Route::middleware('web')->group($callback);
        }

        // Refresh route lookups after adding routes
        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }

    /**
     * Fire AdminPanelBooting and process requests.
     *
     * Called by your admin frontage when admin routes are being set up.
     */
    public static function fireAdminBooting(): void
    {
        $event = new AdminPanelBooting;
        event($event);

        // Process view namespace requests
        foreach ($event->viewRequests() as [$namespace, $path]) {
            if (is_dir($path)) {
                view()->addNamespace($namespace, $path);
            }
        }

        // Process Livewire component requests if Livewire is available
        if (class_exists(\Livewire\Livewire::class)) {
            foreach ($event->livewireRequests() as [$alias, $class]) {
                \Livewire\Livewire::component($alias, $class);
            }
        }

        // Process route requests with admin middleware
        foreach ($event->routeRequests() as $callback) {
            Route::middleware('admin')->group($callback);
        }
    }

    /**
     * Fire ClientRoutesRegistering and process requests.
     *
     * Called by your client frontage when client routes are being set up.
     */
    public static function fireClientRoutes(): void
    {
        $event = new ClientRoutesRegistering;
        event($event);

        // Process view namespace requests
        foreach ($event->viewRequests() as [$namespace, $path]) {
            if (is_dir($path)) {
                view()->addNamespace($namespace, $path);
            }
        }

        // Process Livewire component requests if Livewire is available
        if (class_exists(\Livewire\Livewire::class)) {
            foreach ($event->livewireRequests() as [$alias, $class]) {
                \Livewire\Livewire::component($alias, $class);
            }
        }

        // Process route requests with client middleware
        foreach ($event->routeRequests() as $callback) {
            Route::middleware('client')->group($callback);
        }

        // Refresh route lookups after adding routes
        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }

    /**
     * Fire ApiRoutesRegistering and process requests.
     *
     * Called by your API frontage when API routes are being set up.
     */
    public static function fireApiRoutes(): void
    {
        $event = new ApiRoutesRegistering;
        event($event);

        // Process route requests with api middleware
        foreach ($event->routeRequests() as $callback) {
            Route::middleware('api')->prefix('api')->group($callback);
        }
    }

    /**
     * Fire McpToolsRegistering and return collected handlers.
     *
     * Called by MCP server command when loading tools.
     *
     * @return array<string> Handler class names
     */
    public static function fireMcpTools(): array
    {
        $event = new McpToolsRegistering;
        event($event);

        return $event->handlers();
    }

    /**
     * Fire ConsoleBooting and process requests.
     *
     * @param  \Illuminate\Support\ServiceProvider  $provider  The service provider to register commands on
     */
    public static function fireConsoleBooting(\Illuminate\Support\ServiceProvider $provider): void
    {
        $event = new ConsoleBooting;
        event($event);

        // Process command requests
        if (! empty($event->commandRequests())) {
            $provider->commands($event->commandRequests());
        }
    }
}
