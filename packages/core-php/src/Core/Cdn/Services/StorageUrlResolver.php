<?php

declare(strict_types=1);

namespace Core\Cdn\Services;

use Core\Crypt\LthnHash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Context-aware URL resolver for CDN/storage architecture.
 *
 * Provides intelligent URL resolution based on request context:
 * - Admin/internal requests → Origin URLs (Hetzner)
 * - Public/embed requests → CDN URLs (BunnyCDN)
 * - API requests → Both URLs returned
 *
 * Supports vBucket scoping for workspace-isolated CDN paths using LTHN QuasiHash.
 */
class StorageUrlResolver
{
    protected BunnyStorageService $bunnyStorage;

    public function __construct(BunnyStorageService $bunnyStorage)
    {
        $this->bunnyStorage = $bunnyStorage;
    }

    /**
     * Generate a vBucket ID for a domain/workspace.
     *
     * Uses LTHN QuasiHash protocol for deterministic, scoped identifiers.
     * Format: cdn.host.uk.com/{vBucketId}/path/to/asset.js
     *
     * @param  string  $domain  The domain name (e.g., "host.uk.com")
     * @return string 16-character vBucket identifier
     */
    public function vBucketId(string $domain): string
    {
        return LthnHash::vBucketId($domain);
    }

    /**
     * Get CDN URL with vBucket scoping for workspace isolation.
     *
     * @param  string  $domain  The workspace domain for scoping
     * @param  string  $path  Path relative to storage root
     */
    public function vBucketCdn(string $domain, string $path): string
    {
        $vBucketId = $this->vBucketId($domain);

        return $this->cdn("{$vBucketId}/{$path}");
    }

    /**
     * Get origin URL with vBucket scoping for workspace isolation.
     *
     * @param  string  $domain  The workspace domain for scoping
     * @param  string  $path  Path relative to storage root
     */
    public function vBucketOrigin(string $domain, string $path): string
    {
        $vBucketId = $this->vBucketId($domain);

        return $this->origin("{$vBucketId}/{$path}");
    }

    /**
     * Build a vBucket-scoped storage path.
     *
     * @param  string  $domain  The workspace domain for scoping
     * @param  string  $path  Path relative to vBucket root
     */
    public function vBucketPath(string $domain, string $path): string
    {
        $vBucketId = $this->vBucketId($domain);

        return "{$vBucketId}/".ltrim($path, '/');
    }

    /**
     * Get both URLs with vBucket scoping for API responses.
     *
     * @param  string  $domain  The workspace domain for scoping
     * @param  string  $path  Path relative to storage root
     * @return array{cdn: string, origin: string, vbucket: string}
     */
    public function vBucketUrls(string $domain, string $path): array
    {
        $vBucketId = $this->vBucketId($domain);
        $scopedPath = "{$vBucketId}/{$path}";

        return [
            'cdn' => $this->cdn($scopedPath),
            'origin' => $this->origin($scopedPath),
            'vbucket' => $vBucketId,
        ];
    }

    /**
     * Get the CDN delivery URL for a path.
     * Always returns the BunnyCDN pull zone URL.
     *
     * @param  string  $path  Path relative to storage root
     */
    public function cdn(string $path): string
    {
        return $this->buildUrl(config('cdn.urls.cdn'), $path);
    }

    /**
     * Get the origin URL for a path (Hetzner public bucket).
     * Direct access to origin storage, bypassing CDN.
     *
     * @param  string  $path  Path relative to storage root
     */
    public function origin(string $path): string
    {
        return $this->buildUrl(config('cdn.urls.public'), $path);
    }

    /**
     * Get the private storage URL for a path.
     * For DRM/gated content - not publicly accessible.
     *
     * @param  string  $path  Path relative to storage root
     */
    public function private(string $path): string
    {
        return $this->buildUrl(config('cdn.urls.private'), $path);
    }

    /**
     * Get a signed URL for private CDN content with token authentication.
     * Generates time-limited access URLs for gated/DRM content.
     *
     * @param  string  $path  Path relative to storage root
     * @param  int  $expiry  Expiry time in seconds (default 1 hour)
     * @return string|null Signed URL or null if token not configured
     */
    public function signedUrl(string $path, int $expiry = 3600): ?string
    {
        $token = config('cdn.bunny.private.token');

        if (empty($token)) {
            return null;
        }

        $pullZone = config('cdn.bunny.private.pull_zone', 'hostuk.b-cdn.net');
        $expires = time() + $expiry;
        $path = '/'.ltrim($path, '/');

        // BunnyCDN token authentication format
        // hash = base64(sha256(token + path + expires))
        $hashableBase = $token.$path.$expires;
        $hash = base64_encode(hash('sha256', $hashableBase, true));

        // URL-safe base64
        $hash = str_replace(['+', '/'], ['-', '_'], $hash);
        $hash = rtrim($hash, '=');

        return "https://{$pullZone}{$path}?token={$hash}&expires={$expires}";
    }

    /**
     * Get the apex domain URL for a path.
     * Fallback for assets served through main domain.
     *
     * @param  string  $path  Path relative to web root
     */
    public function apex(string $path): string
    {
        return $this->buildUrl(config('cdn.urls.apex'), $path);
    }

    /**
     * Get context-aware URL for a path.
     * Automatically determines whether to return CDN or origin URL.
     *
     * @param  string  $path  Path relative to storage root
     * @param  string|null  $context  Force context ('admin', 'public', or null for auto)
     */
    public function asset(string $path, ?string $context = null): string
    {
        $context = $context ?? $this->detectContext();

        return $context === 'admin' ? $this->origin($path) : $this->cdn($path);
    }

    /**
     * Get both CDN and origin URLs for API responses.
     *
     * @param  string  $path  Path relative to storage root
     * @return array{cdn: string, origin: string}
     */
    public function urls(string $path): array
    {
        return [
            'cdn' => $this->cdn($path),
            'origin' => $this->origin($path),
        ];
    }

    /**
     * Get all URLs for a path (including private and apex).
     *
     * @param  string  $path  Path relative to storage root
     * @return array{cdn: string, origin: string, private: string, apex: string}
     */
    public function allUrls(string $path): array
    {
        return [
            'cdn' => $this->cdn($path),
            'origin' => $this->origin($path),
            'private' => $this->private($path),
            'apex' => $this->apex($path),
        ];
    }

    /**
     * Detect the current request context.
     *
     * @return string 'admin' or 'public'
     */
    public function detectContext(): string
    {
        // Check for admin headers
        foreach (config('cdn.context.admin_headers', []) as $header) {
            if (Request::hasHeader($header)) {
                return 'admin';
            }
        }

        // Check for admin route prefixes
        $path = Request::path();
        foreach (config('cdn.context.admin_prefixes', []) as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return 'admin';
            }
        }

        return config('cdn.context.default', 'public');
    }

    /**
     * Check if the current context is admin/internal.
     */
    public function isAdminContext(): bool
    {
        return $this->detectContext() === 'admin';
    }

    /**
     * Push a file to the CDN storage zone.
     *
     * @param  string  $disk  Laravel disk name ('hetzner-public' or 'hetzner-private')
     * @param  string  $path  Path within the disk
     * @param  string  $zone  Target zone ('public' or 'private')
     */
    public function pushToCdn(string $disk, string $path, string $zone = 'public'): bool
    {
        if (! $this->bunnyStorage->isPushEnabled()) {
            return false;
        }

        return $this->bunnyStorage->copyFromDisk($disk, $path, $zone);
    }

    /**
     * Delete a file from the CDN storage zone.
     *
     * @param  string  $path  Path within the storage zone
     * @param  string  $zone  Target zone ('public' or 'private')
     */
    public function deleteFromCdn(string $path, string $zone = 'public'): bool
    {
        return $this->bunnyStorage->delete($path, $zone);
    }

    /**
     * Purge a path from the CDN cache.
     * Uses the existing BunnyCdnService for pull zone API.
     *
     * @param  string  $path  Path to purge
     */
    public function purge(string $path): bool
    {
        // Use existing BunnyCdnService for pull zone operations
        $bunnyCdnService = app(BunnyCdnService::class);

        if (! $bunnyCdnService->isConfigured()) {
            return false;
        }

        return $bunnyCdnService->purgeUrl($this->cdn($path));
    }

    /**
     * Get cached CDN URL with intelligent caching.
     *
     * @param  string  $path  Path relative to storage root
     * @param  string|null  $context  Force context ('admin', 'public', or null for auto)
     */
    public function cachedAsset(string $path, ?string $context = null): string
    {
        if (! config('cdn.cache.enabled', true)) {
            return $this->asset($path, $context);
        }

        $context = $context ?? $this->detectContext();
        $cacheKey = config('cdn.cache.prefix', 'cdn_url').':'.$context.':'.md5($path);
        $ttl = config('cdn.cache.ttl', 3600);

        return Cache::remember($cacheKey, $ttl, fn () => $this->asset($path, $context));
    }

    /**
     * Build a URL from base URL and path.
     */
    protected function buildUrl(?string $baseUrl, string $path): string
    {
        if (empty($baseUrl)) {
            // Fallback to apex domain if no base URL configured
            $baseUrl = config('cdn.urls.apex', config('app.url'));
        }

        $baseUrl = rtrim($baseUrl, '/');
        $path = ltrim($path, '/');

        return "{$baseUrl}/{$path}";
    }

    /**
     * Get the public storage disk.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function publicDisk()
    {
        return Storage::disk(config('cdn.disks.public', 'hetzner-public'));
    }

    /**
     * Get the private storage disk.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public function privateDisk()
    {
        return Storage::disk(config('cdn.disks.private', 'hetzner-private'));
    }

    /**
     * Store file to public bucket and optionally push to CDN.
     *
     * @param  string  $path  Target path
     * @param  string|resource  $contents  File contents or stream
     * @param  bool  $pushToCdn  Whether to also push to BunnyCDN storage zone
     */
    public function storePublic(string $path, $contents, bool $pushToCdn = true): bool
    {
        $stored = $this->publicDisk()->put($path, $contents);

        if ($stored && $pushToCdn && config('cdn.pipeline.auto_push', true)) {
            // Queue the push if configured, otherwise push synchronously
            if ($queue = config('cdn.pipeline.queue')) {
                dispatch(new \Core\Cdn\Jobs\PushAssetToCdn('hetzner-public', $path, 'public'))->onQueue($queue);
            } else {
                $this->pushToCdn('hetzner-public', $path, 'public');
            }
        }

        return $stored;
    }

    /**
     * Store file to private bucket and optionally push to CDN.
     *
     * @param  string  $path  Target path
     * @param  string|resource  $contents  File contents or stream
     * @param  bool  $pushToCdn  Whether to also push to BunnyCDN storage zone
     */
    public function storePrivate(string $path, $contents, bool $pushToCdn = true): bool
    {
        $stored = $this->privateDisk()->put($path, $contents);

        if ($stored && $pushToCdn && config('cdn.pipeline.auto_push', true)) {
            if ($queue = config('cdn.pipeline.queue')) {
                dispatch(new \Core\Cdn\Jobs\PushAssetToCdn('hetzner-private', $path, 'private'))->onQueue($queue);
            } else {
                $this->pushToCdn('hetzner-private', $path, 'private');
            }
        }

        return $stored;
    }

    /**
     * Delete file from storage and CDN.
     *
     * @param  string  $path  File path
     * @param  string  $bucket  'public' or 'private'
     */
    public function deleteAsset(string $path, string $bucket = 'public'): bool
    {
        $disk = $bucket === 'private' ? $this->privateDisk() : $this->publicDisk();
        $deleted = $disk->delete($path);

        if ($deleted) {
            $this->deleteFromCdn($path, $bucket);

            if (config('cdn.pipeline.auto_purge', true)) {
                $this->purge($path);
            }
        }

        return $deleted;
    }

    /**
     * Get the path prefix for a content category.
     *
     * @param  string  $category  Category key from config (media, social, biolink, etc.)
     */
    public function pathPrefix(string $category): string
    {
        return config("cdn.paths.{$category}", $category);
    }

    /**
     * Build a full path with category prefix.
     *
     * @param  string  $category  Category key
     * @param  string  $path  Relative path within category
     */
    public function categoryPath(string $category, string $path): string
    {
        $prefix = $this->pathPrefix($category);

        return "{$prefix}/{$path}";
    }
}
