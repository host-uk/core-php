<?php

declare(strict_types=1);

namespace Core\Mod\Tenant\Scopes;

use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope for workspace tenancy.
 *
 * Automatically filters queries to the current workspace context.
 * Can be disabled per-query using withoutGlobalScope().
 */
class WorkspaceScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply if workspace_id column exists on the model
        if (! $this->hasWorkspaceColumn($model)) {
            return;
        }

        // Get current workspace (returns Workspace model instance)
        $workspace = Workspace::current();

        if ($workspace) {
            $builder->where($model->getTable().'.workspace_id', $workspace->id);
        }
    }

    /**
     * Check if the model has a workspace_id column.
     */
    protected function hasWorkspaceColumn(Model $model): bool
    {
        $fillable = $model->getFillable();
        $guarded = $model->getGuarded();

        // Check if workspace_id is in fillable or not in guarded
        return in_array('workspace_id', $fillable, true)
            || (count($guarded) === 1 && $guarded[0] === '*')
            || ! in_array('workspace_id', $guarded, true);
    }

    /**
     * Extend the query builder with workspace-specific methods.
     */
    public function extend(Builder $builder): void
    {
        // Add method to set workspace context for a query
        $builder->macro('forWorkspace', function (Builder $builder, Workspace|int $workspace) {
            $workspaceId = $workspace instanceof Workspace ? $workspace->id : $workspace;

            return $builder->withoutGlobalScope($this)
                ->where($builder->getModel()->getTable().'.workspace_id', $workspaceId);
        });

        // Add method to query across all workspaces (use with caution)
        $builder->macro('acrossWorkspaces', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });
    }
}
