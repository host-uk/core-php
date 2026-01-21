<?php

declare(strict_types=1);

namespace Core\Lang;

use Illuminate\Support\ServiceProvider;

/**
 * Core Translations Service Provider.
 *
 * Loads the framework's base translation files which provide:
 * - Brand identity text (name, tagline, copyright)
 * - Navigation labels
 * - Error page messages
 * - Common UI text (actions, status, validation)
 *
 * Usage in Blade: {{ __('core::core.brand.name') }}
 * Usage in PHP:   __('core::core.brand.name')
 *
 * Override translations by publishing to resources/lang/vendor/core/
 */
class Boot extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/en_GB', 'core');

        // Allow publishing translations for customisation
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/en_GB' => $this->app->langPath('vendor/core/en_GB'),
            ], 'core-translations');
        }
    }
}
