<?php

namespace Core\Mod\Api\Controllers;

use Core\Front\Controller;
use Core\Mod\Analytics\Models\Website;
use Core\Mod\Notify\Models\PushWebsite;
use Core\Mod\Trust\Models\Campaign;
use Core\Mod\Analytics\Services\AnalyticsTrackingService;
use Core\Mod\Trust\Services\TrustService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Unified Pixel Controller.
 *
 * Provides configuration and tracking endpoints for the unified
 * hosthub-pixel.js tracking script.
 */
class UnifiedPixelController extends Controller
{
    public function __construct(
        protected AnalyticsTrackingService $analyticsTracking,
        protected TrustService $trustService,
    ) {}

    /**
     * Get unified pixel configuration.
     *
     * Returns which features are enabled for a given pixel key and their settings.
     * The pixel key can be associated with any combination of:
     * - Analytics website
     * - Push notification website
     * - Social proof campaign
     */
    public function config(Request $request): JsonResponse
    {
        $pixelKey = $request->query('pixel_key');

        if (! $pixelKey) {
            return response()->json([
                'ok' => false,
                'error' => 'Missing pixel_key parameter',
            ], 400);
        }

        // Cache config for 5 minutes to reduce database lookups
        $cacheKey = "pixel_config:{$pixelKey}";
        $config = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($pixelKey) {
            return $this->buildConfig($pixelKey);
        });

        if (! $config) {
            return response()->json([
                'ok' => false,
                'error' => 'Invalid or disabled pixel key',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'config' => $config,
        ]);
    }

    /**
     * Build configuration for a pixel key.
     */
    protected function buildConfig(string $pixelKey): ?array
    {
        $config = [
            'analytics' => false,
            'push' => false,
            'socialproof' => false,
        ];

        $foundAny = false;

        // Check analytics
        $analyticsWebsite = Website::where('pixel_key', $pixelKey)
            ->active()
            ->first();

        if ($analyticsWebsite) {
            $foundAny = true;
            $config['analytics'] = true;
            $config['analytics_settings'] = [
                'tracking_type' => $analyticsWebsite->tracking_type ?? 'lightweight',
                'track_clicks' => $analyticsWebsite->settings['track_clicks'] ?? false,
                'track_scroll' => $analyticsWebsite->settings['track_scroll'] ?? false,
                'track_outbound' => $analyticsWebsite->settings['track_outbound'] ?? false,
                'session_timeout' => $analyticsWebsite->settings['session_timeout'] ?? 30,
            ];
        }

        // Check push notifications
        $pushWebsite = PushWebsite::where('pixel_key', $pixelKey)
            ->where('is_enabled', true)
            ->first();

        if ($pushWebsite) {
            $foundAny = true;
            $config['push'] = true;
            $widgetSettings = $pushWebsite->widget_settings ?? [];
            $config['push_settings'] = [
                'auto_prompt' => $widgetSettings['auto_prompt'] ?? true,
                'prompt_delay' => $widgetSettings['prompt_delay'] ?? 3,
                'widget_position' => $widgetSettings['widget_position'] ?? 'top-right',
            ];
        }

        // Check social proof
        $socialProofCampaign = Campaign::where('pixel_key', $pixelKey)
            ->enabled()
            ->first();

        if ($socialProofCampaign) {
            $foundAny = true;
            $config['socialproof'] = true;
            $config['socialproof_settings'] = [
                'primary_color' => $socialProofCampaign->primary_color,
                'logo' => $socialProofCampaign->logo,
            ];
        }

        // If no services found for this pixel key, return null
        if (! $foundAny) {
            return null;
        }

        return $config;
    }

    /**
     * Unified tracking endpoint.
     *
     * Accepts tracking data and routes it to the appropriate service
     * based on the event type.
     */
    public function track(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pixel_key' => 'required|string|max:64',
            'type' => 'required|string|in:pageview,event,goal,session_end',
            'visitor_id' => 'sometimes|string|max:64',
            'session_id' => 'sometimes|string|max:64',
            'timestamp' => 'sometimes|date',
            'path' => 'sometimes|string|max:512',
            'title' => 'sometimes|string|max:256',
            'url' => 'sometimes|string|max:512',
            'referrer' => 'sometimes|nullable|string|max:512',
            'referrer_host' => 'sometimes|nullable|string|max:256',
            'device_type' => 'sometimes|string|max:16',
            'browser' => 'sometimes|string|max:32',
            'os' => 'sometimes|string|max:32',
            'screen' => 'sometimes|array',
            'utm_source' => 'sometimes|nullable|string|max:128',
            'utm_medium' => 'sometimes|nullable|string|max:128',
            'utm_campaign' => 'sometimes|nullable|string|max:128',
            'utm_term' => 'sometimes|nullable|string|max:128',
            'utm_content' => 'sometimes|nullable|string|max:128',
            'event_name' => 'sometimes|string|max:128',
            'event_data' => 'sometimes|array',
            'goal_key' => 'sometimes|string|max:64',
            'value' => 'sometimes|numeric',
            'duration' => 'sometimes|integer|min:0',
        ]);

        // Find the analytics website for this pixel key
        $website = Website::where('pixel_key', $validated['pixel_key'])
            ->active()
            ->first();

        if (! $website) {
            return response()->json([
                'ok' => false,
                'error' => 'Invalid or disabled pixel key',
            ], 404);
        }

        // Map the unified format to the analytics tracking format
        $trackingData = [
            'type' => $validated['type'] === 'event' ? 'custom' : $validated['type'],
            'visitor_id' => $validated['visitor_id'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
            'path' => $validated['path'] ?? '/',
            'title' => $validated['title'] ?? null,
            'referrer' => $validated['referrer'] ?? null,
            'utm_source' => $validated['utm_source'] ?? null,
            'utm_medium' => $validated['utm_medium'] ?? null,
            'utm_campaign' => $validated['utm_campaign'] ?? null,
            'utm_term' => $validated['utm_term'] ?? null,
            'utm_content' => $validated['utm_content'] ?? null,
            'screen_width' => $validated['screen']['width'] ?? null,
            'screen_height' => $validated['screen']['height'] ?? null,
        ];

        // Add event-specific data
        if ($validated['type'] === 'event') {
            $trackingData['event_name'] = $validated['event_name'] ?? 'custom';
            $trackingData['properties'] = $validated['event_data'] ?? [];
        }

        // Add goal-specific data
        if ($validated['type'] === 'goal') {
            $trackingData['event_name'] = $validated['goal_key'] ?? 'goal';
            $trackingData['properties'] = ['value' => $validated['value'] ?? null];
        }

        // Track the event
        $event = $this->analyticsTracking->track($website, $trackingData, $request);

        return response()->json([
            'ok' => true,
            'event_id' => $event?->id,
            'visitor_id' => $event?->visitor?->visitor_uuid,
            'session_id' => $event?->session?->session_uuid,
        ]);
    }

    /**
     * Clear cached configuration for a pixel key.
     *
     * Called when website/campaign settings are updated.
     */
    public static function clearConfigCache(string $pixelKey): void
    {
        Cache::forget("pixel_config:{$pixelKey}");
    }
}
