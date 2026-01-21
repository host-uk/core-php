<?php

declare(strict_types=1);

namespace Core\Mod\Web\Controllers\Web;

use Core\Front\Controller;
use Core\Mod\Web\Middleware\BioPasswordProtection;
use Core\Mod\Web\Middleware\BioSensitiveContent;
use Core\Mod\Web\Models\Domain;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\TargetingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for handling short link redirects.
 *
 * Supports:
 * - 301 (permanent) and 302 (temporary) redirects
 * - Link cloaking (iframe wrapper to hide destination)
 * - Deep linking (app:// schemes for mobile apps)
 * - Targeting rules (geo, device, browser, OS, language)
 * - Password protection
 * - Sensitive content warnings
 *
 * Settings are stored in bio.settings:
 * {
 *   "redirect_type": 302,           // 301 or 302
 *   "cloaking_enabled": false,      // true to use iframe cloaking
 *   "cloaking_title": "Page Title", // Title shown in cloaked page
 *   "deep_link_enabled": false,     // Enable app deep linking
 *   "deep_link_ios": "myapp://...", // iOS app URL
 *   "deep_link_android": "myapp://...", // Android app URL
 *   "deep_link_fallback": "https://...", // Web fallback if app not installed
 * }
 */
class RedirectController extends Controller
{
    /**
     * Cache TTL for biolink lookups (5 minutes).
     */
    protected const CACHE_TTL = 300;

    public function __construct(
        protected TargetingService $targetingService
    ) {}

    /**
     * Handle a short link redirect.
     */
    public function redirect(Request $request, string $url): Response
    {
        $domain = $this->resolveDomain($request);
        $domainId = $domain?->id;

        // Look up the biolink
        $cacheKey = "shortlink:{$domainId}:{$url}";
        $biolink = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domainId, $url) {
            return Page::where('domain_id', $domainId)
                ->where('url', $url)
                ->where('type', 'link')
                ->active()
                ->first();
        });

        if (! $biolink) {
            return $this->notFound($domain);
        }

        // Store biolink in request for middleware access
        $request->attributes->set('biolink', $biolink);

        // Check targeting rules
        $targetingResult = $this->targetingService->evaluate($biolink, $request);
        if (! $targetingResult['matches']) {
            return $this->handleTargetingFailure($biolink, $targetingResult);
        }

        // Check password protection
        if ($this->requiresPassword($biolink, $request)) {
            return $this->showPasswordForm($biolink);
        }

        // Check sensitive content warning
        if ($this->requiresSensitiveWarning($biolink, $request)) {
            return app(BioSensitiveContent::class)->handle(
                $request,
                fn () => $this->performRedirect($biolink, $request)
            );
        }

        return $this->performRedirect($biolink, $request);
    }

    /**
     * Perform the actual redirect.
     */
    protected function performRedirect(Page $biolink, Request $request): Response
    {
        // Track the click asynchronously
        TrackClick::dispatch($biolink->id, null, $request);

        $locationUrl = $biolink->location_url;
        if (! $locationUrl) {
            return $this->notFound(null);
        }

        // Check for splash page
        $splashSettings = $biolink->getSetting('splash_page', []);
        if (! empty($splashSettings['enabled']) && ! $request->query('skip_splash')) {
            return $this->handleSplashPage($biolink, $splashSettings);
        }

        // Check for deep linking
        if ($biolink->getSetting('deep_link_enabled', false)) {
            return $this->handleDeepLink($biolink, $request);
        }

        // Check for cloaking
        if ($biolink->getSetting('cloaking_enabled', false)) {
            return $this->handleCloaking($biolink);
        }

        // Standard redirect
        $redirectType = (int) $biolink->getSetting('redirect_type', 302);
        $statusCode = $redirectType === 301 ? 301 : 302;

        return response('', $statusCode, [
            'Location' => $locationUrl,
            'Cache-Control' => $statusCode === 301 ? 'public, max-age=86400' : 'public, max-age=60',
        ]);
    }

    /**
     * Handle deep linking for mobile apps.
     *
     * Deep linking attempts to open the user's native app, falling back
     * to the web URL if the app is not installed.
     */
    protected function handleDeepLink(Page $biolink, Request $request): Response
    {
        $userAgent = strtolower($request->userAgent() ?? '');
        $iosLink = $biolink->getSetting('deep_link_ios');
        $androidLink = $biolink->getSetting('deep_link_android');
        $fallbackUrl = $biolink->getSetting('deep_link_fallback', $biolink->location_url);

        // Determine which deep link to use based on device
        $deepLink = null;
        if ($iosLink && preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            $deepLink = $iosLink;
        } elseif ($androidLink && preg_match('/android/i', $userAgent)) {
            $deepLink = $androidLink;
        }

        if (! $deepLink) {
            // No deep link for this device, redirect to fallback
            return response('', 302, [
                'Location' => $fallbackUrl,
                'Cache-Control' => 'public, max-age=60',
            ]);
        }

        // Render the deep link page which attempts to open the app
        return response()->view('webpage::web.deep-link', [
            'biolink' => $biolink,
            'deep_link' => $deepLink,
            'fallback_url' => $fallbackUrl,
            'timeout' => (int) $biolink->getSetting('deep_link_timeout', 2000),
        ], 200, [
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Handle splash page display.
     *
     * Splash pages show a branded interstitial before redirecting to the destination.
     */
    protected function handleSplashPage(Page $biolink, array $settings): Response
    {
        return response()->view('webpage::web.splash', [
            'biolink' => $biolink,
            'destination_url' => $biolink->location_url,
            'title' => $settings['title'] ?? '',
            'description' => $settings['description'] ?? '',
            'button_text' => $settings['button_text'] ?? 'Continue',
            'background_color' => $settings['background_color'] ?? '#ffffff',
            'text_color' => $settings['text_color'] ?? '#000000',
            'button_color' => $settings['button_color'] ?? '#3b82f6',
            'button_text_color' => $settings['button_text_color'] ?? '#ffffff',
            'logo_url' => $settings['logo_url'] ?? null,
            'auto_redirect_delay' => (int) ($settings['auto_redirect_delay'] ?? 5),
            'show_timer' => (bool) ($settings['show_timer'] ?? true),
        ], 200, [
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Handle link cloaking.
     *
     * Cloaking uses an iframe to load the destination URL while keeping
     * the short link URL in the browser's address bar.
     */
    protected function handleCloaking(Page $biolink): Response
    {
        return response()->view('webpage::web.cloaked', [
            'biolink' => $biolink,
            'destination_url' => $biolink->location_url,
            'title' => $biolink->getSetting('cloaking_title', $biolink->url),
        ], 200, [
            'X-Frame-Options' => 'SAMEORIGIN',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    /**
     * Handle password form submission.
     */
    public function verifyPassword(Request $request, string $url): Response
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $domain = $this->resolveDomain($request);
        $domainId = $domain?->id;

        $biolink = Page::where('domain_id', $domainId)
            ->where('url', $url)
            ->where('type', 'link')
            ->active()
            ->first();

        if (! $biolink) {
            return $this->notFound($domain);
        }

        $storedHash = $biolink->getSetting('password');

        if (! $storedHash || ! Hash::check($request->password, $storedHash)) {
            return $this->showPasswordForm($biolink, 'Incorrect password. Please try again.');
        }

        // Grant access via middleware
        app(BioPasswordProtection::class)->grantAccess($biolink, $request);

        // Store biolink in request and perform redirect
        $request->attributes->set('biolink', $biolink);

        return $this->performRedirect($biolink, $request);
    }

    /**
     * Handle sensitive content acknowledgment.
     */
    public function acknowledgeSensitiveContent(Request $request, string $url): Response
    {
        $request->validate([
            'age_confirmed' => 'sometimes|accepted',
        ]);

        $domain = $this->resolveDomain($request);
        $domainId = $domain?->id;

        $biolink = Page::where('domain_id', $domainId)
            ->where('url', $url)
            ->where('type', 'link')
            ->active()
            ->first();

        if (! $biolink) {
            return $this->notFound($domain);
        }

        // Check if age confirmation is required but not provided
        if ($biolink->getSetting('sensitive_age_gate', false) && ! $request->boolean('age_confirmed')) {
            return back()->withErrors(['age_confirmed' => 'You must confirm you are 18 or older.']);
        }

        // Acknowledge via middleware
        app(BioSensitiveContent::class)->acknowledge($biolink, $request);

        // Store biolink in request and perform redirect
        $request->attributes->set('biolink', $biolink);

        return $this->performRedirect($biolink, $request);
    }

    /**
     * Check if password is required and not yet verified.
     */
    protected function requiresPassword(Page $biolink, Request $request): bool
    {
        if (! $biolink->getSetting('password_protected', false)) {
            return false;
        }

        $sessionKey = 'biolink_access_'.$biolink->id;

        return ! $request->session()->has($sessionKey);
    }

    /**
     * Check if sensitive content warning should be shown.
     */
    protected function requiresSensitiveWarning(Page $biolink, Request $request): bool
    {
        if (! $biolink->getSetting('sensitive_content', false)) {
            return false;
        }

        $sessionKey = 'biolink_sensitive_'.$biolink->id;

        return ! $request->session()->has($sessionKey);
    }

    /**
     * Handle targeting rule failure.
     */
    protected function handleTargetingFailure(Page $biolink, array $result): Response
    {
        if (! empty($result['fallback_url'])) {
            return response('', 302, [
                'Location' => $result['fallback_url'],
                'Cache-Control' => 'no-store',
            ]);
        }

        $message = TargetingService::getReasonMessage($result['reason'] ?? 'unknown');

        return response()->view('webpage::web.not-available', [
            'biolink' => $biolink,
            'message' => $message,
            'reason' => $result['reason'],
        ], 403, [
            'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * Show password form for protected link.
     */
    protected function showPasswordForm(Page $biolink, ?string $error = null): Response
    {
        return response()->view('webpage::web.password', [
            'biolink' => $biolink,
            'error' => $error,
            'hint' => $biolink->getSetting('password_hint'),
        ], 200, [
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * Resolve the current domain from request.
     */
    protected function resolveDomain(Request $request): ?Domain
    {
        // Check if already resolved by middleware
        $domain = $request->attributes->get('biolink_domain');
        if ($domain !== null) {
            return $domain instanceof Domain ? $domain : null;
        }

        $host = $request->getHost();

        // Default domains don't need lookup
        if (in_array($host, ['bio.host.uk.com', 'lnktr.fyi', 'localhost'])) {
            return null;
        }

        return Cache::remember("domain:{$host}", 3600, function () use ($host) {
            return Domain::where('host', $host)
                ->where('is_enabled', true)
                ->first();
        });
    }

    /**
     * Handle 404 responses.
     */
    protected function notFound(?Domain $domain): Response
    {
        if ($domain?->custom_not_found_url) {
            return response('', 302, [
                'Location' => $domain->custom_not_found_url,
                'Cache-Control' => 'public, max-age=60',
            ]);
        }

        return response()->view('webpage::web.404', [], 404, [
            'Cache-Control' => 'public, max-age=60',
        ]);
    }
}
