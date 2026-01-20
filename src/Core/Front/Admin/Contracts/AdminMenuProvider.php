<?php

declare(strict_types=1);

namespace Core\Front\Admin\Contracts;

/**
 * Interface for modules that provide admin menu items.
 *
 * Modules implement this interface and register themselves with an admin menu
 * registry during boot. The registry collects all items and builds the final
 * menu structure.
 */
interface AdminMenuProvider
{
    /**
     * Return admin menu items for this module.
     *
     * Each item should specify:
     * - group: string (e.g., dashboard, settings, services)
     * - priority: int (lower = earlier in group)
     * - item: Closure (lazy-evaluated menu item data)
     *
     * Example:
     * ```php
     * return [
     *     [
     *         'group' => 'services',
     *         'priority' => 20,
     *         'item' => fn() => [
     *             'label' => 'Products',
     *             'icon' => 'box',
     *             'href' => route('admin.products.index'),
     *             'active' => request()->routeIs('admin.products.*'),
     *             'children' => [...],
     *         ],
     *     ],
     * ];
     * ```
     *
     * @return array<int, array{
     *     group: string,
     *     priority: int,
     *     item: \Closure
     * }>
     */
    public function adminMenuItems(): array;
}
