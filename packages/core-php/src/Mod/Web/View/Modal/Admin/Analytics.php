<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\AnalyticsService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;

/**
 * BioLink Analytics Dashboard component.
 *
 * Displays click statistics, geographic breakdown, device/browser data,
 * referrer tracking, and UTM campaign analytics for a bio.
 */
class Analytics extends Component
{
    public int $biolinkId;

    public string $period = '7d';

    public ?Page $biolink = null;

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

    /**
     * Mount the component.
     *
     * Accepts either numeric ID (from hub routes) or URL slug (from lt.hn routes).
     */
    public function mount(int|string $id): void
    {
        $this->biolink = $this->resolveBiolink($id);
        $this->biolinkId = $this->biolink->id;

        // Set max retention days based on entitlement
        $this->maxRetentionDays = $this->analyticsService->getRetentionDays(
            $this->getCurrentWorkspace()
        );
    }

    /**
     * Resolve biolink from ID or URL slug.
     */
    protected function resolveBiolink(int|string $id): Page
    {
        if (is_numeric($id)) {
            return Page::where('user_id', Auth::id())->findOrFail((int) $id);
        }

        return Page::where('url', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
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
     * Get summary statistics.
     */
    #[Computed]
    public function stats(): array
    {
        [$start, $end] = $this->dateRange;

        return $this->analyticsService->getSummary($this->biolink, $start, $end);
    }

    /**
     * Get chart data (clicks over time).
     */
    #[Computed]
    public function chartData(): array
    {
        [$start, $end] = $this->dateRange;

        return $this->analyticsService->getClicksOverTime($this->biolink, $start, $end);
    }

    /**
     * Get clicks by country.
     */
    #[Computed]
    public function countries(): array
    {
        [$start, $end] = $this->dateRange;

        return $this->analyticsService->getClicksByCountry($this->biolink, $start, $end);
    }

    /**
     * Get clicks by device type.
     */
    #[Computed]
    public function devices(): array
    {
        [$start, $end] = $this->dateRange;

        return $this->analyticsService->getClicksByDevice($this->biolink, $start, $end);
    }

    /**
     * Get clicks by browser.
     */
    #[Computed]
    public function browsers(): array
    {
        [$start, $end] = $this->dateRange;

        return $this->analyticsService->getClicksByBrowser($this->biolink, $start, $end);
    }

    /**
     * Get clicks by operating system.
     */
    #[Computed]
    public function operatingSystems(): array
    {
        [$start, $end] = $this->dateRange;

        return $this->analyticsService->getClicksByOs($this->biolink, $start, $end);
    }

    /**
     * Get clicks by referrer.
     */
    #[Computed]
    public function referrers(): array
    {
        [$start, $end] = $this->dateRange;

        return $this->analyticsService->getClicksByReferrer($this->biolink, $start, $end);
    }

    /**
     * Get clicks by UTM source.
     */
    #[Computed]
    public function utmSources(): array
    {
        [$start, $end] = $this->dateRange;

        return $this->analyticsService->getClicksByUtmSource($this->biolink, $start, $end);
    }

    /**
     * Get clicks by UTM campaign.
     */
    #[Computed]
    public function utmCampaigns(): array
    {
        [$start, $end] = $this->dateRange;

        return $this->analyticsService->getClicksByUtmCampaign($this->biolink, $start, $end);
    }

    /**
     * Get clicks by block with block details.
     */
    #[Computed]
    public function blockClicks(): array
    {
        [$start, $end] = $this->dateRange;

        $clicks = $this->analyticsService->getClicksByBlock($this->biolink, $start, $end);

        // Enrich with block details
        $blocks = $this->biolink->blocks()->whereIn('id', array_column($clicks, 'block_id'))->get()->keyBy('id');

        return array_map(function ($click) use ($blocks) {
            $block = $blocks->get($click['block_id']);

            return [
                'block_id' => $click['block_id'],
                'type' => $block?->type ?? 'unknown',
                'label' => $block?->getSetting('title') ?? $block?->getSetting('text') ?? $block?->location_url ?? ucfirst($block?->type ?? 'Unknown'),
                'clicks' => $click['clicks'],
                'unique_clicks' => $click['unique_clicks'],
            ];
        }, $clicks);
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
        return view('webpage::admin.analytics')
            ->layout('client::layouts.app', [
                'title' => 'Analytics: '.$this->biolink->url,
                'bioUrl' => $this->biolink->url,
            ]);
    }
}
