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
 * Fired when running in console/CLI context.
 *
 * Modules listen to this event to register Artisan commands.
 * Commands are registered via the command() method inherited
 * from LifecycleEvent.
 *
 * Only fired when running artisan commands, not web requests.
 */
class ConsoleBooting extends LifecycleEvent
{
    //
}
