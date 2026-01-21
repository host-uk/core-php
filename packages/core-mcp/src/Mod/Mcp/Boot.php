<?php

declare(strict_types=1);

namespace Core\Mod\Mcp;

use Core\Events\AdminPanelBooting;
use Core\Events\ConsoleBooting;
use Core\Events\McpToolsRegistering;
use Illuminate\Support\ServiceProvider;

class Boot extends ServiceProvider
{
    /**
     * The module name.
     */
    protected string $moduleName = 'mcp';

    /**
     * Events this module listens to for lazy loading.
     *
     * @var array<class-string, string>
     */
    public static array $listens = [
        AdminPanelBooting::class => 'onAdminPanel',
        ConsoleBooting::class => 'onConsole',
        McpToolsRegistering::class => 'onMcpTools',
    ];

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

    public function onAdminPanel(AdminPanelBooting $event): void
    {
        $event->views($this->moduleName, __DIR__.'/View/Blade');

        if (file_exists(__DIR__.'/Routes/admin.php')) {
            $event->routes(fn () => require __DIR__.'/Routes/admin.php');
        }

        $event->livewire('mcp.admin.api-key-manager', View\Modal\Admin\ApiKeyManager::class);
        $event->livewire('mcp.admin.playground', View\Modal\Admin\Playground::class);
        $event->livewire('mcp.admin.request-log', View\Modal\Admin\RequestLog::class);
    }

    public function onConsole(ConsoleBooting $event): void
    {
        $event->command(Console\Commands\McpAgentServerCommand::class);
    }

    public function onMcpTools(McpToolsRegistering $event): void
    {
        // MCP tool handlers will be registered here once extracted
        // from the monolithic McpAgentServerCommand
    }
}
