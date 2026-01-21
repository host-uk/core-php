<?php

namespace Core\Mod\Web\Policies;

use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;

class BioPagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Page $bioLink): bool
    {
        // User must own the biolink or be in the same workspace
        if ($bioLink->user_id === $user->id) {
            return true;
        }

        // Check workspace membership
        $workspace = $bioLink->workspace;
        if ($workspace && $user->hostWorkspaces->contains($workspace->id)) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Can create if user has access to at least one workspace
        return $user->hostWorkspaces()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Page $bioLink): bool
    {
        // User must own the biolink
        return $bioLink->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Page $bioLink): bool
    {
        // User must own the biolink
        return $bioLink->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Page $bioLink): bool
    {
        // User must own the biolink
        return $bioLink->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Page $bioLink): bool
    {
        // Only the owner can force delete
        return $bioLink->user_id === $user->id;
    }
}
