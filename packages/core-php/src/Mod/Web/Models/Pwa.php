<?php

namespace Core\Mod\Web\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PWA manifest configuration for a bio.
 *
 * Allows fans to "install" the creator's biopage as an app,
 * creating a dedicated fan experience on their device.
 */
class Pwa extends Model
{
    protected $table = 'biolink_pwas';

    protected $fillable = [
        'biolink_id',
        'name',
        'short_name',
        'description',
        'theme_color',
        'background_color',
        'display',
        'orientation',
        'icon_url',
        'icon_maskable_url',
        'screenshots',
        'shortcuts',
        'start_url',
        'scope',
        'lang',
        'dir',
        'installs',
        'is_enabled',
    ];

    protected $casts = [
        'screenshots' => 'array',
        'shortcuts' => 'array',
        'installs' => 'integer',
        'is_enabled' => 'boolean',
    ];

    /**
     * Display mode options.
     */
    public const DISPLAY_STANDALONE = 'standalone';

    public const DISPLAY_FULLSCREEN = 'fullscreen';

    public const DISPLAY_MINIMAL_UI = 'minimal-ui';

    public const DISPLAY_BROWSER = 'browser';

    /**
     * Get the biolink this PWA belongs to.
     */
    public function biolink(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'biolink_id');
    }

    /**
     * Generate the manifest.json content.
     */
    public function toManifest(): array
    {
        $biolink = $this->biolink;
        $baseUrl = $biolink->full_url;

        $manifest = [
            'name' => $this->name,
            'short_name' => $this->short_name ?? $this->name,
            'description' => $this->description,
            'start_url' => $this->start_url ?? $baseUrl,
            'scope' => $this->scope ?? $baseUrl,
            'display' => $this->display,
            'orientation' => $this->orientation,
            'theme_color' => $this->theme_color,
            'background_color' => $this->background_color,
            'lang' => $this->lang,
            'dir' => $this->dir,
            'id' => $baseUrl,
        ];

        // Icons
        $icons = [];
        if ($this->icon_url) {
            $icons[] = [
                'src' => $this->icon_url,
                'sizes' => '512x512',
                'type' => $this->getImageType($this->icon_url),
                'purpose' => 'any',
            ];
        }
        if ($this->icon_maskable_url) {
            $icons[] = [
                'src' => $this->icon_maskable_url,
                'sizes' => '512x512',
                'type' => $this->getImageType($this->icon_maskable_url),
                'purpose' => 'maskable',
            ];
        }
        if (! empty($icons)) {
            $manifest['icons'] = $icons;
        }

        // Screenshots
        if (! empty($this->screenshots)) {
            $manifest['screenshots'] = array_map(function ($screenshot) {
                return [
                    'src' => $screenshot['url'],
                    'type' => $this->getImageType($screenshot['url']),
                    'form_factor' => $screenshot['platform'] ?? 'narrow',
                ];
            }, $this->screenshots);
        }

        // Shortcuts
        if (! empty($this->shortcuts)) {
            $manifest['shortcuts'] = array_map(function ($shortcut) {
                $item = [
                    'name' => $shortcut['name'],
                    'url' => $shortcut['url'],
                ];
                if (! empty($shortcut['description'])) {
                    $item['description'] = $shortcut['description'];
                }
                if (! empty($shortcut['icon_url'])) {
                    $item['icons'] = [[
                        'src' => $shortcut['icon_url'],
                        'type' => $this->getImageType($shortcut['icon_url']),
                    ]];
                }

                return $item;
            }, $this->shortcuts);
        }

        return array_filter($manifest, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Get MIME type from URL extension.
     */
    protected function getImageType(string $url): string
    {
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return match ($extension) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }

    /**
     * Record an install.
     */
    public function recordInstall(): void
    {
        $this->increment('installs');
    }
}
