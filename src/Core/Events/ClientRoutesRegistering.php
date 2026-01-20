<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when client routes are being registered.
 *
 * Modules listen to this event to register routes for authenticated
 * SaaS customers managing their namespace/workspace.
 *
 * Routes are automatically wrapped with the 'client' middleware group.
 *
 * Use this for authenticated namespace management pages:
 * - Settings pages
 * - Analytics dashboards
 * - Content editors
 */
class ClientRoutesRegistering extends LifecycleEvent
{
    //
}
