<?php

declare(strict_types=1);

namespace Core\Tests\Feature;

use Core\Module\ModuleRegistry;
use Core\Module\ModuleScanner;
use Core\Tests\TestCase;

class ModuleRegistryTest extends TestCase
{
    public function test_registry_starts_unregistered(): void
    {
        $registry = new ModuleRegistry(new ModuleScanner);

        $this->assertFalse($registry->isRegistered());
    }

    public function test_registry_marks_as_registered_after_register(): void
    {
        $registry = new ModuleRegistry(new ModuleScanner);

        $registry->register([]);

        $this->assertTrue($registry->isRegistered());
    }

    public function test_registry_only_registers_once(): void
    {
        $registry = new ModuleRegistry(new ModuleScanner);

        $registry->register([]);
        $registry->register([$this->getFixturePath('Mod')]);

        // Should still be empty since it only registers once
        $this->assertEmpty($registry->getMappings());
    }

    public function test_get_mappings_returns_array(): void
    {
        $registry = new ModuleRegistry(new ModuleScanner);

        $this->assertIsArray($registry->getMappings());
    }

    public function test_get_listeners_for_returns_empty_for_unknown_event(): void
    {
        $registry = new ModuleRegistry(new ModuleScanner);

        $result = $registry->getListenersFor('Unknown\\Event');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_get_events_returns_array(): void
    {
        $registry = new ModuleRegistry(new ModuleScanner);
        $registry->register([]);

        $this->assertIsArray($registry->getEvents());
    }

    public function test_get_modules_returns_array(): void
    {
        $registry = new ModuleRegistry(new ModuleScanner);
        $registry->register([]);

        $this->assertIsArray($registry->getModules());
    }
}
