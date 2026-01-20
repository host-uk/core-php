<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when API routes are being registered.
 *
 * Modules listen to this event to register their REST API endpoints.
 * Routes are automatically wrapped with the 'api' middleware group.
 *
 * Only fired for API requests, not web or admin requests.
 */
class ApiRoutesRegistering extends LifecycleEvent
{
    //
}
