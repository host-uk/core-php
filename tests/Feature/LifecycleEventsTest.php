<?php

declare(strict_types=1);

namespace Core\Tests\Feature;

use Core\Events\AdminPanelBooting;
use Core\Events\ApiRoutesRegistering;
use Core\Events\ClientRoutesRegistering;
use Core\Events\ConsoleBooting;
use Core\Events\FrameworkBooted;
use Core\Events\McpToolsRegistering;
use Core\Events\WebRoutesRegistering;
use Core\Tests\TestCase;

class LifecycleEventsTest extends TestCase
{
    public function test_web_routes_event_collects_route_requests(): void
    {
        $event = new WebRoutesRegistering;

        $event->routes(fn () => 'test');

        $this->assertCount(1, $event->routeRequests());
    }

    public function test_web_routes_event_collects_view_requests(): void
    {
        $event = new WebRoutesRegistering;

        $event->views('test', '/path/to/views');

        $requests = $event->viewRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals(['test', '/path/to/views'], $requests[0]);
    }

    public function test_admin_event_collects_navigation_requests(): void
    {
        $event = new AdminPanelBooting;

        $event->navigation(['label' => 'Test', 'icon' => 'cog']);

        $this->assertCount(1, $event->navigationRequests());
    }

    public function test_api_event_collects_route_requests(): void
    {
        $event = new ApiRoutesRegistering;

        $event->routes(fn () => 'api-test');

        $this->assertCount(1, $event->routeRequests());
    }

    public function test_client_event_collects_livewire_requests(): void
    {
        $event = new ClientRoutesRegistering;

        $event->livewire('test-component', 'App\\Livewire\\TestComponent');

        $requests = $event->livewireRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals(['test-component', 'App\\Livewire\\TestComponent'], $requests[0]);
    }

    public function test_console_event_collects_command_requests(): void
    {
        $event = new ConsoleBooting;

        $event->command('App\\Console\\Commands\\TestCommand');

        $this->assertCount(1, $event->commandRequests());
    }

    public function test_mcp_event_collects_handlers(): void
    {
        $event = new McpToolsRegistering;

        $event->handler('App\\Mcp\\TestHandler');

        $this->assertCount(1, $event->handlers());
        $this->assertEquals(['App\\Mcp\\TestHandler'], $event->handlers());
    }

    public function test_framework_booted_event_exists(): void
    {
        $event = new FrameworkBooted;

        $this->assertInstanceOf(FrameworkBooted::class, $event);
    }

    public function test_lifecycle_event_collects_middleware_requests(): void
    {
        $event = new WebRoutesRegistering;

        $event->middleware('custom', 'App\\Http\\Middleware\\Custom');

        $requests = $event->middlewareRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals(['custom', 'App\\Http\\Middleware\\Custom'], $requests[0]);
    }

    public function test_lifecycle_event_collects_translation_requests(): void
    {
        $event = new WebRoutesRegistering;

        $event->translations('test', '/path/to/lang');

        $requests = $event->translationRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals(['test', '/path/to/lang'], $requests[0]);
    }

    public function test_lifecycle_event_collects_policy_requests(): void
    {
        $event = new WebRoutesRegistering;

        $event->policy('App\\Models\\User', 'App\\Policies\\UserPolicy');

        $requests = $event->policyRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals(['App\\Models\\User', 'App\\Policies\\UserPolicy'], $requests[0]);
    }

    public function test_lifecycle_event_collects_blade_component_requests(): void
    {
        $event = new WebRoutesRegistering;

        $event->bladeComponentPath('/path/to/components', 'custom');

        $requests = $event->bladeComponentRequests();
        $this->assertCount(1, $requests);
        $this->assertEquals(['/path/to/components', 'custom'], $requests[0]);
    }
}
