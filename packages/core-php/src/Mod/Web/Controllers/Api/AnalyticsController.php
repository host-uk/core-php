<?php

declare(strict_types=1);

namespace Core\Mod\Web\Controllers\Api;

use Core\Front\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Core\Mod\Api\Controllers\Concerns\HasApiResponses;
use Core\Mod\Api\Controllers\Concerns\ResolvesWorkspace;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Resources\AnalyticsResource;
use Core\Mod\Web\Services\AnalyticsService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;

/**
 * BioLink Analytics API controller.
 *
 * Provides analytics data for biolinks via REST API.
 * Supports both session auth and API key auth.
 */
class AnalyticsController extends Controller
{
    use HasApiResponses;
    use ResolvesWorkspace;

    public function __construct(
        protected AnalyticsService $analytics,
        protected EntitlementService $entitlements
    ) {}

    /**
     * Get analytics summary for a bio.
     *
     * GET /api/v1/biolinks/{biolink}/analytics
     */
    public function summary(Request $request, Page $biolink): AnalyticsResource|JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        // Check analytics entitlement
        $check = $this->entitlements->can($workspace, 'bio.analytics');
        if ($check->isDenied()) {
            return $this->accessDeniedResponse('Analytics access is not available on your current plan.');
        }

        $period = $request->input('period', '7d');
        [$start, $end] = $this->analytics->getDateRangeForPeriod($period);

        // Enforce retention limits
        $dateRange = $this->analytics->enforceDateRetention($start, $end, $workspace);

        $summary = $this->analytics->getSummary($biolink, $dateRange['start'], $dateRange['end']);
        $clicksOverTime = $this->analytics->getClicksOverTime($biolink, $dateRange['start'], $dateRange['end']);
        $countries = $this->analytics->getClicksByCountry($biolink, $dateRange['start'], $dateRange['end']);
        $devices = $this->analytics->getClicksByDevice($biolink, $dateRange['start'], $dateRange['end']);
        $referrers = $this->analytics->getClicksByReferrer($biolink, $dateRange['start'], $dateRange['end']);

        return new AnalyticsResource([
            'biolink_id' => $biolink->id,
            'period' => $period,
            'start' => $dateRange['start']->toIso8601String(),
            'end' => $dateRange['end']->toIso8601String(),
            'limited' => $dateRange['limited'],
            'max_days' => $dateRange['max_days'],
            'summary' => $summary,
            'clicks_over_time' => $clicksOverTime,
            'countries' => $countries,
            'devices' => $devices,
            'referrers' => $referrers,
        ]);
    }

    /**
     * Get clicks over time for a bio.
     *
     * GET /api/v1/biolinks/{biolink}/analytics/clicks
     */
    public function clicks(Request $request, Page $biolink): JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        // Check analytics entitlement
        $check = $this->entitlements->can($workspace, 'bio.analytics');
        if ($check->isDenied()) {
            return $this->accessDeniedResponse('Analytics access is not available on your current plan.');
        }

        $period = $request->input('period', '7d');
        [$start, $end] = $this->analytics->getDateRangeForPeriod($period);

        // Enforce retention limits
        $dateRange = $this->analytics->enforceDateRetention($start, $end, $workspace);

        $clicksOverTime = $this->analytics->getClicksOverTime($biolink, $dateRange['start'], $dateRange['end']);

        return response()->json([
            'data' => $clicksOverTime,
            'period' => $period,
            'start' => $dateRange['start']->toIso8601String(),
            'end' => $dateRange['end']->toIso8601String(),
        ]);
    }

    /**
     * Get geographic breakdown for a bio.
     *
     * GET /api/v1/biolinks/{biolink}/analytics/geo
     */
    public function geo(Request $request, Page $biolink): JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        // Check analytics entitlement
        $check = $this->entitlements->can($workspace, 'bio.analytics');
        if ($check->isDenied()) {
            return $this->accessDeniedResponse('Analytics access is not available on your current plan.');
        }

        $period = $request->input('period', '7d');
        $limit = min((int) $request->input('limit', 10), 50);
        [$start, $end] = $this->analytics->getDateRangeForPeriod($period);

        // Enforce retention limits
        $dateRange = $this->analytics->enforceDateRetention($start, $end, $workspace);

        $countries = $this->analytics->getClicksByCountry($biolink, $dateRange['start'], $dateRange['end'], $limit);

        return response()->json([
            'data' => $countries,
            'period' => $period,
            'start' => $dateRange['start']->toIso8601String(),
            'end' => $dateRange['end']->toIso8601String(),
        ]);
    }

    /**
     * Get device breakdown for a bio.
     *
     * GET /api/v1/biolinks/{biolink}/analytics/devices
     */
    public function devices(Request $request, Page $biolink): JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        // Check analytics entitlement
        $check = $this->entitlements->can($workspace, 'bio.analytics');
        if ($check->isDenied()) {
            return $this->accessDeniedResponse('Analytics access is not available on your current plan.');
        }

        $period = $request->input('period', '7d');
        [$start, $end] = $this->analytics->getDateRangeForPeriod($period);

        // Enforce retention limits
        $dateRange = $this->analytics->enforceDateRetention($start, $end, $workspace);

        $devices = $this->analytics->getClicksByDevice($biolink, $dateRange['start'], $dateRange['end']);
        $browsers = $this->analytics->getClicksByBrowser($biolink, $dateRange['start'], $dateRange['end']);
        $operatingSystems = $this->analytics->getClicksByOs($biolink, $dateRange['start'], $dateRange['end']);

        return response()->json([
            'data' => [
                'devices' => $devices,
                'browsers' => $browsers,
                'operating_systems' => $operatingSystems,
            ],
            'period' => $period,
            'start' => $dateRange['start']->toIso8601String(),
            'end' => $dateRange['end']->toIso8601String(),
        ]);
    }

    /**
     * Get referrer breakdown for a bio.
     *
     * GET /api/v1/biolinks/{biolink}/analytics/referrers
     */
    public function referrers(Request $request, Page $biolink): JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        // Check analytics entitlement
        $check = $this->entitlements->can($workspace, 'bio.analytics');
        if ($check->isDenied()) {
            return $this->accessDeniedResponse('Analytics access is not available on your current plan.');
        }

        $period = $request->input('period', '7d');
        $limit = min((int) $request->input('limit', 10), 50);
        [$start, $end] = $this->analytics->getDateRangeForPeriod($period);

        // Enforce retention limits
        $dateRange = $this->analytics->enforceDateRetention($start, $end, $workspace);

        $referrers = $this->analytics->getClicksByReferrer($biolink, $dateRange['start'], $dateRange['end'], $limit);

        return response()->json([
            'data' => $referrers,
            'period' => $period,
            'start' => $dateRange['start']->toIso8601String(),
            'end' => $dateRange['end']->toIso8601String(),
        ]);
    }

    /**
     * Get UTM campaign breakdown for a bio.
     *
     * GET /api/v1/biolinks/{biolink}/analytics/utm
     */
    public function utm(Request $request, Page $biolink): JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        // Check analytics entitlement
        $check = $this->entitlements->can($workspace, 'bio.analytics');
        if ($check->isDenied()) {
            return $this->accessDeniedResponse('Analytics access is not available on your current plan.');
        }

        $period = $request->input('period', '7d');
        $limit = min((int) $request->input('limit', 10), 50);
        [$start, $end] = $this->analytics->getDateRangeForPeriod($period);

        // Enforce retention limits
        $dateRange = $this->analytics->enforceDateRetention($start, $end, $workspace);

        $sources = $this->analytics->getClicksByUtmSource($biolink, $dateRange['start'], $dateRange['end'], $limit);
        $campaigns = $this->analytics->getClicksByUtmCampaign($biolink, $dateRange['start'], $dateRange['end'], $limit);

        return response()->json([
            'data' => [
                'sources' => $sources,
                'campaigns' => $campaigns,
            ],
            'period' => $period,
            'start' => $dateRange['start']->toIso8601String(),
            'end' => $dateRange['end']->toIso8601String(),
        ]);
    }

    /**
     * Get block-level analytics for a bio.
     *
     * GET /api/v1/biolinks/{biolink}/analytics/blocks
     */
    public function blocks(Request $request, Page $biolink): JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        // Check analytics entitlement
        $check = $this->entitlements->can($workspace, 'bio.analytics');
        if ($check->isDenied()) {
            return $this->accessDeniedResponse('Analytics access is not available on your current plan.');
        }

        $period = $request->input('period', '7d');
        $limit = min((int) $request->input('limit', 10), 50);
        [$start, $end] = $this->analytics->getDateRangeForPeriod($period);

        // Enforce retention limits
        $dateRange = $this->analytics->enforceDateRetention($start, $end, $workspace);

        $blockStats = $this->analytics->getClicksByBlock($biolink, $dateRange['start'], $dateRange['end'], $limit);

        // Enrich with block details
        $blockIds = array_column($blockStats, 'block_id');
        $blocks = $biolink->blocks()->whereIn('id', $blockIds)->get()->keyBy('id');

        $enrichedStats = array_map(function ($stat) use ($blocks) {
            $block = $blocks->get($stat['block_id']);

            return [
                'block_id' => $stat['block_id'],
                'block_type' => $block?->type,
                'block_settings' => $block?->settings?->toArray() ?? [],
                'clicks' => $stat['clicks'],
                'unique_clicks' => $stat['unique_clicks'],
            ];
        }, $blockStats);

        return response()->json([
            'data' => $enrichedStats,
            'period' => $period,
            'start' => $dateRange['start']->toIso8601String(),
            'end' => $dateRange['end']->toIso8601String(),
        ]);
    }

    /**
     * Get the current user's workspace.
     */
    /**
     * Get the current user's workspace.
     *
     * @deprecated Use resolveWorkspace() from ResolvesWorkspace trait
     */
    protected function getWorkspace(Request $request): ?Workspace
    {
        return $this->resolveWorkspace($request);
    }
}
