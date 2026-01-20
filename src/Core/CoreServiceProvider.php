<?php

declare(strict_types=1);

namespace Core;

use Core\Console\Commands\MakeModCommand;
use Core\Console\Commands\MakePlugCommand;
use Core\Console\Commands\MakeWebsiteCommand;
use Core\Events\FrameworkBooted;
use Core\Module\ModuleRegistry;
use Core\Module\ModuleScanner;
use Illuminate\Support\ServiceProvider;

/**
 * Core framework service provider.
 *
 * Manages lifecycle events for lazy module loading:
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
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/core.php', 'core');

        $this->app->singleton(ModuleScanner::class);
        $this->app->singleton(ModuleRegistry::class, function ($app) {
            return new ModuleRegistry($app->make(ModuleScanner::class));
        });

        $paths = config('core.module_paths', []);

        if (! empty($paths)) {
            $registry = $this->app->make(ModuleRegistry::class);
            $registry->register($paths);
        }
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeModCommand::class,
                MakeWebsiteCommand::class,
                MakePlugCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../../config/core.php' => config_path('core.php'),
            ], 'core-config');

            $this->publishes([
                __DIR__.'/../../stubs' => base_path('stubs/core'),
            ], 'core-stubs');
        }

        $this->app->booted(function () {
            event(new FrameworkBooted);
        });
    }
}
