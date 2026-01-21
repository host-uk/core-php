<?php

declare(strict_types=1);

namespace Core\Media\Image;

use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageOptimizer
{
    protected string $driver;

    protected int $defaultQuality;

    protected int $pngCompression;

    protected int $minSizeKb;

    protected int|float $maxSizeMb;

    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = config('images.optimization.enabled', true);
        $this->driver = config('images.optimization.driver', 'gd');
        $this->defaultQuality = config('images.optimization.quality', 80);
        $this->pngCompression = config('images.optimization.png_compression', 6);
        $this->minSizeKb = config('images.optimization.min_size_kb', 10);
        $this->maxSizeMb = config('images.optimization.max_size_mb', 10);

        // Validate driver availability
        if ($this->driver === 'imagick' && ! extension_loaded('imagick')) {
            Log::warning('ImageOptimizer: imagick driver selected but extension not loaded, falling back to gd');
            $this->driver = 'gd';
        }

        if ($this->driver === 'gd' && ! extension_loaded('gd')) {
            throw new \RuntimeException('ImageOptimizer: GD extension is not loaded');
        }
    }

    /**
     * Optimize an uploaded file.
     */
    public function optimizeUploadedFile(UploadedFile $file, array $options = []): OptimizationResult
    {
        // Get temporary path
        $tempPath = $file->getRealPath();

        return $this->optimizeFile($tempPath, $options);
    }

    /**
     * Optimize an image at the given path.
     *
     * @param  string  $path  Absolute file path
     * @param  array  $options  Override options (quality, driver, etc.)
     */
    public function optimize(string $path, array $options = []): OptimizationResult
    {
        if (! $this->enabled) {
            return $this->createNoOpResult($path);
        }

        // Handle both absolute paths and storage paths
        $absolutePath = $this->resolveAbsolutePath($path);

        return $this->optimizeFile($absolutePath, $options);
    }

    /**
     * Optimize a file and replace it in place.
     */
    protected function optimizeFile(string $absolutePath, array $options = []): OptimizationResult
    {
        if (! file_exists($absolutePath)) {
            throw new \InvalidArgumentException("File not found: {$absolutePath}");
        }

        $originalSize = filesize($absolutePath);

        // Check size constraints
        if ($originalSize < ($this->minSizeKb * 1024)) {
            Log::debug("ImageOptimizer: Skipping file smaller than {$this->minSizeKb}KB", ['path' => $absolutePath]);

            return $this->createNoOpResult($absolutePath);
        }

        if ($originalSize > ($this->maxSizeMb * 1024 * 1024)) {
            Log::debug("ImageOptimizer: Skipping file larger than {$this->maxSizeMb}MB", ['path' => $absolutePath]);

            return $this->createNoOpResult($absolutePath);
        }

        // Detect image type
        $imageInfo = @getimagesize($absolutePath);
        if (! $imageInfo) {
            throw new \InvalidArgumentException("Not a valid image: {$absolutePath}");
        }

        $mimeType = $imageInfo['mime'];
        $quality = $options['quality'] ?? $this->defaultQuality;
        $driver = $options['driver'] ?? $this->driver;

        // Optimize based on mime type
        try {
            $optimizedSize = match ($mimeType) {
                'image/jpeg', 'image/jpg' => $this->optimizeJpeg($absolutePath, $quality, $driver),
                'image/png' => $this->optimizePng($absolutePath, $this->pngCompression, $driver),
                'image/webp' => $this->optimizeWebp($absolutePath, $quality, $driver),
                default => null,
            };

            if ($optimizedSize === null) {
                Log::debug("ImageOptimizer: Unsupported mime type: {$mimeType}");

                return $this->createNoOpResult($absolutePath);
            }

            // Calculate savings
            $percentageSaved = $originalSize > 0
                ? (int) round((($originalSize - $optimizedSize) / $originalSize) * 100)
                : 0;

            // Ensure we don't report negative savings
            $percentageSaved = max(0, $percentageSaved);

            return new OptimizationResult(
                originalSize: $originalSize,
                optimizedSize: $optimizedSize,
                percentageSaved: $percentageSaved,
                path: $absolutePath,
                driver: $driver,
            );
        } catch (\Exception $e) {
            Log::error('ImageOptimizer: Optimization failed', [
                'path' => $absolutePath,
                'error' => $e->getMessage(),
            ]);

            // Return no-op result on failure
            return $this->createNoOpResult($absolutePath);
        }
    }

    /**
     * Optimize JPEG image.
     */
    protected function optimizeJpeg(string $path, int $quality, string $driver): int
    {
        if ($driver === 'gd') {
            $image = @imagecreatefromjpeg($path);
            if (! $image) {
                throw new \RuntimeException('Failed to create image from JPEG');
            }

            // Save with compression
            $success = imagejpeg($image, $path, $quality);
            imagedestroy($image);

            if (! $success) {
                throw new \RuntimeException('Failed to save optimised JPEG');
            }

            return filesize($path);
        }

        // Imagick implementation would go here
        throw new \RuntimeException('Only GD driver is currently implemented for JPEG');
    }

    /**
     * Optimize PNG image.
     */
    protected function optimizePng(string $path, int $compression, string $driver): int
    {
        if ($driver === 'gd') {
            $image = @imagecreatefrompng($path);
            if (! $image) {
                throw new \RuntimeException('Failed to create image from PNG');
            }

            // PNG compression level: 0 (no compression) to 9 (max compression)
            // Config uses 0-9 scale
            $success = imagepng($image, $path, $compression);
            imagedestroy($image);

            if (! $success) {
                throw new \RuntimeException('Failed to save optimised PNG');
            }

            return filesize($path);
        }

        // Imagick implementation would go here
        throw new \RuntimeException('Only GD driver is currently implemented for PNG');
    }

    /**
     * Optimize WebP image.
     */
    protected function optimizeWebp(string $path, int $quality, string $driver): int
    {
        if ($driver === 'gd') {
            $image = @imagecreatefromwebp($path);
            if (! $image) {
                throw new \RuntimeException('Failed to create image from WebP');
            }

            $success = imagewebp($image, $path, $quality);
            imagedestroy($image);

            if (! $success) {
                throw new \RuntimeException('Failed to save optimised WebP');
            }

            return filesize($path);
        }

        // Imagick implementation would go here
        throw new \RuntimeException('Only GD driver is currently implemented for WebP');
    }

    /**
     * Create a no-op result (no optimization performed).
     */
    protected function createNoOpResult(string $path): OptimizationResult
    {
        $size = file_exists($path) ? filesize($path) : 0;

        return new OptimizationResult(
            originalSize: $size,
            optimizedSize: $size,
            percentageSaved: 0,
            path: $path,
            driver: $this->driver,
        );
    }

    /**
     * Resolve path to absolute filesystem path.
     */
    protected function resolveAbsolutePath(string $path): string
    {
        // If already absolute, return as-is
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // If it's a storage path, resolve it
        if (Storage::exists($path)) {
            return Storage::path($path);
        }

        // Try to resolve relative to storage/app
        $storagePath = storage_path('app/'.$path);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        // Return as-is and let the caller handle the error
        return $path;
    }

    /**
     * Get optimization statistics for a workspace.
     */
    public function getStats(?Workspace $workspace = null): array
    {
        return ImageOptimization::getWorkspaceStats($workspace);
    }

    /**
     * Track optimization in database.
     */
    public function recordOptimization(
        OptimizationResult $result,
        ?Workspace $workspace = null,
        $optimizable = null,
        ?string $originalPath = null
    ): ImageOptimization {
        return ImageOptimization::create([
            'path' => $result->path,
            'original_path' => $originalPath,
            'original_size' => $result->originalSize,
            'optimized_size' => $result->optimizedSize,
            'percentage_saved' => $result->percentageSaved,
            'driver' => $result->driver,
            'quality' => $this->defaultQuality,
            'workspace_id' => $workspace?->id,
            'optimizable_type' => $optimizable ? get_class($optimizable) : null,
            'optimizable_id' => $optimizable?->id,
        ]);
    }
}
