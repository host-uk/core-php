<?php

declare(strict_types=1);

namespace Core\Front\Admin\Contracts;

/**
 * Interface for modules that provide admin menu items.
 *
 * Modules implement this interface and register themselves with AdminMenuRegistry
 * during boot. The registry collects all items and builds the final menu structure.
 */
interface AdminMenuProvider
{
    /**
     * Return admin menu items for this module.
     *
     * Each item should specify:
     * - group: string (dashboard|webhost|services|settings|admin)
     * - priority: int (lower = earlier in group)
     * - entitlement: string|null (feature code for access check)
     * - admin: bool (requires Hades/admin user)
     * - item: Closure (lazy-evaluated menu item data)
     *
     * Example:
     * ```php
     * return [
     *     [
     *         'group' => 'services',
     *         'priority' => 20,
     *         'entitlement' => 'core.srv.bio',
     *         'item' => fn() => [
     *             'label' => 'BioHost',
     *             'icon' => 'link',
     *             'href' => route('hub.bio.index'),
     *             'active' => request()->routeIs('hub.bio.*'),
     *             'children' => [...],
     *         ],
     *     ],
     * ];
     * ```
     *
     * @return array<int, array{
     *     group: string,
     *     priority: int,
     *     entitlement?: string|null,
     *     admin?: bool,
     *     item: \Closure
     * }>
     */
    public function adminMenuItems(): array;
}
