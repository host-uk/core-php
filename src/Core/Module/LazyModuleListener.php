<?php

declare(strict_types=1);

namespace Core\Module;

use Illuminate\Support\ServiceProvider;

/**
 * Wraps a module method as an event listener.
 *
 * The module is only instantiated when the event fires,
 * enabling lazy loading of modules based on actual usage.
 *
 * Handles both plain classes and ServiceProviders correctly.
 *
 * Usage:
 *     Event::listen(
 *         AdminPanelBooting::class,
 *         new LazyModuleListener(Commerce\Boot::class, 'registerAdmin')
 *     );
 */
class LazyModuleListener
{
    private ?object $instance = null;

    public function __construct(
        private string $moduleClass,
        private string $method
    ) {}

    /**
     * Handle the event by instantiating the module and calling its method.
     *
     * This is the callable interface for Laravel's event dispatcher.
     */
    public function __invoke(object $event): void
    {
        $module = $this->resolveModule();
        $module->{$this->method}($event);
    }

    /**
     * Alias for __invoke for explicit calls.
     */
    public function handle(object $event): void
    {
        $this->__invoke($event);
    }

    /**
     * Resolve the module instance.
     *
     * ServiceProviders are resolved via resolveProvider() to get proper $app injection.
     * Plain classes are resolved via make().
     */
    private function resolveModule(): object
    {
        if ($this->instance !== null) {
            return $this->instance;
        }

        $app = app();

        // Check if this is a ServiceProvider
        if (is_subclass_of($this->moduleClass, ServiceProvider::class)) {
            // Use resolveProvider for ServiceProviders - handles $app injection
            $this->instance = $app->resolveProvider($this->moduleClass);
        } else {
            // Plain class - just make it
            $this->instance = $app->make($this->moduleClass);
        }

        return $this->instance;
    }

    /**
     * Get the module class this listener wraps.
     */
    public function getModuleClass(): string
    {
        return $this->moduleClass;
    }

    /**
     * Get the method this listener will call.
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
