<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Front\Admin\Contracts;

use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;

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
     * @param  User|null  $user  The authenticated user
     * @param  Workspace|null  $workspace  The current workspace context
     * @return bool
     */
    public function canViewMenu(?User $user, ?Workspace $workspace): bool;
}
