<?php

declare(strict_types=1);

namespace Core\Tests\Feature;

use Core\Events\WebRoutesRegistering;
use Core\Module\LazyModuleListener;
use Core\Tests\TestCase;

class LazyModuleListenerTest extends TestCase
{
    public function test_listener_stores_module_class(): void
    {
        $listener = new LazyModuleListener(
            'App\\Mod\\Test\\Boot',
            'onWebRoutes'
        );

        $this->assertEquals('App\\Mod\\Test\\Boot', $listener->getModuleClass());
    }

    public function test_listener_stores_method(): void
    {
        $listener = new LazyModuleListener(
            'App\\Mod\\Test\\Boot',
            'onWebRoutes'
        );

        $this->assertEquals('onWebRoutes', $listener->getMethod());
    }

    public function test_listener_invokes_module_method(): void
    {
        // Create a test module class
        $moduleClass = new class
        {
            public bool $called = false;

            public function onWebRoutes(WebRoutesRegistering $event): void
            {
                $this->called = true;
            }
        };

        // Bind it to the container
        $this->app->instance($moduleClass::class, $moduleClass);

        $listener = new LazyModuleListener(
            $moduleClass::class,
            'onWebRoutes'
        );

        $event = new WebRoutesRegistering;
        $listener($event);

        $this->assertTrue($moduleClass->called);
    }

    public function test_handle_is_alias_for_invoke(): void
    {
        $moduleClass = new class
        {
            public int $callCount = 0;

            public function onWebRoutes(WebRoutesRegistering $event): void
            {
                $this->callCount++;
            }
        };

        $this->app->instance($moduleClass::class, $moduleClass);

        $listener = new LazyModuleListener(
            $moduleClass::class,
            'onWebRoutes'
        );

        $event = new WebRoutesRegistering;
        $listener->handle($event);

        $this->assertEquals(1, $moduleClass->callCount);
    }
}
