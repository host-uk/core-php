<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Click;
use Core\Mod\Web\Models\ClickStat;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\AnalyticsService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Analytics Overview component.
 *
 * Cross-biolink analytics dashboard showing aggregate statistics
 * across all user's biolinks with top performers and trends.
 */
#[Layout('hub::admin.layouts.app')]
class AnalyticsOverview extends Component
{
    public string $period = '7d';

    // Retention limit state
    public bool $isDateLimited = false;

    public int $maxRetentionDays = 30;

    protected $queryString = [
        'period' => ['except' => '7d'],
    ];

    protected AnalyticsService $analyticsService;

    public function boot(AnalyticsService $analyticsService): void
    {
        $this->analyticsService = $analyticsService;
    }

    public function mount(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
        // Set max retention days based on entitlement
        $this->maxRetentionDays = $this->analyticsService->getRetentionDays(
            $this->getCurrentWorkspace()
        );
    }

    /**
     * Get the current user's workspace.
     */
    protected function getCurrentWorkspace(): ?Workspace
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        return $user->defaultHostWorkspace();
    }

    /**
     * Get user's biolink IDs.
     */
    protected function getUserBiolinkIds(): array
    {
        return Page::where('user_id', Auth::id())
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get the date range for the selected period, with retention limits applied.
     */
    #[Computed]
    public function dateRange(): array
    {
        [$start, $end] = $this->analyticsService->getDateRangeForPeriod($this->period);

        // Enforce retention limits
        $result = $this->analyticsService->enforceDateRetention(
            $start,
            $end,
            $this->getCurrentWorkspace()
        );

        $this->isDateLimited = $result['limited'];
        $this->maxRetentionDays = $result['max_days'];

        return [$result['start'], $result['end']];
    }

    /**
     * Get available period options based on retention days.
     */
    #[Computed]
    public function availablePeriods(): array
    {
        $periods = [
            '24h' => ['label' => 'Last 24 hours', 'days' => 1],
            '7d' => ['label' => 'Last 7 days', 'days' => 7],
            '30d' => ['label' => 'Last 30 days', 'days' => 30],
            '90d' => ['label' => 'Last 90 days', 'days' => 90],
            '1y' => ['label' => 'Last year', 'days' => 365],
        ];

        $available = [];
        foreach ($periods as $key => $config) {
            $available[$key] = [
                'label' => $config['label'],
                'available' => $config['days'] <= $this->maxRetentionDays,
                'requires_upgrade' => $config['days'] > $this->maxRetentionDays,
            ];
        }

        return $available;
    }

    /**
     * Get total statistics across all bio.
     */
    #[Computed]
    public function totalStats(): array
    {
        [$start, $end] = $this->dateRange;
        $biolinkIds = $this->getUserBiolinkIds();

        if (empty($biolinkIds)) {
            return ['clicks' => 0, 'unique_clicks' => 0, 'biolinks' => 0];
        }

        // Try aggregated stats first
        $stats = ClickStat::whereIn('biolink_id', $biolinkIds)
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
                'biolinks' => count($biolinkIds),
            ];
        }

        // Fall back to raw clicks table
        $raw = Click::whereIn('biolink_id', $biolinkIds)
            ->inDateRange($start, $end)
            ->selectRaw('COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->first();

        return [
            'clicks' => (int) ($raw->clicks ?? 0),
            'unique_clicks' => (int) ($raw->unique_clicks ?? 0),
            'biolinks' => count($biolinkIds),
        ];
    }

    /**
     * Get clicks over time (aggregate across all biolinks).
     */
    #[Computed]
    public function chartData(): array
    {
        [$start, $end] = $this->dateRange;
        $biolinkIds = $this->getUserBiolinkIds();

        if (empty($biolinkIds)) {
            return ['labels' => [], 'clicks' => [], 'unique_clicks' => []];
        }

        // Try aggregated stats first
        $stats = ClickStat::whereIn('biolink_id', $biolinkIds)
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
        $raw = Click::whereIn('biolink_id', $biolinkIds)
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
    protected function fillDateGaps($data, Carbon $start, Carbon $end): array
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
     * Get top performing bio.
     */
    #[Computed]
    public function topBiolinks(): array
    {
        [$start, $end] = $this->dateRange;
        $biolinkIds = $this->getUserBiolinkIds();

        if (empty($biolinkIds)) {
            return [];
        }

        // Get clicks by biolink
        $clicks = Click::whereIn('biolink_id', $biolinkIds)
            ->inDateRange($start, $end)
            ->selectRaw('biolink_id, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('biolink_id')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get()
            ->keyBy('biolink_id');

        if ($clicks->isEmpty()) {
            return [];
        }

        // Get biolink details
        $biolinks = Page::whereIn('id', $clicks->keys())->get()->keyBy('id');

        return $clicks->map(function ($stat) use ($biolinks) {
            $biolink = $biolinks->get($stat->biolink_id);

            return [
                'id' => $stat->biolink_id,
                'url' => $biolink?->url ?? 'Unknown',
                'type' => $biolink?->type ?? 'unknown',
                'clicks' => (int) $stat->clicks,
                'unique_clicks' => (int) $stat->unique_clicks,
            ];
        })->values()->toArray();
    }

    /**
     * Get clicks by country (aggregate).
     */
    #[Computed]
    public function countries(): array
    {
        [$start, $end] = $this->dateRange;
        $biolinkIds = $this->getUserBiolinkIds();

        if (empty($biolinkIds)) {
            return [];
        }

        return Click::whereIn('biolink_id', $biolinkIds)
            ->inDateRange($start, $end)
            ->whereNotNull('country_code')
            ->selectRaw('country_code, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('country_code')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'country_code' => $s->country_code,
                'country_name' => $this->analyticsService->getCountryName($s->country_code),
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])
            ->toArray();
    }

    /**
     * Get clicks by device type (aggregate).
     */
    #[Computed]
    public function devices(): array
    {
        [$start, $end] = $this->dateRange;
        $biolinkIds = $this->getUserBiolinkIds();

        if (empty($biolinkIds)) {
            return [];
        }

        return Click::whereIn('biolink_id', $biolinkIds)
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
     * Get clicks by referrer (aggregate).
     */
    #[Computed]
    public function referrers(): array
    {
        [$start, $end] = $this->dateRange;
        $biolinkIds = $this->getUserBiolinkIds();

        if (empty($biolinkIds)) {
            return [];
        }

        // With referrer
        $withReferrer = Click::whereIn('biolink_id', $biolinkIds)
            ->inDateRange($start, $end)
            ->whereNotNull('referrer_host')
            ->where('referrer_host', '!=', '')
            ->selectRaw('referrer_host, COUNT(*) as clicks, SUM(is_unique) as unique_clicks')
            ->groupBy('referrer_host')
            ->orderByDesc('clicks')
            ->limit(10)
            ->get()
            ->map(fn ($s) => [
                'referrer' => $s->referrer_host,
                'clicks' => (int) $s->clicks,
                'unique_clicks' => (int) $s->unique_clicks,
            ])
            ->toArray();

        // Direct visits
        $direct = Click::whereIn('biolink_id', $biolinkIds)
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
     * Get the period label for display.
     */
    #[Computed]
    public function periodLabel(): string
    {
        return match ($this->period) {
            '24h' => 'Last 24 hours',
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            '90d' => 'Last 90 days',
            '1y' => 'Last year',
            default => 'Last 7 days',
        };
    }

    /**
     * Get icon for device type.
     */
    public function getDeviceIcon(string $deviceType): string
    {
        return match ($deviceType) {
            'desktop' => 'fa-desktop',
            'mobile' => 'fa-mobile',
            'tablet' => 'fa-tablet',
            default => 'fa-question',
        };
    }

    /**
     * Get colour for chart dataset.
     */
    public function getDeviceColour(string $deviceType): string
    {
        return match ($deviceType) {
            'desktop' => '#8b5cf6',
            'mobile' => '#06b6d4',
            'tablet' => '#f59e0b',
            default => '#6b7280',
        };
    }

    /**
     * Convert country code to flag emoji.
     */
    public function getFlagEmoji(?string $countryCode): string
    {
        if (empty($countryCode) || strlen($countryCode) !== 2) {
            return '🌍';
        }

        $countryCode = strtoupper($countryCode);

        // Convert country code to regional indicator symbols
        $first = ord($countryCode[0]) - ord('A') + 0x1F1E6;
        $second = ord($countryCode[1]) - ord('A') + 0x1F1E6;

        return mb_chr($first).mb_chr($second);
    }

    public function render()
    {
        return view('webpage::admin.analytics-overview')
            ->title('BioHost Analytics');
    }
}
