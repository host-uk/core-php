<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Front;

use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\AggregateServiceProvider;

/**
 * Core front-end module - I/O translation layer.
 *
 * Seven frontages, each translating a transport protocol:
 *   Web        - HTTP → HTML (public marketing)
 *   Client     - HTTP → HTML (namespace owner dashboard)
 *   Admin      - HTTP → HTML (backend admin dashboard)
 *   Api        - HTTP → JSON (REST API)
 *   Mcp        - HTTP → JSON-RPC (MCP protocol)
 *   Cli        - Artisan commands (console context)
 *   Stdio      - stdin/stdout (CLI pipes, MCP stdio)
 *   Components - View namespaces (shared across HTTP frontages)
 */
class Boot extends AggregateServiceProvider
{
    protected $providers = [
        Web\Boot::class,
        Client\Boot::class,
        Admin\Boot::class,
        Api\Boot::class,
        Mcp\Boot::class,
        Cli\Boot::class,
        Stdio\Boot::class,
        Components\Boot::class,
    ];

    /**
     * Configure HTTP middleware - delegates to each HTTP frontage.
     * Stdio has no HTTP middleware (different transport).
     */
    public static function middleware(Middleware $middleware): void
    {
        Web\Boot::middleware($middleware);
        Client\Boot::middleware($middleware);
        Admin\Boot::middleware($middleware);
        Api\Boot::middleware($middleware);
        Mcp\Boot::middleware($middleware);
    }
}
