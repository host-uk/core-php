<?php

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired after the framework has fully booted.
 *
 * Use this for late-stage initialisation that needs the full
 * application context available. Most modules should use more
 * specific events (AdminPanelBooting, ApiRoutesRegistering, etc.)
 * rather than this general event.
 */
class FrameworkBooted extends LifecycleEvent
{
    //
}
