<?php

declare(strict_types=1);

namespace Core\Mod\Web\Middleware;

use Core\Mod\Web\Models\Domain;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves custom domains for BioHost pages.
 *
 * This middleware runs early in the request lifecycle to:
 * 1. Check if the request host is a verified custom domain
 * 2. Store the resolved domain in the request for downstream use
 * 3. Handle domain-level redirects (custom index URL, exclusive biolink)
 *
 * Note: This works alongside RestrictToBioDomain middleware which
 * handles access control. This middleware focuses on resolution and
 * making the domain available to controllers.
 */
class BioDomainResolver
{
    /**
     * Default biolink domains (not custom domains).
     */
    protected array $defaultDomains = [
        'link.host.uk.com',
        'bio.host.uk.com',
        'lnktr.fyi',
        'bio.host.test',
        'link.host.test',
        'localhost',
    ];

    /**
     * Cache TTL for domain lookups (in seconds).
     */
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Handle an incoming request.
     *
     * Resolves the custom domain (if any) and stores it in the request
     * for use by controllers and other middleware.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Skip resolution for default domains
        if (in_array($host, $this->defaultDomains, true)) {
            $request->attributes->set('biolink_domain', null);
            $request->attributes->set('biolink_domain_id', null);

            return $next($request);
        }

        // Look up custom domain with caching
        $domain = $this->resolveDomain($host);

        // Store domain in request for downstream use
        $request->attributes->set('biolink_domain', $domain);
        $request->attributes->set('biolink_domain_id', $domain?->id);

        // If no valid domain found, let the request continue
        // (RestrictToBioDomain will handle access control)
        if (! $domain) {
            return $next($request);
        }

        // Handle root path for custom domains
        if ($this->isRootPath($request)) {
            // Custom index URL redirect takes priority
            if ($domain->custom_index_url) {
                return redirect($domain->custom_index_url, 302);
            }

            // Exclusive domain - redirect to the biolink URL
            if ($domain->biolink_id && $domain->exclusiveLink) {
                return redirect('/'.$domain->exclusiveLink->url, 302);
            }
        }

        return $next($request);
    }

    /**
     * Resolve a custom domain from the database.
     */
    protected function resolveDomain(string $host): ?Domain
    {
        $cacheKey = "biolink_domain:{$host}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($host) {
            return Domain::where('host', $host)
                ->where('is_enabled', true)
                ->where('verification_status', 'verified')
                ->with('exclusiveLink')
                ->first();
        });
    }

    /**
     * Check if the request is for the root path.
     */
    protected function isRootPath(Request $request): bool
    {
        $path = $request->path();

        return $path === '/' || $path === '';
    }

    /**
     * Clear the domain cache for a specific host.
     */
    public static function clearCache(string $host): void
    {
        Cache::forget("biolink_domain:{$host}");
    }

    /**
     * Clear all domain caches (use sparingly).
     */
    public static function clearAllCache(): void
    {
        // Get all custom domains and clear their caches
        Domain::where('is_enabled', true)->each(function ($domain) {
            self::clearCache($domain->host);
        });
    }
}
