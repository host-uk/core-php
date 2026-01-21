<?php

declare(strict_types=1);

namespace Core;

use Illuminate\Support\Facades\Event;

/**
 * Manages lazy module registration via events.
 *
 * Scans module directories, extracts $listens declarations,
 * and wires up lazy listeners for each event-module pair.
 *
 * Listeners are registered in priority order (higher priority runs first).
 *
 * Usage:
 *     $registry = new ModuleRegistry(new ModuleScanner());
 *     $registry->register([app_path('Core'), app_path('Mod')]);
 */
class ModuleRegistry
{
    private array $mappings = [];

    private bool $registered = false;

    public function __construct(
        private ModuleScanner $scanner
    ) {}

    /**
     * Scan paths and register lazy listeners for all declared events.
     *
     * Listeners are sorted by priority (highest first) before registration.
     *
     * @param  array<string>  $paths  Directories containing modules
     */
    public function register(array $paths): void
    {
        if ($this->registered) {
            return;
        }

        $this->mappings = $this->scanner->scan($paths);

        foreach ($this->mappings as $event => $listeners) {
            $sorted = $this->sortByPriority($listeners);

            foreach ($sorted as $moduleClass => $config) {
                Event::listen($event, new LazyModuleListener($moduleClass, $config['method']));
            }
        }

        $this->registered = true;
    }

    /**
     * Sort listeners by priority (highest first).
     *
     * @param  array<string, array{method: string, priority: int}>  $listeners
     * @return array<string, array{method: string, priority: int}>
     */
    private function sortByPriority(array $listeners): array
    {
        uasort($listeners, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        return $listeners;
    }

    /**
     * Get all scanned mappings.
     *
     * @return array<string, array<string, array{method: string, priority: int}>> Event => [Module => config]
     */
    public function getMappings(): array
    {
        return $this->mappings;
    }

    /**
     * Get modules that listen to a specific event.
     *
     * @return array<string, array{method: string, priority: int}> Module => config
     */
    public function getListenersFor(string $event): array
    {
        return $this->mappings[$event] ?? [];
    }

    /**
     * Check if registration has been performed.
     */
    public function isRegistered(): bool
    {
        return $this->registered;
    }

    /**
     * Get all events that have listeners.
     *
     * @return array<string>
     */
    public function getEvents(): array
    {
        return array_keys($this->mappings);
    }

    /**
     * Get all modules that have declared listeners.
     *
     * @return array<string>
     */
    public function getModules(): array
    {
        $modules = [];

        foreach ($this->mappings as $listeners) {
            foreach (array_keys($listeners) as $module) {
                $modules[$module] = true;
            }
        }

        return array_keys($modules);
    }

    /**
     * Add additional paths to scan and register.
     *
     * Used by packages to register their module paths.
     * Note: Priority ordering only applies within the newly added paths.
     * For full priority control, use register() with all paths.
     *
     * @param  array<string>  $paths  Directories containing modules
     */
    public function addPaths(array $paths): void
    {
        $newMappings = $this->scanner->scan($paths);

        foreach ($newMappings as $event => $listeners) {
            $sorted = $this->sortByPriority($listeners);

            foreach ($sorted as $moduleClass => $config) {
                // Skip if already registered
                if (isset($this->mappings[$event][$moduleClass])) {
                    continue;
                }

                $this->mappings[$event][$moduleClass] = $config;
                Event::listen($event, new LazyModuleListener($moduleClass, $config['method']));
            }
        }
    }
}
