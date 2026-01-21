<?php

declare(strict_types=1);

namespace Core\Console;

use Core\Events\ConsoleBooting;

/**
 * Core Console module - registers framework artisan commands.
 */
class Boot
{
    public static array $listens = [
        ConsoleBooting::class => 'onConsole',
    ];

    public function onConsole(ConsoleBooting $event): void
    {
        $event->command(Commands\InstallCommand::class);
        $event->command(Commands\MakeModCommand::class);
        $event->command(Commands\MakePlugCommand::class);
        $event->command(Commands\MakeWebsiteCommand::class);
    }
}
