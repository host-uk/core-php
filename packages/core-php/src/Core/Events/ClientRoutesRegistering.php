<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when client routes are being registered.
 *
 * Modules listen to this event to register routes for namespace owners
 * (authenticated SaaS customers managing their space).
 *
 * Routes are automatically wrapped with the 'client' middleware group.
 *
 * Use this for authenticated namespace management pages:
 * - Bio/link editors
 * - Settings pages
 * - Analytics dashboards
 */
class ClientRoutesRegistering extends LifecycleEvent
{
    //
}
