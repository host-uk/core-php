<?php

declare(strict_types=1);

namespace Core\Cdn\Services;

use Core\Config\ConfigService;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * BunnyCDN Pull Zone API service.
 *
 * Handles CDN operations via BunnyCDN API:
 * - Cache purging (URL, tag, workspace, global)
 * - Statistics retrieval
 * - Pull zone management
 */
class BunnyCdnService
{
    protected string $apiKey;

    protected string $pullZoneId;

    protected string $baseUrl = 'https://api.bunny.net';

    public function __construct(
        protected ConfigService $config,
    ) {
        $this->apiKey = $this->config->get('cdn.bunny.api_key') ?? '';
        $this->pullZoneId = $this->config->get('cdn.bunny.pull_zone_id') ?? '';
    }

    /**
     * Check if the service is configured.
     */
    public function isConfigured(): bool
    {
        return $this->config->isConfigured('cdn.bunny');
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Cache Purging
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Purge a single URL from CDN cache.
     */
    public function purgeUrl(string $url): bool
    {
        return $this->purgeUrls([$url]);
    }

    /**
     * Purge multiple URLs from CDN cache.
     */
    public function purgeUrls(array $urls): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('BunnyCDN: Cannot purge - not configured');

            return false;
        }

        try {
            foreach ($urls as $url) {
                $response = Http::withHeaders([
                    'AccessKey' => $this->apiKey,
                ])->post("{$this->baseUrl}/purge", [
                    'url' => $url,
                ]);

                if (! $response->successful()) {
                    Log::error('BunnyCDN: Purge failed', [
                        'url' => $url,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);

                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('BunnyCDN: Purge exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Purge entire pull zone cache.
     */
    public function purgeAll(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'AccessKey' => $this->apiKey,
            ])->post("{$this->baseUrl}/pullzone/{$this->pullZoneId}/purgeCache");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('BunnyCDN: PurgeAll exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Purge cache by tag.
     */
    public function purgeByTag(string $tag): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'AccessKey' => $this->apiKey,
            ])->post("{$this->baseUrl}/pullzone/{$this->pullZoneId}/purgeCache", [
                'CacheTag' => $tag,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('BunnyCDN: PurgeByTag exception', [
                'tag' => $tag,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Purge all cached content for a workspace.
     */
    public function purgeWorkspace(Workspace $workspace): bool
    {
        return $this->purgeByTag("workspace-{$workspace->uuid}");
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Statistics
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Get CDN statistics for pull zone.
     */
    public function getStats(?string $dateFrom = null, ?string $dateTo = null): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $params = [
                'pullZone' => $this->pullZoneId,
            ];

            if ($dateFrom) {
                $params['dateFrom'] = $dateFrom;
            }
            if ($dateTo) {
                $params['dateTo'] = $dateTo;
            }

            $response = Http::withHeaders([
                'AccessKey' => $this->apiKey,
            ])->get("{$this->baseUrl}/statistics", $params);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('BunnyCDN: GetStats exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get bandwidth usage for pull zone.
     */
    public function getBandwidth(?string $dateFrom = null, ?string $dateTo = null): ?array
    {
        $stats = $this->getStats($dateFrom, $dateTo);

        if (! $stats) {
            return null;
        }

        return [
            'total_bandwidth' => $stats['TotalBandwidthUsed'] ?? 0,
            'cached_bandwidth' => $stats['CacheHitRate'] ?? 0,
            'origin_bandwidth' => $stats['TotalOriginTraffic'] ?? 0,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Storage Zone Operations (via API)
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * List files in a storage zone via API.
     *
     * Note: For direct storage operations, use BunnyStorageService instead.
     */
    public function listStorageFiles(string $storageZoneName, string $path = '/'): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        try {
            $storageApiKey = $this->config->get('cdn.bunny.storage.public.api_key');
            $region = $this->config->get('cdn.bunny.storage.public.hostname', 'storage.bunnycdn.com');

            $url = "https://{$region}/{$storageZoneName}/{$path}";

            $response = Http::withHeaders([
                'AccessKey' => $storageApiKey,
            ])->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('BunnyCDN: ListStorageFiles exception', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Upload a file to storage zone via API.
     *
     * Note: For direct storage operations, use BunnyStorageService instead.
     */
    public function uploadFile(string $storageZoneName, string $path, string $contents): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $storageApiKey = $this->config->get('cdn.bunny.storage.public.api_key');
            $region = $this->config->get('cdn.bunny.storage.public.hostname', 'storage.bunnycdn.com');

            $url = "https://{$region}/{$storageZoneName}/{$path}";

            $response = Http::withHeaders([
                'AccessKey' => $storageApiKey,
                'Content-Type' => 'application/octet-stream',
            ])->withBody($contents, 'application/octet-stream')->put($url);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('BunnyCDN: UploadFile exception', ['error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Delete a file from storage zone via API.
     *
     * Note: For direct storage operations, use BunnyStorageService instead.
     */
    public function deleteFile(string $storageZoneName, string $path): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $storageApiKey = $this->config->get('cdn.bunny.storage.public.api_key');
            $region = $this->config->get('cdn.bunny.storage.public.hostname', 'storage.bunnycdn.com');

            $url = "https://{$region}/{$storageZoneName}/{$path}";

            $response = Http::withHeaders([
                'AccessKey' => $storageApiKey,
            ])->delete($url);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('BunnyCDN: DeleteFile exception', ['error' => $e->getMessage()]);

            return false;
        }
    }
}
