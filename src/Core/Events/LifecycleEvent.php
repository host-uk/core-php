<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Base class for lifecycle events.
 *
 * Lifecycle events are fired at key points during application bootstrap.
 * Modules listen to these events via static $listens arrays and register
 * their resources (routes, views, navigation, etc.) through request methods.
 *
 * Core collects all requests and processes them with validation, ensuring
 * modules cannot directly mutate infrastructure.
 */
abstract class LifecycleEvent
{
    protected array $navigationRequests = [];

    protected array $routeRequests = [];

    protected array $viewRequests = [];

    protected array $middlewareRequests = [];

    protected array $livewireRequests = [];

    protected array $commandRequests = [];

    protected array $translationRequests = [];

    protected array $bladeComponentRequests = [];

    protected array $policyRequests = [];

    /**
     * Request a navigation item be added.
     */
    public function navigation(array $item): void
    {
        $this->navigationRequests[] = $item;
    }

    /**
     * Request routes be registered.
     */
    public function routes(callable $callback): void
    {
        $this->routeRequests[] = $callback;
    }

    /**
     * Request a view namespace be registered.
     */
    public function views(string $namespace, string $path): void
    {
        $this->viewRequests[] = [$namespace, $path];
    }

    /**
     * Request a middleware alias be registered.
     */
    public function middleware(string $alias, string $class): void
    {
        $this->middlewareRequests[] = [$alias, $class];
    }

    /**
     * Request a Livewire component be registered.
     */
    public function livewire(string $alias, string $class): void
    {
        $this->livewireRequests[] = [$alias, $class];
    }

    /**
     * Request an Artisan command be registered.
     */
    public function command(string $class): void
    {
        $this->commandRequests[] = $class;
    }

    /**
     * Request translations be loaded for a namespace.
     */
    public function translations(string $namespace, string $path): void
    {
        $this->translationRequests[] = [$namespace, $path];
    }

    /**
     * Request an anonymous Blade component path be registered.
     */
    public function bladeComponentPath(string $path, ?string $namespace = null): void
    {
        $this->bladeComponentRequests[] = [$path, $namespace];
    }

    /**
     * Request a policy be registered for a model.
     */
    public function policy(string $model, string $policy): void
    {
        $this->policyRequests[] = [$model, $policy];
    }

    /**
     * Get all navigation requests for processing.
     */
    public function navigationRequests(): array
    {
        return $this->navigationRequests;
    }

    /**
     * Get all route requests for processing.
     */
    public function routeRequests(): array
    {
        return $this->routeRequests;
    }

    /**
     * Get all view namespace requests for processing.
     */
    public function viewRequests(): array
    {
        return $this->viewRequests;
    }

    /**
     * Get all middleware alias requests for processing.
     */
    public function middlewareRequests(): array
    {
        return $this->middlewareRequests;
    }

    /**
     * Get all Livewire component requests for processing.
     */
    public function livewireRequests(): array
    {
        return $this->livewireRequests;
    }

    /**
     * Get all command requests for processing.
     */
    public function commandRequests(): array
    {
        return $this->commandRequests;
    }

    /**
     * Get all translation requests for processing.
     */
    public function translationRequests(): array
    {
        return $this->translationRequests;
    }

    /**
     * Get all Blade component path requests for processing.
     */
    public function bladeComponentRequests(): array
    {
        return $this->bladeComponentRequests;
    }

    /**
     * Get all policy requests for processing.
     */
    public function policyRequests(): array
    {
        return $this->policyRequests;
    }
}
