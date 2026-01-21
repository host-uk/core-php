<?php

declare(strict_types=1);

namespace Core\Mod\Tenant\Concerns;

use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Trait for models that belong to a workspace.
 *
 * Provides workspace relationship, scoping, and workspace-scoped caching.
 *
 * Usage:
 *   class Account extends Model {
 *       use BelongsToWorkspace;
 *   }
 *
 *   // Get cached collection for current workspace
 *   $accounts = Account::ownedByCurrentWorkspaceCached();
 *
 *   // Get query scoped to current workspace
 *   $accounts = Account::ownedByCurrentWorkspace()->where('status', 'active')->get();
 */
trait BelongsToWorkspace
{
    /**
     * Boot the trait - sets up auto-assignment of workspace_id and cache invalidation.
     */
    protected static function bootBelongsToWorkspace(): void
    {
        // Auto-assign workspace_id when creating a model without one
        static::creating(function ($model) {
            if (empty($model->workspace_id)) {
                $workspace = static::getCurrentWorkspace();
                if ($workspace) {
                    $model->workspace_id = $workspace->id;
                }
            }
        });

        static::saved(function ($model) {
            if ($model->workspace_id) {
                static::clearWorkspaceCache($model->workspace_id);
            }
        });

        static::deleted(function ($model) {
            if ($model->workspace_id) {
                static::clearWorkspaceCache($model->workspace_id);
            }
        });
    }

    /**
     * Get the workspace this model belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope query to the current user's default workspace.
     */
    public function scopeOwnedByCurrentWorkspace(Builder $query): Builder
    {
        $workspace = static::getCurrentWorkspace();

        if (! $workspace) {
            return $query->whereRaw('1 = 0'); // Return empty result
        }

        return $query->where('workspace_id', $workspace->id);
    }

    /**
     * Scope query to a specific workspace.
     */
    public function scopeForWorkspace(Builder $query, Workspace|int $workspace): Builder
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $query->where('workspace_id', $workspaceId);
    }

    /**
     * Get all models owned by the current workspace, cached.
     *
     * @param  int  $ttl  Cache TTL in seconds (default 5 minutes)
     */
    public static function ownedByCurrentWorkspaceCached(int $ttl = 300): Collection
    {
        $workspace = static::getCurrentWorkspace();

        if (! $workspace) {
            return collect();
        }

        return Cache::remember(
            static::workspaceCacheKey($workspace->id),
            $ttl,
            fn () => static::ownedByCurrentWorkspace()->get()
        );
    }

    /**
     * Get all models for a specific workspace, cached.
     *
     * @param  int  $ttl  Cache TTL in seconds (default 5 minutes)
     */
    public static function forWorkspaceCached(Workspace|int $workspace, int $ttl = 300): Collection
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return Cache::remember(
            static::workspaceCacheKey($workspaceId),
            $ttl,
            fn () => static::forWorkspace($workspaceId)->get()
        );
    }

    /**
     * Get the cache key for a workspace's model collection.
     */
    protected static function workspaceCacheKey(int $workspaceId): string
    {
        $modelClass = class_basename(static::class);

        return "workspace.{$workspaceId}.{$modelClass}";
    }

    /**
     * Clear the cache for a workspace's model collection.
     */
    public static function clearWorkspaceCache(int $workspaceId): void
    {
        Cache::forget(static::workspaceCacheKey($workspaceId));
    }

    /**
     * Clear cache for all workspaces (use sparingly).
     */
    public static function clearAllWorkspaceCache(): void
    {
        // Clear for all workspaces the current user has access to
        $user = auth()->user();

        if ($user && method_exists($user, 'hostWorkspaces')) {
            foreach ($user->hostWorkspaces as $workspace) {
                static::clearWorkspaceCache($workspace->id);
            }
        }
    }

    /**
     * Get the current user's default workspace.
     */
    protected static function getCurrentWorkspace(): ?Workspace
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        // Use the Host UK method if available
        if (method_exists($user, 'defaultHostWorkspace')) {
            return $user->defaultHostWorkspace();
        }

        return null;
    }

    /**
     * Check if this model belongs to the given workspace.
     */
    public function belongsToWorkspace(Workspace|int $workspace): bool
    {
        $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

        return $this->workspace_id === $workspaceId;
    }

    /**
     * Check if this model belongs to the current user's workspace.
     */
    public function belongsToCurrentWorkspace(): bool
    {
        $workspace = static::getCurrentWorkspace();

        if (! $workspace) {
            return false;
        }

        return $this->belongsToWorkspace($workspace);
    }
}
