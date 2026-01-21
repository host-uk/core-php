<?php

declare(strict_types=1);

namespace Website\Demo;

use Core\Events\DomainResolving;
use Core\Events\WebRoutesRegistering;
use Core\Website\DomainResolver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Demo Mod - Example marketing site.
 *
 * Shows how to create a website module for the Core PHP framework.
 * Uses the event-driven $listens pattern for lazy loading.
 */
class Boot extends ServiceProvider
{
    /**
     * Domain patterns this website responds to.
     *
     * @var array<string>
     */
    public static array $domains = [
        '/^core\.(test|localhost)$/',
    ];

    /**
     * Events this module listens to for lazy loading.
     *
     * @var array<class-string, string>
     */
    public static array $listens = [
        DomainResolving::class => 'onDomainResolving',
        WebRoutesRegistering::class => 'onWebRoutes',
    ];

    /**
     * Handle domain resolution - register if we match.
     */
    public function onDomainResolving(DomainResolving $event): void
    {
        foreach (static::$domains as $pattern) {
            if ($event->matches($pattern)) {
                $event->register(static::class);

                return;
            }
        }
    }

    public function register(): void
    {
        //
    }

    /**
     * Get domains for this website.
     *
     * @return array<string>
     */
    protected function domains(): array
    {
        return app(DomainResolver::class)->domainsFor(self::class);
    }

    /**
     * Register public web routes.
     */
    public function onWebRoutes(WebRoutesRegistering $event): void
    {
        $event->views('demo', __DIR__.'/View/Blade');

        // Register routes for all configured domains
        $domains = $this->domains();

        if (empty($domains)) {
            // No domain mapping - register globally (for demo/dev)
            $event->routes(fn () => Route::middleware('web')
                ->group(__DIR__.'/Routes/web.php'));
        } else {
            foreach ($domains as $domain) {
                $event->routes(fn () => Route::middleware('web')
                    ->domain($domain)
                    ->group(__DIR__.'/Routes/web.php'));
            }
        }

        // Livewire components - names must match Livewire's auto-discovery from namespace
        $event->livewire('website.demo.view.modal.landing', View\Modal\Landing::class);
        $event->livewire('website.demo.view.modal.login', View\Modal\Login::class);
        $event->livewire('website.demo.view.modal.install', View\Modal\Install::class);
    }
}
