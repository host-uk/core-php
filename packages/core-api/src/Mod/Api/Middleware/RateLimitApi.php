<?php

declare(strict_types=1);

namespace Core\Mod\Api\Middleware;

use Core\Mod\Api\Models\ApiKey;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limit API requests based on API key or user.
 *
 * Rate limits are configured in config/api.php and can be tier-based.
 *
 * Register in bootstrap/app.php:
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->alias([
 *           'api.ratelimit' => \App\Http\Middleware\Api\RateLimitApi::class,
 *       ]);
 *   })
 */
class RateLimitApi
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->resolveRateLimitKey($request);
        $limits = $this->resolveRateLimits($request);

        $maxAttempts = $limits['requests'];
        $decayMinutes = $limits['per_minutes'];

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        $response = $next($request);

        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve the rate limit key for the request.
     */
    protected function resolveRateLimitKey(Request $request): string
    {
        $apiKey = $request->attributes->get('api_key');

        if ($apiKey instanceof ApiKey) {
            return 'api_key:'.$apiKey->id;
        }

        if ($request->user()) {
            return 'user:'.$request->user()->id;
        }

        return 'ip:'.$request->ip();
    }

    /**
     * Resolve rate limits based on authentication and tier.
     */
    protected function resolveRateLimits(Request $request): array
    {
        $apiKey = $request->attributes->get('api_key');

        // Default limits for unauthenticated requests
        $default = config('api.rate_limits.default', [
            'requests' => 60,
            'per_minutes' => 1,
        ]);

        if (! $apiKey && ! $request->user()) {
            return $default;
        }

        // Authenticated limits
        $authenticated = config('api.rate_limits.authenticated', [
            'requests' => 1000,
            'per_minutes' => 1,
        ]);

        // Check for tier-based limits via workspace/entitlements
        $workspace = $request->attributes->get('workspace');
        if ($workspace) {
            $tier = $this->getWorkspaceTier($workspace);
            $tierLimits = config("api.rate_limits.by_tier.{$tier}");
            if ($tierLimits) {
                return $tierLimits;
            }
        }

        return $authenticated;
    }

    /**
     * Get workspace tier for rate limiting.
     *
     * Integrates with EntitlementService for tier detection.
     */
    protected function getWorkspaceTier($workspace): string
    {
        // Check if workspace has an active package
        $package = $workspace->activePackages()->first();

        return $package?->slug ?? 'starter';
    }

    /**
     * Build response for too many attempts.
     */
    protected function buildTooManyAttemptsResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        return response()->json([
            'error' => 'rate_limit_exceeded',
            'message' => 'Too many requests. Please slow down.',
            'retry_after' => $retryAfter,
        ], 429)->withHeaders([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
        ]);
    }

    /**
     * Add rate limit headers to response.
     */
    protected function addRateLimitHeaders(Response $response, int $maxAttempts, int $remaining): Response
    {
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', (string) now()->addMinute()->timestamp);

        return $response;
    }

    /**
     * Calculate remaining attempts.
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->remaining($key, $maxAttempts);
    }
}
