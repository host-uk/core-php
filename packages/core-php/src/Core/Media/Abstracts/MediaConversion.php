<?php

declare(strict_types=1);

namespace Core\Media\Abstracts;

use Core\Media\Support\MediaConversionData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Abstract base class for media conversions.
 *
 * Provides common functionality for media processing operations
 * such as image resizing, thumbnail generation, and video processing.
 */
abstract class MediaConversion
{
    protected string $filepath;

    protected string $fromDisk = 'local';

    protected string $toDisk = 'local';

    protected string $name = '';

    protected string $suffix = '';

    /**
     * Image MIME types.
     */
    protected const IMAGE_MIMES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/svg+xml',
    ];

    /**
     * Video MIME types.
     */
    protected const VIDEO_MIMES = [
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/x-msvideo',
        'video/x-ms-wmv',
        'video/webm',
        'video/ogg',
        'video/3gpp',
    ];

    /**
     * Get the engine name for this conversion.
     */
    abstract public function getEngineName(): string;

    /**
     * Check if this conversion can be performed.
     */
    abstract public function canPerform(): bool;

    /**
     * Get the output file path.
     */
    abstract public function getPath(): string;

    /**
     * Perform the conversion.
     */
    abstract public function handle(): ?MediaConversionData;

    /**
     * Set the source file path.
     */
    public function filepath(string $filepath): static
    {
        $this->filepath = $filepath;

        return $this;
    }

    /**
     * Set the source disk.
     */
    public function fromDisk(string $disk): static
    {
        $this->fromDisk = $disk;

        return $this;
    }

    /**
     * Set the destination disk.
     */
    public function toDisk(string $disk): static
    {
        $this->toDisk = $disk;

        return $this;
    }

    /**
     * Set the conversion name.
     */
    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set the filename suffix.
     */
    public function suffix(string $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    /**
     * Get the source file path.
     */
    public function getFilepath(): string
    {
        return $this->filepath;
    }

    /**
     * Get the source disk.
     */
    public function getFromDisk(): string
    {
        return $this->fromDisk;
    }

    /**
     * Get the destination disk.
     */
    public function getToDisk(): string
    {
        return $this->toDisk;
    }

    /**
     * Get the conversion name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the filename suffix.
     */
    public function getSuffix(): string
    {
        return $this->suffix;
    }

    /**
     * Get a filesystem instance for the given disk.
     */
    protected function filesystem(string $disk): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($disk);
    }

    /**
     * Get the file path with a suffix added before the extension.
     */
    protected function getFilePathWithSuffix(?string $extension = null, ?string $basePath = null): string
    {
        $path = $basePath ?? $this->filepath;
        $directory = dirname($path);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $originalExtension = pathinfo($path, PATHINFO_EXTENSION);
        $ext = $extension ?? $originalExtension;

        $suffix = $this->suffix !== '' ? '-'.$this->suffix : '-'.Str::slug($this->name);

        if ($directory === '.') {
            return $filename.$suffix.'.'.$ext;
        }

        return $directory.'/'.$filename.$suffix.'.'.$ext;
    }

    /**
     * Check if the source file is an image.
     */
    protected function isImage(): bool
    {
        $mimeType = $this->getMimeType();

        return in_array($mimeType, self::IMAGE_MIMES, true);
    }

    /**
     * Check if the source file is a GIF image.
     */
    protected function isGifImage(): bool
    {
        return $this->getMimeType() === 'image/gif';
    }

    /**
     * Check if the source file is a video.
     */
    protected function isVideo(): bool
    {
        $mimeType = $this->getMimeType();

        return in_array($mimeType, self::VIDEO_MIMES, true);
    }

    /**
     * Get the MIME type of the source file.
     */
    protected function getMimeType(): ?string
    {
        $disk = $this->filesystem($this->fromDisk);

        if (! $disk->exists($this->filepath)) {
            return null;
        }

        return $disk->mimeType($this->filepath);
    }
}
