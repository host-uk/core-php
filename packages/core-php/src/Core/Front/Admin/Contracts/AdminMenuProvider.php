<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Front\Admin\Contracts;

/**
 * Interface for modules that provide admin menu items.
 *
 * Modules implement this interface to contribute navigation items to the admin
 * panel sidebar. The `AdminMenuRegistry` collects items from all registered
 * providers and builds the final menu structure with proper ordering, grouping,
 * and permission filtering.
 *
 * ## Menu Item Structure
 *
 * Each item returned by `adminMenuItems()` specifies:
 *
 * - **group** - Where in the menu hierarchy (`dashboard`, `webhost`, `services`, `settings`, `admin`)
 * - **priority** - Order within group (lower = earlier)
 * - **entitlement** - Optional feature code for workspace-level access
 * - **permissions** - Optional array of required user permissions
 * - **admin** - Whether item requires Hades/admin user
 * - **item** - Closure returning the actual menu item data (lazy-evaluated)
 *
 * ## Lazy Evaluation
 *
 * The `item` closure is only called when the menu is rendered, after permission
 * checks pass. This avoids unnecessary work for filtered items and allows
 * route-dependent data (like `active` state) to be computed at render time.
 *
 * ## Registration
 *
 * Providers are typically registered via `AdminMenuRegistry::register()` during
 * the AdminPanelBooting event or in a service provider's boot method.
 *
 * @package Core\Front\Admin\Contracts
 *
 * @see DynamicMenuProvider For uncached, real-time menu items
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
     * - permissions: array|null (required user permissions)
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
     *         'permissions' => ['bio.view', 'bio.manage'],
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
     *     permissions?: array<string>|null,
     *     admin?: bool,
     *     item: \Closure
     * }>
     */
    public function adminMenuItems(): array;

    /**
     * Get the permissions required to view any menu items from this provider.
     *
     * This provides a way to define global permission requirements for all
     * menu items from this provider. Individual items can override with their
     * own 'permissions' key in adminMenuItems().
     *
     * Return an empty array if no global permissions are required.
     *
     * @return array<string>
     */
    public function menuPermissions(): array;

    /**
     * Check if the user has permission to view menu items from this provider.
     *
     * Override this method to implement custom permission logic beyond
     * simple permission key checks.
     *
     * @param  object|null  $user  The authenticated user (User model instance)
     * @param  object|null  $workspace  The current workspace context (Workspace model instance)
     * @return bool
     */
    public function canViewMenu(?object $user, ?object $workspace): bool;
}
