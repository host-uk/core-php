<?php

namespace Core\Mod\Web\Middleware;

use Core\Mod\Web\Models\Domain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToBioDomain
{
    /**
     * Allowed hosts for biolink pages.
     * Custom domains are also allowed (checked against database).
     */
    protected array $allowedHosts = [
        'lt.hn',              // Primary bio domain
        'link.host.uk.com',
        'bio.host.uk.com',
        'lnktr.fyi',          // Vanity domain for paid users
        'bio.host.test',
        'link.host.test',
        'localhost',
        '127.0.0.1',          // Browser tests
    ];

    /**
     * Handle an incoming request.
     *
     * Ensures biolink routes are only accessible from:
     * 1. Allowed subdomains (link.host.uk.com, bio.host.uk.com)
     * 2. Custom domains configured in the database
     *
     * For custom domains at root path, handles redirects/exclusive bio.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Check if host is in allowed list (exact match)
        if (in_array($host, $this->allowedHosts)) {
            return $next($request);
        }

        // Check if it's a verified custom domain
        $customDomain = Domain::where('host', $host)
            ->where('is_enabled', true)
            ->first();

        if ($customDomain) {
            // Handle root path for custom domains
            if ($request->path() === '/') {
                // Custom index URL redirect
                if ($customDomain->custom_index_url) {
                    return redirect($customDomain->custom_index_url);
                }

                // Exclusive domain - redirect to the biolink URL
                if ($customDomain->biolink_id && $customDomain->exclusiveLink) {
                    return redirect('/'.$customDomain->exclusiveLink->url);
                }

                // No exclusive link or custom index - 404
                abort(404);
            }

            return $next($request);
        }

        // Not a valid biolink domain - return 404
        abort(404);
    }
}
