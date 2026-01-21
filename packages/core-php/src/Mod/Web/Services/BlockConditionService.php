<?php

namespace Core\Mod\Web\Services;

use Core\Mod\Web\Models\Block;
use Core\Headers\DetectDevice;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Block conditional display service.
 *
 * Determines whether a block should be displayed based on:
 * - Schedule (start_date, end_date)
 * - Device type (mobile, desktop, tablet)
 * - Country/region (geo targeting)
 * - Browser, OS, language
 *
 * Conditions are stored in block settings.conditions as JSON:
 * {
 *   "schedule": {"start": "2024-01-01", "end": "2024-12-31"},
 *   "devices": ["mobile", "tablet"],
 *   "countries": ["GB", "US", "CA"],
 *   "exclude_countries": ["RU", "CN"],
 *   "browsers": ["Chrome", "Safari"],
 *   "operating_systems": ["iOS", "Android"],
 *   "languages": ["en", "es"]
 * }
 */
class BlockConditionService
{
    protected DetectDevice $deviceDetection;

    public function __construct(DetectDevice $deviceDetection)
    {
        $this->deviceDetection = $deviceDetection;
    }

    /**
     * Determine if a block should be displayed.
     */
    public function shouldDisplay(Block $block, Request $request): bool
    {
        // Block must be enabled first
        if (! $block->is_enabled) {
            return false;
        }

        // Check schedule (uses block-level start_date/end_date)
        if (! $this->checkSchedule($block)) {
            return false;
        }

        // Get conditions from settings
        $conditions = $block->getSetting('conditions', []);

        if (empty($conditions)) {
            return true; // No conditions = always show
        }

        // Check all conditions (AND logic - all must pass)
        return $this->checkDevices($conditions, $request)
            && $this->checkCountry($conditions, $request)
            && $this->checkBrowser($conditions, $request)
            && $this->checkOperatingSystem($conditions, $request)
            && $this->checkLanguage($conditions, $request)
            && $this->checkCustomSchedule($conditions);
    }

    /**
     * Check block-level schedule (start_date, end_date columns).
     */
    protected function checkSchedule(Block $block): bool
    {
        $now = now();

        if ($block->start_date && $now->lt($block->start_date)) {
            return false;
        }

        if ($block->end_date && $now->gt($block->end_date)) {
            return false;
        }

        return true;
    }

    /**
     * Check custom schedule in conditions (more flexible than block columns).
     */
    protected function checkCustomSchedule(array $conditions): bool
    {
        if (! isset($conditions['schedule'])) {
            return true;
        }

        $schedule = $conditions['schedule'];
        $now = now();

        // Date range
        if (isset($schedule['start']) && $now->lt(Carbon::parse($schedule['start']))) {
            return false;
        }

        if (isset($schedule['end']) && $now->gt(Carbon::parse($schedule['end'])->endOfDay())) {
            return false;
        }

        // Time of day
        if (isset($schedule['time_start']) && isset($schedule['time_end'])) {
            $timeNow = $now->format('H:i');
            if ($timeNow < $schedule['time_start'] || $timeNow > $schedule['time_end']) {
                return false;
            }
        }

        // Days of week (0 = Sunday, 6 = Saturday)
        if (isset($schedule['days']) && is_array($schedule['days'])) {
            if (! in_array($now->dayOfWeek, $schedule['days'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check device type conditions.
     */
    protected function checkDevices(array $conditions, Request $request): bool
    {
        if (! isset($conditions['devices']) || empty($conditions['devices'])) {
            return true;
        }

        $userAgent = $request->userAgent();
        $deviceType = $this->deviceDetection->detectDeviceType($userAgent);

        return in_array($deviceType, $conditions['devices']);
    }

    /**
     * Check country/geo conditions.
     */
    protected function checkCountry(array $conditions, Request $request): bool
    {
        // Get country code from CDN headers
        $country = $this->getCountryFromRequest($request);

        if (! $country) {
            // If we can't determine country, default to showing
            // (unless exclude_countries without countries is set)
            return ! isset($conditions['countries']) || empty($conditions['countries']);
        }

        // Check exclusion list first
        if (isset($conditions['exclude_countries']) && is_array($conditions['exclude_countries'])) {
            if (in_array($country, $conditions['exclude_countries'])) {
                return false;
            }
        }

        // Check inclusion list
        if (isset($conditions['countries']) && is_array($conditions['countries']) && ! empty($conditions['countries'])) {
            return in_array($country, $conditions['countries']);
        }

        return true;
    }

    /**
     * Check browser conditions.
     */
    protected function checkBrowser(array $conditions, Request $request): bool
    {
        if (! isset($conditions['browsers']) || empty($conditions['browsers'])) {
            return true;
        }

        $userAgent = $request->userAgent();
        $browser = $this->deviceDetection->detectBrowser($userAgent);

        if (! $browser) {
            return true; // Can't detect = show
        }

        return in_array($browser, $conditions['browsers']);
    }

    /**
     * Check operating system conditions.
     */
    protected function checkOperatingSystem(array $conditions, Request $request): bool
    {
        if (! isset($conditions['operating_systems']) || empty($conditions['operating_systems'])) {
            return true;
        }

        $userAgent = $request->userAgent();
        $os = $this->deviceDetection->detectOs($userAgent);

        if (! $os) {
            return true; // Can't detect = show
        }

        return in_array($os, $conditions['operating_systems']);
    }

    /**
     * Check language conditions (Accept-Language header).
     */
    protected function checkLanguage(array $conditions, Request $request): bool
    {
        if (! isset($conditions['languages']) || empty($conditions['languages'])) {
            return true;
        }

        $acceptLanguage = $request->header('Accept-Language');

        if (! $acceptLanguage) {
            return true;
        }

        // Parse Accept-Language header (e.g., "en-GB,en;q=0.9,es;q=0.8")
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $part) {
            $lang = trim(explode(';', $part)[0]);
            // Get just the primary language code (en-GB -> en)
            $primary = explode('-', $lang)[0];
            $languages[] = strtolower($primary);
        }

        // Check if any accepted language matches
        foreach ($conditions['languages'] as $allowed) {
            if (in_array(strtolower($allowed), $languages)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get country code from request (CDN headers).
     */
    protected function getCountryFromRequest(Request $request): ?string
    {
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

        // Filter out unknown/invalid
        if ($country === 'XX' || $country === 'T1') {
            return null;
        }

        return $country ? strtoupper($country) : null;
    }

    /**
     * Get available condition options for the editor UI.
     */
    public static function getConditionOptions(): array
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
            'days_of_week' => [
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
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
            'AE' => 'UAE',
            'SA' => 'Saudi Arabia',
            'ZA' => 'South Africa',
        ];
    }
}
