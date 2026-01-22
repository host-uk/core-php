<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Cdn\Services;

use Core\Config\ConfigService;
use Core\Crypt\LthnHash;
use Bunny\Storage\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * BunnyCDN Storage Zone service for direct file operations.
 *
 * Manages file uploads/downloads to BunnyCDN storage zones:
 * - Public zone: General assets, media
 * - Private zone: DRM/gated content
 *
 * Supports vBucket scoping for workspace-isolated CDN paths.
 */
class BunnyStorageService
{
    protected ?Client $publicClient = null;

    protected ?Client $privateClient = null;

    /**
     * Default maximum file size in bytes (100MB).
     */
    protected const DEFAULT_MAX_FILE_SIZE = 104857600;

    /**
     * Maximum retry attempts for failed uploads.
     */
    protected const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Base delay in milliseconds for exponential backoff.
     */
    protected const RETRY_BASE_DELAY_MS = 100;

    public function __construct(
        protected ConfigService $config,
    ) {}

    /**
     * Get the public storage zone client.
     */
    public function publicClient(): ?Client
    {
        if ($this->publicClient === null && $this->isConfigured('public')) {
            $this->publicClient = new Client(
                $this->config->get('cdn.bunny.storage.public.api_key'),
                $this->config->get('cdn.bunny.storage.public.name'),
                $this->config->get('cdn.bunny.storage.public.region', Client::STORAGE_ZONE_FS_EU)
            );
        }

        return $this->publicClient;
    }

    /**
     * Get the private storage zone client.
     */
    public function privateClient(): ?Client
    {
        if ($this->privateClient === null && $this->isConfigured('private')) {
            $this->privateClient = new Client(
                $this->config->get('cdn.bunny.storage.private.api_key'),
                $this->config->get('cdn.bunny.storage.private.name'),
                $this->config->get('cdn.bunny.storage.private.region', Client::STORAGE_ZONE_FS_EU)
            );
        }

        return $this->privateClient;
    }

    /**
     * Check if a storage zone is configured.
     */
    public function isConfigured(string $zone = 'public'): bool
    {
        return $this->config->isConfigured("cdn.bunny.storage.{$zone}");
    }

    /**
     * Check if CDN push is enabled.
     */
    public function isPushEnabled(): bool
    {
        return (bool) $this->config->get('cdn.bunny.push_enabled', false);
    }

    /**
     * List files in a storage zone path.
     */
    public function list(string $path, string $zone = 'public'): array
    {
        $client = $zone === 'private' ? $this->privateClient() : $this->publicClient();

        if (! $client) {
            return [];
        }

        try {
            return $client->listFiles($path);
        } catch (\Exception $e) {
            Log::error('BunnyStorage: Failed to list files', [
                'path' => $path,
                'zone' => $zone,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Upload a file to storage zone.
     */
    public function upload(string $localPath, string $remotePath, string $zone = 'public'): bool
    {
        $client = $zone === 'private' ? $this->privateClient() : $this->publicClient();

        if (! $client) {
            Log::warning('BunnyStorage: Client not configured', ['zone' => $zone]);

            return false;
        }

        if (! file_exists($localPath)) {
            Log::error('BunnyStorage: Local file not found', ['local' => $localPath]);

            return false;
        }

        $fileSize = filesize($localPath);
        $maxSize = $this->getMaxFileSize();

        if ($fileSize === false || $fileSize > $maxSize) {
            Log::error('BunnyStorage: File size exceeds limit', [
                'local' => $localPath,
                'size' => $fileSize,
                'max_size' => $maxSize,
            ]);

            return false;
        }

        return $this->executeWithRetry(function () use ($client, $localPath, $remotePath, $zone) {
            $client->upload($localPath, $remotePath);

            return true;
        }, [
            'local' => $localPath,
            'remote' => $remotePath,
            'zone' => $zone,
        ], 'Upload');
    }

    /**
     * Get the maximum allowed file size in bytes.
     */
    protected function getMaxFileSize(): int
    {
        return (int) $this->config->get('cdn.bunny.max_file_size', self::DEFAULT_MAX_FILE_SIZE);
    }

    /**
     * Execute an operation with exponential backoff retry.
     */
    protected function executeWithRetry(callable $operation, array $context, string $operationName): bool
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRY_ATTEMPTS; $attempt++) {
            try {
                return $operation();
            } catch (\Exception $e) {
                $lastException = $e;

                if ($attempt < self::MAX_RETRY_ATTEMPTS) {
                    $delayMs = self::RETRY_BASE_DELAY_MS * (2 ** ($attempt - 1));
                    usleep($delayMs * 1000);

                    Log::warning("BunnyStorage: {$operationName} attempt {$attempt} failed, retrying", array_merge($context, [
                        'attempt' => $attempt,
                        'next_delay_ms' => $delayMs * 2,
                    ]));
                }
            }
        }

        Log::error("BunnyStorage: {$operationName} failed after " . self::MAX_RETRY_ATTEMPTS . ' attempts', array_merge($context, [
            'error' => $lastException?->getMessage() ?? 'Unknown error',
        ]));

        return false;
    }

    /**
     * Upload file contents directly.
     */
    public function putContents(string $remotePath, string $contents, string $zone = 'public'): bool
    {
        $client = $zone === 'private' ? $this->privateClient() : $this->publicClient();

        if (! $client) {
            return false;
        }

        $contentSize = strlen($contents);
        $maxSize = $this->getMaxFileSize();

        if ($contentSize > $maxSize) {
            Log::error('BunnyStorage: Content size exceeds limit', [
                'remote' => $remotePath,
                'size' => $contentSize,
                'max_size' => $maxSize,
            ]);

            return false;
        }

        return $this->executeWithRetry(function () use ($client, $remotePath, $contents) {
            $client->putContents($remotePath, $contents);

            return true;
        }, [
            'remote' => $remotePath,
            'zone' => $zone,
        ], 'putContents');
    }

    /**
     * Download file contents.
     */
    public function getContents(string $remotePath, string $zone = 'public'): ?string
    {
        $client = $zone === 'private' ? $this->privateClient() : $this->publicClient();

        if (! $client) {
            return null;
        }

        try {
            return $client->getContents($remotePath);
        } catch (\Exception $e) {
            Log::error('BunnyStorage: getContents failed', [
                'remote' => $remotePath,
                'zone' => $zone,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Delete a file from storage zone.
     */
    public function delete(string $remotePath, string $zone = 'public'): bool
    {
        $client = $zone === 'private' ? $this->privateClient() : $this->publicClient();

        if (! $client) {
            return false;
        }

        try {
            $client->delete($remotePath);

            return true;
        } catch (\Exception $e) {
            Log::error('BunnyStorage: Delete failed', [
                'remote' => $remotePath,
                'zone' => $zone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Delete multiple files.
     */
    public function deleteMultiple(array $paths, string $zone = 'public'): array
    {
        $results = [];

        foreach ($paths as $path) {
            $results[$path] = $this->delete($path, $zone);
        }

        return $results;
    }

    /**
     * Copy a file from a Laravel disk to CDN storage zone.
     */
    public function copyFromDisk(string $disk, string $path, string $zone = 'public'): bool
    {
        $diskInstance = Storage::disk($disk);

        if (! $diskInstance->exists($path)) {
            Log::warning('BunnyStorage: Source file not found on disk', [
                'disk' => $disk,
                'path' => $path,
            ]);

            return false;
        }

        $contents = $diskInstance->get($path);

        return $this->putContents($path, $contents, $zone);
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // vBucket operations for workspace-isolated CDN paths
    // ─────────────────────────────────────────────────────────────────────────────

    /**
     * Generate a vBucket ID for a domain/workspace.
     */
    public function vBucketId(string $domain): string
    {
        return LthnHash::vBucketId($domain);
    }

    /**
     * Build a vBucket-scoped path.
     */
    public function vBucketPath(string $domain, string $path): string
    {
        $vBucketId = $this->vBucketId($domain);

        return $vBucketId.'/'.ltrim($path, '/');
    }

    /**
     * Upload content with vBucket scoping.
     */
    public function vBucketPutContents(string $domain, string $path, string $contents, string $zone = 'public'): bool
    {
        $scopedPath = $this->vBucketPath($domain, $path);

        return $this->putContents($scopedPath, $contents, $zone);
    }

    /**
     * Upload file with vBucket scoping.
     */
    public function vBucketUpload(string $domain, string $localPath, string $remotePath, string $zone = 'public'): bool
    {
        $scopedPath = $this->vBucketPath($domain, $remotePath);

        return $this->upload($localPath, $scopedPath, $zone);
    }

    /**
     * Get file contents with vBucket scoping.
     */
    public function vBucketGetContents(string $domain, string $path, string $zone = 'public'): ?string
    {
        $scopedPath = $this->vBucketPath($domain, $path);

        return $this->getContents($scopedPath, $zone);
    }

    /**
     * Delete file with vBucket scoping.
     */
    public function vBucketDelete(string $domain, string $path, string $zone = 'public'): bool
    {
        $scopedPath = $this->vBucketPath($domain, $path);

        return $this->delete($scopedPath, $zone);
    }

    /**
     * List files within a vBucket.
     */
    public function vBucketList(string $domain, string $path = '', string $zone = 'public'): array
    {
        $scopedPath = $this->vBucketPath($domain, $path);

        return $this->list($scopedPath, $zone);
    }
}
