<?php

declare(strict_types=1);

namespace Core;

use Illuminate\Http\Request;

/**
 * Application initialisation - the true entry point.
 *
 * Use this in public/index.php:
 *
 *     require __DIR__.'/../vendor/autoload.php';
 *     Core\Init::handle();
 *
 * This replaces Laravel's bootstrap/app.php pattern with
 * explicit provider loading via Core\Boot.
 */
class Init
{
    /**
     * Handle the incoming request.
     */
    public static function handle(): void
    {
        // Maintenance mode check (before anything else)
        $maintenance = dirname(__DIR__, 4).'/storage/framework/maintenance.php';
        if (file_exists($maintenance)) {
            require $maintenance;
        }

        // Capture request and hand to Laravel
        $request = Request::capture();
        Boot::app()->handleRequest($request);
    }

    /**
     * Handle for testing - returns response instead of sending.
     */
    public static function handleForTesting(): mixed
    {
        $request = Request::capture();

        return Boot::app()->handle($request);
    }
}
