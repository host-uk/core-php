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
 * Interface for providers that supply dynamic menu items.
 *
 * Dynamic menu items are computed at runtime based on context (user, workspace,
 * database state, etc.) and are never cached. Use this interface when menu items
 * need to reflect real-time data such as notification counts, recent items, or
 * user-specific content.
 *
 * Classes implementing this interface are processed separately from static
 * AdminMenuProvider items - their results are merged after cache retrieval.
 */
interface DynamicMenuProvider
{
    /**
     * Get dynamic menu items that should not be cached.
     *
     * Called on every request to retrieve menu items that depend on
     * real-time data. Keep this method efficient as it runs uncached.
     *
     * Each item should include the same structure as AdminMenuProvider::adminMenuItems()
     * plus an optional 'dynamic' key set to true for identification.
     *
     * @param  User|null  $user  The authenticated user
     * @param  Workspace|null  $workspace  The current workspace context
     * @param  bool  $isAdmin  Whether the user is an admin
     * @return array<int, array{
     *     group: string,
     *     priority: int,
     *     entitlement?: string|null,
     *     permissions?: array<string>|null,
     *     admin?: bool,
     *     dynamic?: bool,
     *     item: \Closure
     * }>
     */
    public function dynamicMenuItems(?User $user, ?Workspace $workspace, bool $isAdmin): array;

    /**
     * Get the cache key modifier for dynamic items.
     *
     * Dynamic items from this provider will invalidate menu cache when
     * this key changes. Return null if dynamic items should never affect
     * cache invalidation.
     *
     * @param  User|null  $user
     * @param  Workspace|null  $workspace
     * @return string|null
     */
    public function dynamicCacheKey(?User $user, ?Workspace $workspace): ?string;
}
