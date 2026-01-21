<?php

declare(strict_types=1);

namespace Core;

use Core\Events\AdminPanelBooting;
use Core\Events\ApiRoutesRegistering;
use Core\Events\ClientRoutesRegistering;
use Core\Events\ConsoleBooting;
use Core\Events\FrameworkBooted;
use Core\Events\McpToolsRegistering;
use Core\Events\QueueWorkerBooting;
use Core\Events\WebRoutesRegistering;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

/**
 * Manages lifecycle events for lazy module loading.
 *
 * This provider:
 * 1. Scans modules for $listens declarations during register()
 * 2. Wires up lazy listeners for each event-module pair
 * 3. Fires lifecycle events at appropriate times during boot()
 *
 * Modules declare interest via static $listens arrays:
 *
 *     public static array $listens = [
 *         WebRoutesRegistering::class => 'onWebRoutes',
 *         AdminPanelBooting::class => 'onAdmin',
 *     ];
 *
 * The module is only instantiated when its events fire.
 */
class LifecycleEventProvider extends ServiceProvider
{
    /**
     * Directories to scan for modules with $listens declarations.
     */
    protected array $scanPaths = [];

    public function register(): void
    {
        // Register infrastructure
        $this->app->singleton(ModuleScanner::class);
        $this->app->singleton(ModuleRegistry::class, function ($app) {
            return new ModuleRegistry($app->make(ModuleScanner::class));
        });

        // Scan and wire lazy listeners
        // Website modules are included - they use DomainResolving event to self-register
        $this->scanPaths = [
            app_path('Core'),
            app_path('Mod'),
            app_path('Website'),
        ];

        $registry = $this->app->make(ModuleRegistry::class);
        $registry->register($this->scanPaths);
    }

    public function boot(): void
    {
        // Console event now fired by Core\Front\Cli\Boot

        // Fire queue worker event for queue context
        if ($this->app->bound('queue.worker')) {
            $this->fireQueueWorkerBooting();
        }

        // Framework booted event fires after all providers have booted
        $this->app->booted(function () {
            event(new FrameworkBooted);
        });
    }

    /**
     * Fire WebRoutesRegistering and process requests.
     *
     * Called by Front/Web/Boot when web middleware is being set up.
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

        // Process Livewire component requests
        foreach ($event->livewireRequests() as [$alias, $class]) {
            if (class_exists(Livewire::class)) {
                Livewire::component($alias, $class);
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
     * Called by Front/Admin/Boot when admin routes are being set up.
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

        // Process translation requests
        foreach ($event->translationRequests() as [$namespace, $path]) {
            if (is_dir($path)) {
                app('translator')->addNamespace($namespace, $path);
            }
        }

        // Process Livewire component requests
        foreach ($event->livewireRequests() as [$alias, $class]) {
            if (class_exists(Livewire::class)) {
                Livewire::component($alias, $class);
            }
        }

        // Process route requests with admin middleware
        foreach ($event->routeRequests() as $callback) {
            Route::middleware('admin')->group($callback);
        }

        // Note: Navigation is handled via AdminMenuProvider interface.
        // Modules implementing that interface will have their navigation
        // registered through the existing AdminMenuRegistry::register() call.
        // The $event->navigation() requests are available for future use
        // when we move away from the AdminMenuProvider pattern.
    }

    /**
     * Fire ClientRoutesRegistering and process requests.
     *
     * Called by Front/Client/Boot when client routes are being set up.
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

        // Process Livewire component requests
        foreach ($event->livewireRequests() as [$alias, $class]) {
            if (class_exists(Livewire::class)) {
                Livewire::component($alias, $class);
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
     * Called by Front/Api/Boot when API routes are being set up.
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
     */
    protected function fireConsoleBooting(): void
    {
        $event = new ConsoleBooting;
        event($event);

        // Process command requests
        if (! empty($event->commandRequests())) {
            $this->commands($event->commandRequests());
        }
    }

    /**
     * Fire QueueWorkerBooting and process requests.
     */
    protected function fireQueueWorkerBooting(): void
    {
        $event = new QueueWorkerBooting;
        event($event);

        // Job registration handled by Laravel's queue system
    }
}
