<?php

namespace Core\Mod\Web\Jobs;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Click;
use Core\Mod\Web\Models\NotificationHandler;
use Core\Mod\Web\Services\NotificationService;
use Core\Headers\DetectDevice;
use Core\Mod\Analytics\Services\GeoIpService;
use Core\Helpers\PrivacyHelper;
use Core\Helpers\UtmHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

/**
 * Track a click on a biolink or block.
 *
 * Dispatched asynchronously to avoid blocking the response.
 * Request data is serialised since Request objects cannot be queued.
 */
class TrackBioLinkClick implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $biolinkId;

    public ?int $blockId;

    public array $requestData;

    /**
     * Create a new job instance.
     *
     * Extracts serialisable data from the request since Request
     * objects cannot be serialised for queue workers.
     */
    public function __construct(int $biolinkId, ?int $blockId, Request $request)
    {
        $this->biolinkId = $biolinkId;
        $this->blockId = $blockId;

        // Extract serialisable data from request
        $this->requestData = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->header('Referer'),
            // Store CDN headers for GeoIP service
            'cf_country' => $request->header('CF-IPCountry'),
            'x_country' => $request->header('X-Country-Code'),
            // UTM params
            ...UtmHelper::extractFromRequest($request),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(
        DetectDevice $deviceDetection,
        GeoIpService $geoIp
    ): void {
        $biolink = Page::find($this->biolinkId);

        if (! $biolink) {
            return;
        }

        $userAgent = $this->requestData['user_agent'] ?? '';
        $ip = $this->requestData['ip'] ?? '';

        // Skip bot traffic
        if ($deviceDetection->isBot($userAgent)) {
            return;
        }

        // Determine if this is a unique visitor (IP-based check for today)
        $uniqueKey = PrivacyHelper::uniqueVisitorCacheKey("biolink_visit:{$this->biolinkId}", $ip);
        $isUnique = ! Cache::has($uniqueKey);

        if ($isUnique) {
            Cache::put($uniqueKey, true, now()->endOfDay());
        }

        // Parse device info using shared service
        $deviceInfo = $deviceDetection->parse($userAgent);

        // Get country from CDN headers (stored during job creation)
        $countryCode = $this->requestData['cf_country'] ?? $this->requestData['x_country'];

        // CloudFlare uses 'XX' for unknown countries
        if ($countryCode === 'XX') {
            $countryCode = null;
        }

        $referrerHost = UtmHelper::extractReferrerHost($this->requestData['referrer'] ?? null);

        Click::create([
            'biolink_id' => $this->biolinkId,
            'block_id' => $this->blockId,
            'visitor_hash' => PrivacyHelper::hashIpDaily($ip),
            'country_code' => $countryCode,
            'region' => null,
            'device_type' => $deviceInfo['device_type'],
            'os_name' => $deviceInfo['os_name'],
            'browser_name' => $deviceInfo['browser_name'],
            'referrer_host' => $referrerHost,
            'utm_source' => $this->requestData['utm_source'],
            'utm_medium' => $this->requestData['utm_medium'],
            'utm_campaign' => $this->requestData['utm_campaign'],
            'is_unique' => $isUnique,
            'created_at' => now(),
        ]);

        // Dispatch notifications for this click event
        $this->dispatchNotifications($biolink, $countryCode, $deviceInfo, $referrerHost);
    }

    /**
     * Dispatch notifications for click events.
     */
    protected function dispatchNotifications(
        Page $biolink,
        ?string $countryCode,
        array $deviceInfo,
        ?string $referrerHost
    ): void {
        // Determine event type
        $event = $this->blockId
            ? NotificationHandler::EVENT_BLOCK_CLICK
            : NotificationHandler::EVENT_CLICK;

        // Build notification data
        $data = [
            'country_code' => $countryCode,
            'device_type' => $deviceInfo['device_type'] ?? 'unknown',
            'browser' => $deviceInfo['browser_name'] ?? null,
            'os' => $deviceInfo['os_name'] ?? null,
            'referrer' => $referrerHost,
            'utm_source' => $this->requestData['utm_source'] ?? null,
            'utm_medium' => $this->requestData['utm_medium'] ?? null,
            'utm_campaign' => $this->requestData['utm_campaign'] ?? null,
        ];

        // Add block info if this is a block click
        if ($this->blockId) {
            $block = $biolink->blocks()->find($this->blockId);
            if ($block) {
                $data['block_id'] = $block->id;
                $data['block_type'] = $block->type;
            }
        }

        // Dispatch via notification service
        app(NotificationService::class)->dispatch($biolink, $event, $data);
    }
}
