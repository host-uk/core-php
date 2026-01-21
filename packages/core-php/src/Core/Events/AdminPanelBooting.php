<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when the admin panel is being bootstrapped.
 *
 * Modules listen to this event to register:
 * - Admin navigation items
 * - Admin routes (wrapped with admin middleware)
 * - Admin view namespaces
 * - Admin Livewire components
 *
 * Only fired for requests to admin routes, not public pages or API calls.
 */
class AdminPanelBooting extends LifecycleEvent
{
    //
}
