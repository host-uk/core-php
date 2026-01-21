<?php

declare(strict_types=1);

namespace Core\Mod\Web\Actions;

use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Spatie\Activitylog\Facades\Activity;

/**
 * Update an existing bio.
 *
 * Usage:
 *   $action = app(UpdateBiolink::class);
 *   $biolink = $action->handle($biolink, $data, $user);
 *
 *   // Or via static helper:
 *   $biolink = UpdateBiolink::run($biolink, $data, $user);
 */
class UpdateBiolink
{
    /**
     * Update the bio.
     *
     * @param  array{url?: string, settings?: array, is_enabled?: bool}  $data
     */
    public function handle(Page $biolink, array $data, ?User $user = null): BioLink
    {
        $oldValues = $biolink->only(array_keys($data));

        $biolink->update($data);

        // Log activity with changes
        $activity = Activity::performedOn($biolink)
            ->withProperties([
                'old' => $oldValues,
                'new' => $data,
            ]);

        if ($user) {
            $activity->causedBy($user);
        }

        $activity->log('updated');

        return $biolink->fresh();
    }

    /**
     * Static helper to run the action.
     */
    public static function run(Page $biolink, array $data, ?User $user = null): BioLink
    {
        return app(static::class)->handle($biolink, $data, $user);
    }
}
