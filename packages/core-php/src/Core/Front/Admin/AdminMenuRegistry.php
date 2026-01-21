<?php

declare(strict_types=1);

namespace Core\Front\Admin;

use Core\Front\Admin\Contracts\AdminMenuProvider;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;

/**
 * Registry for admin menu items.
 *
 * Modules register themselves during boot. The registry builds the complete
 * menu structure at render time, handling entitlement checks and sorting.
 */
class AdminMenuRegistry
{
    /**
     * Registered menu providers.
     *
     * @var array<AdminMenuProvider>
     */
    protected array $providers = [];

    /**
     * Pre-defined menu groups with metadata.
     *
     * Groups with 'standalone' => true render items directly.
     * Other groups become dropdown parents with items as children.
     *
     * @var array<string, array>
     */
    protected array $groups = [
        'dashboard' => [
            'standalone' => true,
        ],
        'workspaces' => [
            'label' => 'Workspaces',
            'icon' => 'layer-group',
            'color' => 'blue',
        ],
        'services' => [
            'standalone' => true,
        ],
        'settings' => [
            'label' => 'Account',
            'icon' => 'gear',
            'color' => 'zinc',
        ],
        'admin' => [
            'label' => 'Admin',
            'icon' => 'shield',
            'color' => 'amber',
        ],
    ];

    public function __construct(
        protected EntitlementService $entitlements,
    ) {}

    /**
     * Register a menu provider.
     */
    public function register(AdminMenuProvider $provider): void
    {
        $this->providers[] = $provider;
    }

    /**
     * Build the complete menu structure.
     *
     * @param  Workspace|null  $workspace  Current workspace for entitlement checks
     * @param  bool  $isAdmin  Whether user is admin (Hades)
     * @return array<int, array>
     */
    public function build(?Workspace $workspace, bool $isAdmin = false): array
    {
        // Collect all items from all providers
        $allItems = $this->collectItems($workspace, $isAdmin);

        // Build flat structure with dividers
        $menu = [];
        $firstGroup = true;

        foreach ($this->groups as $groupKey => $groupConfig) {
            // Skip admin group unless user is admin AND on system workspace
            if ($groupKey === 'admin' && (! $isAdmin || $workspace?->slug !== 'system')) {
                continue;
            }

            $groupItems = $allItems[$groupKey] ?? [];

            if (empty($groupItems)) {
                continue;
            }

            // Sort by priority
            usort($groupItems, fn ($a, $b) => $a['priority'] <=> $b['priority']);

            // Evaluate closures and extract items
            $evaluatedItems = [];
            foreach ($groupItems as $item) {
                $evaluated = ($item['item'])();
                if ($evaluated !== null) {
                    $evaluatedItems[] = $evaluated;
                }
            }

            if (empty($evaluatedItems)) {
                continue;
            }

            // Add divider before non-first groups
            if (! $firstGroup) {
                $menu[] = ['divider' => true];
            }
            $firstGroup = false;

            // Standalone groups add items directly
            if (! empty($groupConfig['standalone'])) {
                foreach ($evaluatedItems as $item) {
                    $menu[] = $item;
                }

                continue;
            }

            // Other groups become dropdown parents
            // Check if any item is active
            $isActive = collect($evaluatedItems)->contains(fn ($item) => $item['active'] ?? false);

            // Flatten children: each item becomes a child entry
            $children = [];
            foreach ($evaluatedItems as $item) {
                if (! empty($item['children'])) {
                    // Item has its own children - add label as section header then children
                    $children[] = [
                        'section' => $item['label'],
                        'icon' => $item['icon'] ?? null,
                        'color' => $item['color'] ?? null,
                    ];
                    foreach ($item['children'] as $child) {
                        $children[] = $child;
                    }
                } else {
                    // Item is a direct link - preserve icon and color
                    $children[] = [
                        'label' => $item['label'],
                        'href' => $item['href'] ?? '#',
                        'icon' => $item['icon'] ?? null,
                        'color' => $item['color'] ?? null,
                        'active' => $item['active'] ?? false,
                        'badge' => $item['badge'] ?? null,
                    ];
                }
            }

            $menu[] = [
                'label' => $groupConfig['label'],
                'icon' => $groupConfig['icon'],
                'color' => $groupConfig['color'],
                'active' => $isActive,
                'children' => $children,
            ];
        }

        return $menu;
    }

    /**
     * Collect items from all providers, filtering by entitlements.
     *
     * @return array<string, array<int, array{priority: int, item: \Closure}>>
     */
    protected function collectItems(?Workspace $workspace, bool $isAdmin): array
    {
        $grouped = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->adminMenuItems() as $registration) {
                $group = $registration['group'] ?? 'services';
                $entitlement = $registration['entitlement'] ?? null;
                $requiresAdmin = $registration['admin'] ?? false;

                // Skip if requires admin and user isn't admin
                if ($requiresAdmin && ! $isAdmin) {
                    continue;
                }

                // Skip if entitlement check fails
                if ($entitlement && $workspace) {
                    if ($this->entitlements->can($workspace, $entitlement)->isDenied()) {
                        continue;
                    }
                }

                // Skip if no workspace and entitlement required
                if ($entitlement && ! $workspace) {
                    continue;
                }

                $grouped[$group][] = [
                    'priority' => $registration['priority'] ?? 50,
                    'item' => $registration['item'],
                ];
            }
        }

        return $grouped;
    }

    /**
     * Get available group keys.
     *
     * @return array<string>
     */
    public function getGroups(): array
    {
        return array_keys($this->groups);
    }

    /**
     * Get group configuration.
     *
     * @return array<string, array>
     */
    public function getGroupConfig(string $key): array
    {
        return $this->groups[$key] ?? [];
    }

    /**
     * Get all service menu items indexed by service key.
     *
     * @param  Workspace|null  $workspace  Current workspace for entitlement checks
     * @param  bool  $isAdmin  Whether user is admin (Hades)
     * @return array<string, array> Service items indexed by service key
     */
    public function getAllServiceItems(?Workspace $workspace, bool $isAdmin = false): array
    {
        $services = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->adminMenuItems() as $registration) {
                if (($registration['group'] ?? 'services') !== 'services') {
                    continue;
                }

                $serviceKey = $registration['service'] ?? null;
                if (! $serviceKey) {
                    continue;
                }

                $entitlement = $registration['entitlement'] ?? null;
                $requiresAdmin = $registration['admin'] ?? false;

                // Skip if requires admin and user isn't admin
                if ($requiresAdmin && ! $isAdmin) {
                    continue;
                }

                // Skip if entitlement check fails
                if ($entitlement && $workspace) {
                    if ($this->entitlements->can($workspace, $entitlement)->isDenied()) {
                        continue;
                    }
                }

                // Evaluate the closure and store by service key
                $item = ($registration['item'])();
                if ($item) {
                    $services[$serviceKey] = array_merge($item, [
                        'priority' => $registration['priority'] ?? 50,
                    ]);
                }
            }
        }

        // Sort by priority
        uasort($services, fn ($a, $b) => ($a['priority'] ?? 50) <=> ($b['priority'] ?? 50));

        return $services;
    }

    /**
     * Get a specific service's menu item including its children (tabs).
     *
     * @param  string  $serviceKey  The service identifier (e.g., 'commerce', 'support')
     * @param  Workspace|null  $workspace  Current workspace for entitlement checks
     * @param  bool  $isAdmin  Whether user is admin (Hades)
     * @return array|null The service menu item with children, or null if not found
     */
    public function getServiceItem(string $serviceKey, ?Workspace $workspace, bool $isAdmin = false): ?array
    {
        foreach ($this->providers as $provider) {
            foreach ($provider->adminMenuItems() as $registration) {
                // Only check services group items with matching service key
                if (($registration['group'] ?? 'services') !== 'services') {
                    continue;
                }

                if (($registration['service'] ?? null) !== $serviceKey) {
                    continue;
                }

                $entitlement = $registration['entitlement'] ?? null;
                $requiresAdmin = $registration['admin'] ?? false;

                // Skip if requires admin and user isn't admin
                if ($requiresAdmin && ! $isAdmin) {
                    continue;
                }

                // Skip if entitlement check fails
                if ($entitlement && $workspace) {
                    if ($this->entitlements->can($workspace, $entitlement)->isDenied()) {
                        continue;
                    }
                }

                // Evaluate the closure and return the item
                $item = ($registration['item'])();

                return $item;
            }
        }

        return null;
    }
}
