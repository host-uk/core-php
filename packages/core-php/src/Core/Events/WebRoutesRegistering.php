<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Events;

/**
 * Fired when web routes are being registered.
 *
 * Modules listen to this event to register public-facing web routes.
 * Routes are automatically wrapped with the 'web' middleware group.
 *
 * Use this for marketing pages, public product pages, etc.
 * For authenticated dashboard routes, use AdminPanelBooting instead.
 */
class WebRoutesRegistering extends LifecycleEvent
{
    //
}
