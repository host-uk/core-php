<?php

declare(strict_types=1);

namespace Core\Admin;

use Core\ModuleRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Core Admin Package Bootstrap.
 *
 * Registers package paths with the module scanner.
 */
class Boot extends ServiceProvider
{
    public function register(): void
    {
        // Register our Website modules with the scanner
        app(ModuleRegistry::class)->addPaths([
            __DIR__.'/Website',
        ]);
    }

    public function boot(): void
    {
        // Load Hub translations
        $this->loadTranslationsFrom(__DIR__.'/Mod/Hub/Lang', 'hub');
    }
}
