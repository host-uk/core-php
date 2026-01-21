<?php

declare(strict_types=1);

namespace Core\Mod\Web\Actions;

use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Spatie\Activitylog\Facades\Activity;

/**
 * Delete (soft delete) a bio.
 *
 * Usage:
 *   $action = app(DeleteBiolink::class);
 *   $action->handle($biolink, $user);
 *
 *   // Or via static helper:
 *   DeleteBiolink::run($biolink, $user);
 */
class DeleteBiolink
{
    /**
     * Delete the bio.
     */
    public function handle(Page $biolink, ?User $user = null): bool
    {
        // Log activity before deletion
        $activity = Activity::performedOn($biolink)
            ->withProperties([
                'url' => $biolink->url,
                'clicks' => $biolink->clicks,
            ]);

        if ($user) {
            $activity->causedBy($user);
        }

        $activity->log('deleted');

        // Soft delete the biolink
        return $biolink->delete();
    }

    /**
     * Static helper to run the action.
     */
    public static function run(Page $biolink, ?User $user = null): bool
    {
        return app(static::class)->handle($biolink, $user);
    }
}
