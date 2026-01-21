<?php

declare(strict_types=1);

namespace Core\Mod\Web\Middleware;

use Core\Headers\DetectDevice;
use Core\Mod\Web\Models\Page;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to show content warnings for sensitive bio.
 *
 * This handles two types of sensitive content:
 * 1. Age-restricted content (18+) — requires age verification
 * 2. Content warnings — just an acknowledgment (e.g., flashing images, disturbing content)
 *
 * Settings are stored in bio.settings:
 * {
 *   "sensitive_content": true,
 *   "sensitive_type": "adult", // "adult", "violence", "medical", "other"
 *   "sensitive_message": "Custom warning message",
 *   "sensitive_age_gate": true, // Require age confirmation
 *   "sensitive_skip_for_referrers": ["twitter.com", "instagram.com"] // Optional bypass
 * }
 *
 * Session key: biolink_sensitive_{biolink_id}
 *
 * Note: For in-app browsers from social platforms (Instagram, TikTok, etc.),
 * the warning is ALWAYS shown to help protect creators from platform bans.
 */
class BioSensitiveContent
{
    /**
     * Default warning messages by type.
     */
    protected const DEFAULT_MESSAGES = [
        'adult' => 'This page contains adult content intended for viewers 18 years or older.',
        'violence' => 'This page may contain content depicting violence or graphic imagery.',
        'medical' => 'This page contains medical imagery that some viewers may find disturbing.',
        'flashing' => 'This page contains flashing lights or images that may trigger seizures in people with photosensitive epilepsy.',
        'other' => 'This page contains content that some viewers may find sensitive or objectionable.',
    ];

    public function __construct(
        protected DetectDevice $deviceDetection
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
            return $next($request);
        }

        // Check if sensitive content warning is enabled
        if (! $this->hasSensitiveContent($biolink)) {
            return $next($request);
        }

        // Check if user has already acknowledged the warning
        if ($this->hasAcknowledged($biolink, $request)) {
            // Check if we need to re-show for strict platforms
            if (! $this->shouldForceWarningForPlatform($request)) {
                return $next($request);
            }
        }

        // Check if referrer bypass is configured
        if ($this->canBypassByReferrer($biolink, $request)) {
            return $next($request);
        }

        // Show the sensitive content warning
        return $this->showWarning($biolink, $request);
    }

    /**
     * Check if the biolink has sensitive content flagged.
     */
    protected function hasSensitiveContent(Page $biolink): bool
    {
        return (bool) $biolink->getSetting('sensitive_content', false);
    }

    /**
     * Check if the user has already acknowledged the warning.
     */
    protected function hasAcknowledged(Page $biolink, Request $request): bool
    {
        $sessionKey = $this->getSessionKey($biolink);

        return $request->session()->has($sessionKey);
    }

    /**
     * Record acknowledgment in session.
     */
    public function acknowledge(Page $biolink, Request $request): void
    {
        $sessionKey = $this->getSessionKey($biolink);
        $request->session()->put($sessionKey, [
            'acknowledged_at' => now()->toIso8601String(),
            'age_verified' => $biolink->getSetting('sensitive_age_gate', false),
        ]);
    }

    /**
     * Get the session key for acknowledgment.
     */
    protected function getSessionKey(Page $biolink): string
    {
        return 'biolink_sensitive_'.$biolink->id;
    }

    /**
     * Check if we should force a warning for users coming from strict platforms.
     *
     * Social platforms like Instagram, TikTok, Facebook are very strict about
     * adult content. Always showing the warning protects creators from bans.
     */
    protected function shouldForceWarningForPlatform(Request $request): bool
    {
        $userAgent = $request->userAgent();

        // Always show warning in in-app browsers from strict platforms
        return $this->deviceDetection->isStrictContentPlatform($userAgent);
    }

    /**
     * Check if the referrer allows bypassing the warning.
     */
    protected function canBypassByReferrer(Page $biolink, Request $request): bool
    {
        $skipReferrers = $biolink->getSetting('sensitive_skip_for_referrers', []);

        if (empty($skipReferrers)) {
            return false;
        }

        $referrer = $request->header('Referer');
        if (! $referrer) {
            return false;
        }

        $referrerHost = parse_url($referrer, PHP_URL_HOST);
        if (! $referrerHost) {
            return false;
        }

        foreach ($skipReferrers as $allowedReferrer) {
            if (str_ends_with($referrerHost, $allowedReferrer)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Show the sensitive content warning page.
     */
    protected function showWarning(Page $biolink, Request $request): Response
    {
        $type = $biolink->getSetting('sensitive_type', 'other');
        $customMessage = $biolink->getSetting('sensitive_message');
        $message = $customMessage ?: (self::DEFAULT_MESSAGES[$type] ?? self::DEFAULT_MESSAGES['other']);
        $requiresAgeGate = (bool) $biolink->getSetting('sensitive_age_gate', false);

        // Check if viewing from in-app browser
        $inAppPlatform = $this->deviceDetection->getPlatformDisplayName($request->userAgent());
        $isInApp = $inAppPlatform !== null;

        return response()->view('lthn::bio.sensitive-content', [
            'biolink' => $biolink,
            'message' => $message,
            'type' => $type,
            'requires_age_gate' => $requiresAgeGate,
            'in_app_platform' => $inAppPlatform,
            'is_in_app' => $isInApp,
        ], 200, [
            // Prevent caching of warning pages
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * Get the default message for a content type.
     */
    public static function getDefaultMessage(string $type): string
    {
        return self::DEFAULT_MESSAGES[$type] ?? self::DEFAULT_MESSAGES['other'];
    }

    /**
     * Get all available content types.
     */
    public static function getContentTypes(): array
    {
        return [
            'adult' => 'Adult content (18+)',
            'violence' => 'Violence or graphic content',
            'medical' => 'Medical imagery',
            'flashing' => 'Flashing lights',
            'other' => 'Other sensitive content',
        ];
    }
}
