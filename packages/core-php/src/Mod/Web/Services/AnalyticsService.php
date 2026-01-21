<?php

namespace Core\Mod\Web\Services;

use Core\Mod\Web\Models\Click;
use Core\Mod\Web\Models\ClickStat;
use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service for querying and aggregating BioLink analytics data.
 *
 * Provides methods for retrieving click statistics, geographic breakdowns,
 * device analytics, referrer tracking, and UTM campaign data.
 */
class AnalyticsService
{
    /**
     * Default analytics retention days when no entitlement is available.
     */
    protected const DEFAULT_RETENTION_DAYS = 30;

    /**
     * Get the analytics retention days for a workspace.
     */
    public function getRetentionDays(?Workspace $workspace): int
    {
        if (! $workspace) {
            return self::DEFAULT_RETENTION_DAYS;
        }

        $entitlements = app(EntitlementService::class);
        $result = $entitlements->can($workspace, 'bio.analytics_days');

        if ($result->isDenied()) {
            return self::DEFAULT_RETENTION_DAYS;
        }

        // The limit value is the number of days
        return $result->limit ?? self::DEFAULT_RETENTION_DAYS;
    }

    /**
     * Enforce date retention limits on a date range.
     *
     * Returns an array with:
     * - start: The enforced start date (may be later than requested)
     * - end: The end date (unchanged)
     * - limited: Whether the start date was limited
     * - max_days: The maximum allowed days
     */
    public function enforceDateRetention(
        Carbon $start,
        Carbon $end,
        ?Workspace $workspace
    ): array {
        $maxDays = $this->getRetentionDays($workspace);
        $minAllowedDate = now()->subDays($maxDays)->startOfDay();

        $limited = $start->lt($minAllowedDate);
        $enforcedStart = $limited ? $minAllowedDate : $start;

        return [
            'start' => $enforcedStart,
            'end' => $end,
            'limited' => $limited,
            'max_days' => $maxDays,
        ];
    }

    /**
     * Get summary statistics for a biolink within a date range.
     */
    public function getSummary(Page $biolink, Carbon $start, Carbon $end): array
    {
        // Try aggregated stats first (faster)
        $stats = ClickStat::where('biolink_id', $biolink->id)
            ->dailyTotals()
            ->dateRange($start->toDateString(), $end->toDateString())
            ->whereNull('country_code')
            ->whereNull('device_type')
            ->whereNull('referrer_host')
            ->whereNull('utm_source')
            ->selectRaw('SUM(clicks) as total_clicks, SUM(unique_clicks) as total_unique')
            ->first();

        if ($stats && ($stats->total_clicks > 0 || $stats->total_unique > 0)) {
            return [
                'clicks' => (int) $stats->total_clicks,
                'unique_clicks' => (int) $stats->total_unique,
            ];
        }

        // Fall back to raw clicks table
        $raw = Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->selectRaw('COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->first();

        return [
            'clicks' => (int) ($raw->clicks ?? 0),
            'unique_clicks' => (int) ($raw->unique_clicks ?? 0),
        ];
    }

    /**
     * Get clicks over time (daily trend).
     */
    public function getClicksOverTime(Page $biolink, Carbon $start, Carbon $end): array
    {
        // Try aggregated stats first
        $stats = ClickStat::where('biolink_id', $biolink->id)
            ->dailyTotals()
            ->dateRange($start->toDateString(), $end->toDateString())
            ->whereNull('country_code')
            ->whereNull('device_type')
            ->whereNull('referrer_host')
            ->whereNull('utm_source')
            ->selectRaw('date, SUM(clicks) as clicks, SUM(unique_clicks) as unique_clicks')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        if ($stats->isNotEmpty()) {
            return $this->fillDateGaps($stats, $start, $end);
        }

        // Fall back to raw clicks
        $raw = Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->fillDateGaps($raw, $start, $end);
    }

    /**
     * Fill in missing dates with zero values.
     */
    protected function fillDateGaps(Collection $data, Carbon $start, Carbon $end): array
    {
        $dateMap = $data->keyBy(fn ($item) => Carbon::parse($item->date)->format('Y-m-d'));

        $labels = [];
        $clicks = [];
        $uniqueClicks = [];

        $current = $start->copy()->startOfDay();
        $endDate = $end->copy()->endOfDay();

        while ($current <= $endDate) {
            $key = $current->format('Y-m-d');
            $labels[] = $current->format('M j');

            if ($dateMap->has($key)) {
                $clicks[] = (int) $dateMap[$key]->clicks;
                $uniqueClicks[] = (int) $dateMap[$key]->unique_clicks;
            } else {
                $clicks[] = 0;
                $uniqueClicks[] = 0;
            }

            $current->addDay();
        }

        return [
            'labels' => $labels,
            'clicks' => $clicks,
            'unique_clicks' => $uniqueClicks,
        ];
    }

    /**
     * Get clicks grouped by country.
     */
    public function getClicksByCountry(Page $biolink, Carbon $start, Carbon $end, int $limit = 10): array
    {
        // Try aggregated stats
        $stats = ClickStat::where('biolink_id', $biolink->id)
            ->dailyTotals()
            ->dateRange($start->toDateString(), $end->toDateString())
            ->whereNotNull('country_code')
            ->selectRaw('country_code, SUM(clicks) as clicks, SUM(unique_clicks) as unique_clicks')
            ->groupBy('country_code')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get();

        if ($stats->isNotEmpty()) {
            return $stats->map(fn ($s) => [
                'country_code' => $s->country_code,
                'country_name' => $this->getCountryName($s->country_code),
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])->toArray();
        }

        // Fall back to raw clicks
        return Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->whereNotNull('country_code')
            ->selectRaw('country_code, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('country_code')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'country_code' => $s->country_code,
                'country_name' => $this->getCountryName($s->country_code),
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])
            ->toArray();
    }

    /**
     * Get clicks grouped by device type.
     */
    public function getClicksByDevice(Page $biolink, Carbon $start, Carbon $end): array
    {
        // Try aggregated stats
        $stats = ClickStat::where('biolink_id', $biolink->id)
            ->dailyTotals()
            ->dateRange($start->toDateString(), $end->toDateString())
            ->whereNotNull('device_type')
            ->selectRaw('device_type, SUM(clicks) as clicks, SUM(unique_clicks) as unique_clicks')
            ->groupBy('device_type')
            ->orderByDesc('clicks')
            ->get();

        if ($stats->isNotEmpty()) {
            return $stats->map(fn ($s) => [
                'device_type' => $s->device_type,
                'label' => ucfirst($s->device_type ?? 'Unknown'),
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])->toArray();
        }

        // Fall back to raw clicks
        return Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->selectRaw('device_type, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('device_type')
            ->orderByDesc('clicks')
            ->get()
            ->map(fn ($s) => [
                'device_type' => $s->device_type,
                'label' => ucfirst($s->device_type ?? 'Unknown'),
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])
            ->toArray();
    }

    /**
     * Get clicks grouped by browser.
     */
    public function getClicksByBrowser(Page $biolink, Carbon $start, Carbon $end, int $limit = 5): array
    {
        // Browser is only in raw clicks table
        return Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->whereNotNull('browser_name')
            ->selectRaw('browser_name, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('browser_name')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'browser' => $s->browser_name,
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])
            ->toArray();
    }

    /**
     * Get clicks grouped by operating system.
     */
    public function getClicksByOs(Page $biolink, Carbon $start, Carbon $end, int $limit = 5): array
    {
        return Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->whereNotNull('os_name')
            ->selectRaw('os_name, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('os_name')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'os' => $s->os_name,
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])
            ->toArray();
    }

    /**
     * Get clicks grouped by referrer.
     */
    public function getClicksByReferrer(Page $biolink, Carbon $start, Carbon $end, int $limit = 10): array
    {
        // Try aggregated stats
        $stats = ClickStat::where('biolink_id', $biolink->id)
            ->dailyTotals()
            ->dateRange($start->toDateString(), $end->toDateString())
            ->whereNotNull('referrer_host')
            ->selectRaw('referrer_host, SUM(clicks) as clicks, SUM(unique_clicks) as unique_clicks')
            ->groupBy('referrer_host')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get();

        if ($stats->isNotEmpty()) {
            return $stats->map(fn ($s) => [
                'referrer' => $s->referrer_host,
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])->toArray();
        }

        // Fall back to raw clicks - include direct visits
        $withReferrer = Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->whereNotNull('referrer_host')
            ->where('referrer_host', '!=', '')
            ->selectRaw('referrer_host, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('referrer_host')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'referrer' => $s->referrer_host,
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])
            ->toArray();

        // Add direct visits
        $direct = Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->where(fn ($q) => $q->whereNull('referrer_host')->orWhere('referrer_host', ''))
            ->selectRaw('COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->first();

        if ($direct && $direct->clicks > 0) {
            array_unshift($withReferrer, [
                'referrer' => 'Direct / None',
                'clicks' => (int) $direct->clicks,
                'unique_clicks' => (int) $direct->unique_clicks,
            ]);
        }

        return $withReferrer;
    }

    /**
     * Get clicks grouped by UTM source.
     */
    public function getClicksByUtmSource(Page $biolink, Carbon $start, Carbon $end, int $limit = 10): array
    {
        // Try aggregated stats
        $stats = ClickStat::where('biolink_id', $biolink->id)
            ->dailyTotals()
            ->dateRange($start->toDateString(), $end->toDateString())
            ->whereNotNull('utm_source')
            ->selectRaw('utm_source, SUM(clicks) as clicks, SUM(unique_clicks) as unique_clicks')
            ->groupBy('utm_source')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get();

        if ($stats->isNotEmpty()) {
            return $stats->map(fn ($s) => [
                'source' => $s->utm_source,
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])->toArray();
        }

        // Fall back to raw clicks
        return Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->whereNotNull('utm_source')
            ->selectRaw('utm_source, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('utm_source')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'source' => $s->utm_source,
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])
            ->toArray();
    }

    /**
     * Get clicks grouped by UTM campaign.
     */
    public function getClicksByUtmCampaign(Page $biolink, Carbon $start, Carbon $end, int $limit = 10): array
    {
        return Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->whereNotNull('utm_campaign')
            ->selectRaw('utm_campaign, utm_source, utm_medium, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('utm_campaign', 'utm_source', 'utm_medium')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'campaign' => $s->utm_campaign,
                'source' => $s->utm_source,
                'medium' => $s->utm_medium,
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])
            ->toArray();
    }

    /**
     * Get clicks per block for a bio.
     */
    public function getClicksByBlock(Page $biolink, Carbon $start, Carbon $end, int $limit = 10): array
    {
        return Click::where('biolink_id', $biolink->id)
            ->inDateRange($start, $end)
            ->whereNotNull('block_id')
            ->selectRaw('block_id, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('block_id')
            ->orderByDesc('clicks')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'block_id' => $s->block_id,
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])
            ->toArray();
    }

    /**
     * Convert country code to country name.
     */
    public function getCountryName(?string $code): string
    {
        if (empty($code)) {
            return 'Unknown';
        }

        // Common country codes - can be extended or use a proper library
        $countries = [
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'NL' => 'Netherlands',
            'BE' => 'Belgium',
            'AU' => 'Australia',
            'CA' => 'Canada',
            'BR' => 'Brazil',
            'IN' => 'India',
            'JP' => 'Japan',
            'CN' => 'China',
            'KR' => 'South Korea',
            'RU' => 'Russia',
            'PL' => 'Poland',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'IE' => 'Ireland',
            'PT' => 'Portugal',
            'AT' => 'Austria',
            'CH' => 'Switzerland',
            'NZ' => 'New Zealand',
            'SG' => 'Singapore',
            'HK' => 'Hong Kong',
            'MX' => 'Mexico',
            'AR' => 'Argentina',
            'CL' => 'Chile',
            'CO' => 'Colombia',
            'ZA' => 'South Africa',
            'NG' => 'Nigeria',
            'EG' => 'Egypt',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'IL' => 'Israel',
            'TR' => 'Turkey',
            'TH' => 'Thailand',
            'VN' => 'Vietnam',
            'ID' => 'Indonesia',
            'MY' => 'Malaysia',
            'PH' => 'Philippines',
            'PK' => 'Pakistan',
            'BD' => 'Bangladesh',
            'UA' => 'Ukraine',
            'CZ' => 'Czech Republic',
            'RO' => 'Romania',
            'HU' => 'Hungary',
            'GR' => 'Greece',
        ];

        return $countries[$code] ?? $code;
    }

    /**
     * Get the date range for a period string.
     */
    public function getDateRangeForPeriod(string $period): array
    {
        $end = now()->endOfDay();

        $start = match ($period) {
            '24h' => now()->subHours(24),
            '7d' => now()->subDays(7)->startOfDay(),
            '30d' => now()->subDays(30)->startOfDay(),
            '90d' => now()->subDays(90)->startOfDay(),
            '1y' => now()->subYear()->startOfDay(),
            default => now()->subDays(7)->startOfDay(),
        };

        return [$start, $end];
    }
}
