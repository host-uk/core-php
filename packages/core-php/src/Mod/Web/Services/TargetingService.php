<?php

declare(strict_types=1);

namespace Core\Mod\Web\Services;

use Core\Mod\Web\Models\Page;
use Core\Headers\DetectDevice;
use Illuminate\Http\Request;

/**
 * Targeting service for BioLinks.
 *
 * Evaluates targeting rules for entire biolinks (not just blocks).
 * Used by middleware to gate access based on:
 * - Geographic location (country)
 * - Device type (mobile, desktop, tablet)
 * - Browser (Chrome, Firefox, Safari, etc.)
 * - Operating system (Windows, macOS, iOS, Android, etc.)
 * - Language (Accept-Language header)
 *
 * Targeting rules are stored in biolink settings.targeting as JSON:
 * {
 *   "countries": ["GB", "US", "CA"],
 *   "exclude_countries": ["RU", "CN"],
 *   "devices": ["mobile", "desktop"],
 *   "browsers": ["Chrome", "Safari"],
 *   "operating_systems": ["iOS", "Android"],
 *   "languages": ["en", "es"],
 *   "fallback_url": "https://example.com/not-available"
 * }
 */
class TargetingService
{
    public function __construct(
        protected DetectDevice $deviceDetection
    ) {}

    /**
     * Check if a request matches the biolink's targeting rules.
     *
     * @return array{matches: bool, reason: ?string, fallback_url: ?string}
     */
    public function evaluate(Page $biolink, Request $request): array
    {
        $targeting = $biolink->getSetting('targeting', []);

        // No targeting rules = everyone allowed
        if (empty($targeting)) {
            return [
                'matches' => true,
                'reason' => null,
                'fallback_url' => null,
            ];
        }

        $fallbackUrl = $targeting['fallback_url'] ?? null;

        // Check each rule type - all must pass (AND logic)
        $checks = [
            'country' => $this->checkCountry($targeting, $request),
            'device' => $this->checkDevice($targeting, $request),
            'browser' => $this->checkBrowser($targeting, $request),
            'os' => $this->checkOperatingSystem($targeting, $request),
            'language' => $this->checkLanguage($targeting, $request),
        ];

        foreach ($checks as $type => $result) {
            if (! $result['pass']) {
                return [
                    'matches' => false,
                    'reason' => $result['reason'],
                    'fallback_url' => $fallbackUrl,
                ];
            }
        }

        return [
            'matches' => true,
            'reason' => null,
            'fallback_url' => null,
        ];
    }

    /**
     * Check if the request matches all targeting rules.
     */
    public function matches(Page $biolink, Request $request): bool
    {
        return $this->evaluate($biolink, $request)['matches'];
    }

    /**
     * Check country/geo targeting.
     *
     * @return array{pass: bool, reason: ?string}
     */
    protected function checkCountry(array $targeting, Request $request): array
    {
        $country = $this->getCountryFromRequest($request);

        // Check exclusion list first
        if (! empty($targeting['exclude_countries'])) {
            if ($country && in_array($country, $targeting['exclude_countries'], true)) {
                return [
                    'pass' => false,
                    'reason' => 'country_excluded',
                ];
            }
        }

        // Check inclusion list (if specified)
        if (! empty($targeting['countries'])) {
            if (! $country) {
                // Can't determine country, fail safe by allowing
                // (operator can use exclude_countries for strict blocking)
                return ['pass' => true, 'reason' => null];
            }

            if (! in_array($country, $targeting['countries'], true)) {
                return [
                    'pass' => false,
                    'reason' => 'country_not_allowed',
                ];
            }
        }

        return ['pass' => true, 'reason' => null];
    }

    /**
     * Check device type targeting.
     *
     * @return array{pass: bool, reason: ?string}
     */
    protected function checkDevice(array $targeting, Request $request): array
    {
        if (empty($targeting['devices'])) {
            return ['pass' => true, 'reason' => null];
        }

        $deviceType = $this->deviceDetection->detectDeviceType($request->userAgent());

        if (! in_array($deviceType, $targeting['devices'], true)) {
            return [
                'pass' => false,
                'reason' => 'device_not_allowed',
            ];
        }

        return ['pass' => true, 'reason' => null];
    }

    /**
     * Check browser targeting.
     *
     * @return array{pass: bool, reason: ?string}
     */
    protected function checkBrowser(array $targeting, Request $request): array
    {
        if (empty($targeting['browsers'])) {
            return ['pass' => true, 'reason' => null];
        }

        $browser = $this->deviceDetection->detectBrowser($request->userAgent());

        if (! $browser) {
            // Can't detect browser, allow by default
            return ['pass' => true, 'reason' => null];
        }

        if (! in_array($browser, $targeting['browsers'], true)) {
            return [
                'pass' => false,
                'reason' => 'browser_not_allowed',
            ];
        }

        return ['pass' => true, 'reason' => null];
    }

    /**
     * Check operating system targeting.
     *
     * @return array{pass: bool, reason: ?string}
     */
    protected function checkOperatingSystem(array $targeting, Request $request): array
    {
        if (empty($targeting['operating_systems'])) {
            return ['pass' => true, 'reason' => null];
        }

        $os = $this->deviceDetection->detectOs($request->userAgent());

        if (! $os) {
            // Can't detect OS, allow by default
            return ['pass' => true, 'reason' => null];
        }

        if (! in_array($os, $targeting['operating_systems'], true)) {
            return [
                'pass' => false,
                'reason' => 'os_not_allowed',
            ];
        }

        return ['pass' => true, 'reason' => null];
    }

    /**
     * Check language targeting (Accept-Language header).
     *
     * @return array{pass: bool, reason: ?string}
     */
    protected function checkLanguage(array $targeting, Request $request): array
    {
        if (empty($targeting['languages'])) {
            return ['pass' => true, 'reason' => null];
        }

        $acceptLanguage = $request->header('Accept-Language');

        if (! $acceptLanguage) {
            // No language header, allow by default
            return ['pass' => true, 'reason' => null];
        }

        // Parse Accept-Language header (e.g., "en-GB,en;q=0.9,es;q=0.8")
        $userLanguages = $this->parseAcceptLanguage($acceptLanguage);

        // Check if any user language matches allowed languages
        foreach ($targeting['languages'] as $allowed) {
            $allowedLower = strtolower($allowed);
            if (in_array($allowedLower, $userLanguages, true)) {
                return ['pass' => true, 'reason' => null];
            }
        }

        return [
            'pass' => false,
            'reason' => 'language_not_allowed',
        ];
    }

    /**
     * Parse Accept-Language header into array of language codes.
     *
     * @return array<string> Lowercase primary language codes (e.g., ['en', 'es', 'de'])
     */
    protected function parseAcceptLanguage(string $header): array
    {
        $languages = [];

        foreach (explode(',', $header) as $part) {
            $lang = trim(explode(';', $part)[0]);
            // Get primary language code (en-GB -> en)
            $primary = explode('-', $lang)[0];
            $languages[] = strtolower($primary);
        }

        return array_unique($languages);
    }

    /**
     * Get country code from request (CDN headers or IP lookup).
     */
    protected function getCountryFromRequest(Request $request): ?string
    {
        // Try CDN headers first (most reliable and fastest)

        // Cloudflare
        $country = $request->header('CF-IPCountry');

        // Bunny CDN
        if (! $country) {
            $country = $request->header('X-Country-Code');
        }

        // AWS CloudFront
        if (! $country) {
            $country = $request->header('CloudFront-Viewer-Country');
        }

        // Fastly
        if (! $country) {
            $country = $request->header('Fastly-Geo-Country-Code');
        }

        // Vercel
        if (! $country) {
            $country = $request->header('X-Vercel-IP-Country');
        }

        // Filter out unknown/invalid values
        if ($country === 'XX' || $country === 'T1' || $country === '') {
            return null;
        }

        return $country ? strtoupper($country) : null;
    }

    /**
     * Get human-readable message for a targeting failure reason.
     */
    public static function getReasonMessage(string $reason): string
    {
        return match ($reason) {
            'country_excluded' => 'This content is not available in your region.',
            'country_not_allowed' => 'This content is not available in your region.',
            'device_not_allowed' => 'This content is not available on your device type.',
            'browser_not_allowed' => 'This content is not available in your browser.',
            'os_not_allowed' => 'This content is not available on your operating system.',
            'language_not_allowed' => 'This content is not available in your language.',
            default => 'This content is not available.',
        };
    }

    /**
     * Get available targeting options for the editor UI.
     */
    public static function getTargetingOptions(): array
    {
        return [
            'devices' => [
                'desktop' => 'Desktop',
                'mobile' => 'Mobile',
                'tablet' => 'Tablet',
            ],
            'browsers' => [
                'Chrome' => 'Chrome',
                'Firefox' => 'Firefox',
                'Safari' => 'Safari',
                'Edge' => 'Edge',
                'Opera' => 'Opera',
                'Brave' => 'Brave',
                'Samsung Browser' => 'Samsung Browser',
            ],
            'operating_systems' => [
                'Windows 10' => 'Windows 10',
                'Windows 11' => 'Windows 11',
                'macOS' => 'macOS',
                'iOS' => 'iOS',
                'Android' => 'Android',
                'Linux' => 'Linux',
                'Chrome OS' => 'Chrome OS',
            ],
            'languages' => [
                'en' => 'English',
                'es' => 'Spanish',
                'fr' => 'French',
                'de' => 'German',
                'it' => 'Italian',
                'pt' => 'Portuguese',
                'nl' => 'Dutch',
                'ru' => 'Russian',
                'ja' => 'Japanese',
                'ko' => 'Korean',
                'zh' => 'Chinese',
                'ar' => 'Arabic',
                'hi' => 'Hindi',
                'pl' => 'Polish',
                'tr' => 'Turkish',
                'sv' => 'Swedish',
                'da' => 'Danish',
                'no' => 'Norwegian',
                'fi' => 'Finnish',
            ],
        ];
    }

    /**
     * Get list of common countries for geo targeting UI.
     */
    public static function getCommonCountries(): array
    {
        return [
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
            'IE' => 'Ireland',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'PT' => 'Portugal',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'AT' => 'Austria',
            'CH' => 'Switzerland',
            'PL' => 'Poland',
            'BR' => 'Brazil',
            'MX' => 'Mexico',
            'AR' => 'Argentina',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'SG' => 'Singapore',
            'HK' => 'Hong Kong',
            'IN' => 'India',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'ZA' => 'South Africa',
            'RU' => 'Russia',
            'CN' => 'China',
        ];
    }
}
