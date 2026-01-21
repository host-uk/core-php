<?php

declare(strict_types=1);

namespace Core\Mod\Web;

use Core\Events\ApiRoutesRegistering;
use Core\Events\ClientRoutesRegistering;
use Core\Events\ConsoleBooting;
use Core\Events\WebRoutesRegistering;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class Boot extends ServiceProvider
{
    /**
     * The module name.
     */
    protected string $moduleName = 'webpage';

    /**
     * Events this module listens to for lazy loading.
     *
     * @var array<class-string, string>
     */
    public static array $listens = [
        ClientRoutesRegistering::class => 'onClientRoutes',
        WebRoutesRegistering::class => 'onWebRoutes',
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

        $this->mergeConfigFrom(
            __DIR__.'/device-frames.php',
            'device-frames'
        );

        $this->mergeConfigFrom(
            __DIR__.'/config/viewports.php',
            'webpage.viewports'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Models\Page::class, Policies\BioPagePolicy::class);

        $this->loadMigrationsFrom(__DIR__.'/Migrations');
        $this->loadTranslationsFrom(__DIR__.'/Lang/en_GB', 'web');

        // Blade anonymous components (device mockups)
        Blade::anonymousComponentPath(__DIR__.'/View/Blade/components');

        if ($this->app->runningInConsole()) {
            $this->publishAssets();
        }
    }

    /**
     * Publish module assets for customisation.
     */
    protected function publishAssets(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../config.php' => config_path("{$this->moduleName}.php"),
        ], "{$this->moduleName}-config");

        // Publish views for override
        $this->publishes([
            __DIR__.'/../View/Web' => resource_path("views/vendor/{$this->moduleName}"),
        ], "{$this->moduleName}-views");

        // Publish migrations
        $this->publishes([
            __DIR__.'/../Migrations' => database_path('migrations'),
        ], "{$this->moduleName}-migrations");
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [];
    }

    // -------------------------------------------------------------------------
    // Event-driven handlers
    // -------------------------------------------------------------------------

    public function onClientRoutes(ClientRoutesRegistering $event): void
    {
        $event->views($this->moduleName, __DIR__.'/View/Blade');

        // Admin/Hub components (user dashboard)
        $event->livewire('webpage.admin.index', View\Modal\Admin\Index::class);
        $event->livewire('webpage.admin.editor', View\Modal\Admin\Editor::class);
        $event->livewire('webpage.admin.analytics', View\Modal\Admin\Analytics::class);
        $event->livewire('webpage.admin.analytics-overview', View\Modal\Admin\AnalyticsOverview::class);
        $event->livewire('webpage.admin.short-link-index', View\Modal\Admin\ShortLinkIndex::class);
        $event->livewire('webpage.admin.create-short-link', View\Modal\Admin\CreateShortLink::class);
        $event->livewire('webpage.admin.create-static-page', View\Modal\Admin\CreateStaticPage::class);
        $event->livewire('webpage.admin.edit-static-page', View\Modal\Admin\EditStaticPage::class);
        $event->livewire('webpage.admin.create-file-link', View\Modal\Admin\CreateFileLink::class);
        $event->livewire('webpage.admin.create-vcard', View\Modal\Admin\CreateVcard::class);
        $event->livewire('webpage.admin.create-event', View\Modal\Admin\CreateEvent::class);
        $event->livewire('webpage.admin.domain-manager', View\Modal\Admin\DomainManager::class);
        $event->livewire('webpage.admin.pixel-manager', View\Modal\Admin\PixelManager::class);
        $event->livewire('webpage.admin.project-manager', View\Modal\Admin\ProjectManager::class);
        $event->livewire('webpage.admin.theme-gallery', View\Modal\Admin\ThemeGallery::class);
        $event->livewire('webpage.admin.theme-editor', View\Modal\Admin\ThemeEditor::class);
        $event->livewire('webpage.admin.notification-handler-manager', View\Modal\Admin\NotificationHandlerManager::class);
        $event->livewire('webpage.admin.submissions-manager', View\Modal\Admin\SubmissionsManager::class);
        $event->livewire('webpage.admin.qr-code-editor', View\Modal\Admin\QrCodeEditor::class);
        $event->livewire('webpage.admin.pwa-editor', View\Modal\Admin\PwaEditor::class);
        $event->livewire('webpage.admin.splash-page-editor', View\Modal\Admin\SplashPageEditor::class);
        $event->livewire('webpage.admin.ai-assistant', View\Modal\Admin\AiAssistant::class);
        $event->livewire('webpage.admin.template-gallery', View\Modal\Admin\TemplateGallery::class);
        $event->livewire('webpage.admin.template-manager', View\Modal\Admin\TemplateManager::class);
        $event->livewire('webpage.admin.theme-gallery-manager', View\Modal\Admin\ThemeGalleryManager::class);
        $event->livewire('webpage.admin.image-optimisation-stats', View\Modal\Admin\ImageOptimisationStats::class);
    }

    public function onWebRoutes(WebRoutesRegistering $event): void
    {
        $event->views($this->moduleName, __DIR__.'/View/Blade');

        if (file_exists(__DIR__.'/Routes/web.php')) {
            $event->routes(fn () => Route::middleware('web')->group(__DIR__.'/Routes/web.php'));
        }
    }

    public function onApiRoutes(ApiRoutesRegistering $event): void
    {
        if (file_exists(__DIR__.'/Routes/api.php')) {
            $event->routes(fn () => Route::middleware('api')->group(__DIR__.'/Routes/api.php'));
        }
    }

    public function onConsole(ConsoleBooting $event): void
    {
        $event->command(Console\Commands\AggregateBioClicks::class);
        $event->command(Console\Commands\SeedBioDemos::class);
        $event->command(Console\Commands\SendBioEmailReports::class);
        $event->command(Console\Commands\VerifyBioDomains::class);
        $event->command(Console\Commands\Import66BiolinksThemes::class);
    }
}
