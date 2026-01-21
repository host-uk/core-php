<?php

declare(strict_types=1);

namespace Core\Mod\Web\Middleware;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\TargetingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to evaluate targeting rules before rendering bio.
 *
 * This middleware checks geo, device, browser, OS, and language targeting
 * rules configured on the bio. If the request doesn't match the rules,
 * the user is either redirected to a fallback URL or shown a "not available"
 * page.
 *
 * Usage: Apply to biolink public routes.
 *
 * Targeting settings are stored in bio.settings.targeting:
 * {
 *   "countries": ["GB", "US"],
 *   "exclude_countries": ["RU", "CN"],
 *   "devices": ["mobile", "desktop"],
 *   "browsers": ["Chrome", "Safari"],
 *   "operating_systems": ["iOS", "macOS"],
 *   "languages": ["en", "es"],
 *   "fallback_url": "https://example.com/not-available"
 * }
 */
class BioTargeting
{
    public function __construct(
        protected TargetingService $targetingService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the biolink from the request (set by controller or earlier middleware)
        $biolink = $request->attributes->get('biolink');

        if (! $biolink instanceof Page) {
            // No biolink resolved yet, let the request continue
            // The controller will handle 404 if needed
            return $next($request);
        }

        // Check if targeting is enabled
        $targeting = $biolink->getSetting('targeting', []);
        if (empty($targeting) || empty($targeting['enabled'])) {
            return $next($request);
        }

        // Evaluate targeting rules
        $result = $this->targetingService->evaluate($biolink, $request);

        if ($result['matches']) {
            return $next($request);
        }

        // Targeting rules not matched - handle the failure
        return $this->handleTargetingFailure($biolink, $result, $request);
    }

    /**
     * Handle a targeting rule failure.
     */
    protected function handleTargetingFailure(
        Page $biolink,
        array $result,
        Request $request
    ): Response {
        // If a fallback URL is configured, redirect there
        if (! empty($result['fallback_url'])) {
            return redirect()->away($result['fallback_url'], 302);
        }

        // Otherwise show the "not available" page
        $message = TargetingService::getReasonMessage($result['reason'] ?? 'unknown');

        return response()->view('lthn::bio.not-available', [
            'biolink' => $biolink,
            'message' => $message,
            'reason' => $result['reason'],
        ], 403);
    }

    /**
     * Get the targeting evaluation result for a bio.
     *
     * Useful for controllers that need to check targeting without applying middleware.
     */
    public function evaluateTargeting(Page $biolink, Request $request): array
    {
        return $this->targetingService->evaluate($biolink, $request);
    }
}
