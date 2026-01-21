<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\AnalyticsService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class BioAnalyticsTools extends BaseBioTool
{
    protected string $name = 'analytics_tools';

    protected string $description = 'Get detailed analytics for bio links';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');

        return match ($action) {
            'stats' => $this->getStats($request->get('biolink_id'), $request->get('period', '7d')),
            'detailed' => $this->getDetailedAnalytics($request),
            default => $this->error('Invalid action', ['available' => ['stats', 'detailed']]),
        };
    }

    protected function getStats(?int $biolinkId, string $period): Response
    {
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::with('clickStats', 'blocks')->find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $days = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT) ?: 7;
        $startDate = now()->subDays($days);

        $clicksByDay = $biolink->clickRecords()
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as clicks, SUM(CASE WHEN is_unique THEN 1 ELSE 0 END) as unique_clicks')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('clicks', 'date');

        $topBlocks = $biolink->blocks()
            ->orderByDesc('clicks')
            ->limit(10)
            ->get(['id', 'type', 'clicks', 'settings']);

        return $this->json([
            'biolink_id' => $biolink->id,
            'period' => $period,
            'total_clicks' => $biolink->clicks,
            'unique_clicks' => $biolink->unique_clicks,
            'clicks_by_day' => $clicksByDay,
            'top_blocks' => $topBlocks->map(fn ($b) => [
                'id' => $b->id,
                'type' => $b->type,
                'clicks' => $b->clicks,
                'name' => $b->settings['name'] ?? null,
            ]),
        ]);
    }

    protected function getDetailedAnalytics(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $analyticsService = app(AnalyticsService::class);

        $period = $request->get('period', '7d');
        [$start, $end] = $analyticsService->getDateRangeForPeriod($period);

        $workspace = $biolink->workspace;
        $retention = $analyticsService->enforceDateRetention($start, $end, $workspace);
        $start = $retention['start'];

        $result = [
            'biolink_id' => $biolink->id,
            'url' => $biolink->full_url,
            'period' => $period,
            'date_range' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'limited' => $retention['limited'],
                'max_days' => $retention['max_days'],
            ],
            'summary' => $analyticsService->getSummary($biolink, $start, $end),
            'clicks_over_time' => $analyticsService->getClicksOverTime($biolink, $start, $end),
        ];

        $include = $request->get('include', ['geo', 'devices', 'referrers', 'utm']);
        if (is_string($include)) {
            $include = explode(',', $include);
        }

        if (in_array('geo', $include)) {
            $result['geo'] = [
                'countries' => $analyticsService->getClicksByCountry($biolink, $start, $end),
            ];
        }

        if (in_array('devices', $include)) {
            $result['devices'] = [
                'types' => $analyticsService->getClicksByDevice($biolink, $start, $end),
                'browsers' => $analyticsService->getClicksByBrowser($biolink, $start, $end),
                'operating_systems' => $analyticsService->getClicksByOs($biolink, $start, $end),
            ];
        }

        if (in_array('referrers', $include)) {
            $result['referrers'] = $analyticsService->getClicksByReferrer($biolink, $start, $end);
        }

        if (in_array('utm', $include)) {
            $result['utm'] = [
                'sources' => $analyticsService->getClicksByUtmSource($biolink, $start, $end),
                'campaigns' => $analyticsService->getClicksByUtmCampaign($biolink, $start, $end),
            ];
        }

        if (in_array('blocks', $include)) {
            $result['blocks'] = $analyticsService->getClicksByBlock($biolink, $start, $end);
        }

        return $this->json($result);
    }
}
