<?php

namespace Core\Mod\Web\Models;

use Core\Mod\Tenant\Concerns\BelongsToWorkspace;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Web\Effects\Background\BackgroundEffect;
use Core\Mod\Web\Effects\Catalog;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Page extends Model
{
    use BelongsToWorkspace;
    use HasFactory;
    use LogsActivity;
    use SoftDeletes;

    protected static function newFactory()
    {
        return \Core\Mod\Web\Database\Factories\PageFactory::new();
    }

    protected $table = 'biolinks';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'parent_id',
        'project_id',
        'domain_id',
        'theme_id',
        'type',
        'url',
        'location_url',
        'settings',
        'effects',
        'layout_config',
        'clicks',
        'unique_clicks',
        'start_date',
        'end_date',
        'is_enabled',
        'is_verified',
        'last_click_at',
    ];

    protected $casts = [
        'settings' => AsArrayObject::class,
        'effects' => AsArrayObject::class,
        'layout_config' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_click_at' => 'datetime',
        'is_enabled' => 'boolean',
        'is_verified' => 'boolean',
        'clicks' => 'integer',
        'unique_clicks' => 'integer',
    ];

    /**
     * Get the user that owns the bio.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent page (if this is a sub-page).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Get all sub-pages for this page.
     */
    public function subPages(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('url');
    }

    /**
     * Check if this page is a sub-page.
     */
    public function isSubPage(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Check if this page is a root page (not a sub-page).
     */
    public function isRootPage(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * Get the full URL path for this page.
     *
     * For sub-pages: parent-url/sub-url
     * For root pages: url
     */
    public function getUrlPath(): string
    {
        if ($this->isSubPage() && $this->parent) {
            return $this->parent->url . '/' . $this->url;
        }

        return $this->url;
    }

    /**
     * Scope to only root pages (not sub-pages).
     */
    public function scopeRootPages($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to only sub-pages.
     */
    public function scopeSubPages($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Get the project this biolink belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the custom domain for this bio.
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    /**
     * Get the theme for this bio.
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class, 'theme_id');
    }

    /**
     * Get all blocks for this bio.
     */
    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class, 'biolink_id')->orderBy('order');
    }

    /**
     * Get blocks for a specific region, ordered by region_order.
     */
    public function blocksInRegion(string $region): HasMany
    {
        return $this->hasMany(Block::class, 'biolink_id')
            ->where('region', $region)
            ->orderBy('region_order');
    }

    /**
     * Get all blocks grouped by region.
     *
     * @return array<string, \Illuminate\Database\Eloquent\Collection>
     */
    public function getBlocksByRegion(): array
    {
        $blocks = $this->blocks()
            ->orderBy('region')
            ->orderBy('region_order')
            ->get();

        return [
            Block::REGION_HEADER => $blocks->where('region', Block::REGION_HEADER)->values(),
            Block::REGION_LEFT => $blocks->where('region', Block::REGION_LEFT)->values(),
            Block::REGION_CONTENT => $blocks->where('region', Block::REGION_CONTENT)->values(),
            Block::REGION_RIGHT => $blocks->where('region', Block::REGION_RIGHT)->values(),
            Block::REGION_FOOTER => $blocks->where('region', Block::REGION_FOOTER)->values(),
        ];
    }

    /**
     * Get the layout preset for this page.
     *
     * Defaults to 'bio' (content-only) for backwards compatibility.
     */
    public function getLayoutPreset(): string
    {
        return $this->layout_config['preset'] ?? 'bio';
    }

    /**
     * Check if this page uses HLCRF layout (has regions enabled).
     */
    public function hasHlcrfLayout(): bool
    {
        return $this->layout_config !== null && $this->getLayoutPreset() !== 'bio';
    }

    /**
     * Get layout type for a specific breakpoint.
     *
     * Returns: 'C', 'HCF', 'HLCF', 'HCRF', or 'HLCRF'
     */
    public function getLayoutTypeFor(string $breakpoint): string
    {
        $presets = config('bio.layout_presets', []);
        $preset = $this->getLayoutPreset();

        return $presets[$preset][$breakpoint] ?? 'C';
    }

    /**
     * Check if a specific region is enabled at a breakpoint.
     */
    public function isRegionEnabled(string $region, string $breakpoint = 'desktop'): bool
    {
        $layoutType = $this->getLayoutTypeFor($breakpoint);

        return match ($region) {
            'header' => str_starts_with($layoutType, 'H'),
            'left' => str_contains($layoutType, 'L'),
            'content' => true, // Content is always enabled
            'right' => str_contains($layoutType, 'R'),
            'footer' => str_ends_with($layoutType, 'F'),
            default => false,
        };
    }

    /**
     * Get all clicks for this bio.
     */
    public function clickRecords(): HasMany
    {
        return $this->hasMany(Click::class, 'biolink_id');
    }

    /**
     * Get aggregated click statistics for this bio.
     */
    public function clickStats(): HasMany
    {
        return $this->hasMany(ClickStat::class, 'biolink_id');
    }

    /**
     * Get pixels attached to this biolink (many-to-many).
     */
    public function pixels(): BelongsToMany
    {
        return $this->belongsToMany(Pixel::class, 'biolink_pixel', 'biolink_id', 'pixel_id');
    }

    /**
     * Get the PWA configuration for this bio.
     */
    public function pwa(): HasOne
    {
        return $this->hasOne(Pwa::class, 'biolink_id');
    }

    /**
     * Get the push notification config for this bio.
     */
    public function pushConfig(): HasOne
    {
        return $this->hasOne(PushConfig::class, 'biolink_id');
    }

    /**
     * Get push subscribers for this bio.
     */
    public function pushSubscribers(): HasMany
    {
        return $this->hasMany(PushSubscriber::class, 'biolink_id');
    }

    /**
     * Get push notifications sent for this bio.
     */
    public function pushNotifications(): HasMany
    {
        return $this->hasMany(PushNotification::class, 'biolink_id');
    }

    /**
     * Get notification handlers for this bio.
     */
    public function notificationHandlers(): HasMany
    {
        return $this->hasMany(NotificationHandler::class, 'biolink_id');
    }

    /**
     * Get active notification handlers for a specific event.
     */
    public function getActiveHandlersForEvent(string $event): \Illuminate\Database\Eloquent\Collection
    {
        return $this->notificationHandlers()
            ->active()
            ->forEvent($event)
            ->get();
    }

    /**
     * Get the full URL for this bio.
     */
    public function getFullUrlAttribute(): string
    {
        $base = $this->domain
            ? $this->domain->scheme.'://'.$this->domain->host
            : config('bio.default_domain');

        return rtrim($base, '/').'/'.$this->getUrlPath();
    }

    /**
     * Scope a query to only include active bio.
     */
    public function scopeActive($query)
    {
        return $query->where('is_enabled', true)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }

    /**
     * Scope a query to only include biolinks of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Check if this is a biolink page (has blocks).
     */
    public function isBioLinkPage(): bool
    {
        return $this->type === 'biolink';
    }

    /**
     * Check if this is a short link (redirect).
     */
    public function isShortLink(): bool
    {
        return $this->type === 'link';
    }

    /**
     * Check if this is a static HTML page.
     */
    public function isStaticPage(): bool
    {
        return $this->type === 'static';
    }

    /**
     * Increment the click counter.
     */
    public function recordClick(): void
    {
        $this->increment('clicks');
        $this->update(['last_click_at' => now()]);
    }

    /**
     * Get setting value with dot notation support.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Get SEO title.
     */
    public function getSeoTitle(): ?string
    {
        return $this->getSetting('seo.title');
    }

    /**
     * Get SEO description.
     */
    public function getSeoDescription(): ?string
    {
        return $this->getSetting('seo.description');
    }

    /**
     * Get avatar URL from the first avatar block, with robohash fallback.
     */
    public function getAvatarUrl(): string
    {
        $avatarBlock = $this->blocks()->where('type', 'avatar')->first();

        if ($avatarBlock && $image = data_get($avatarBlock->settings, 'image')) {
            // Return full URL if already absolute, otherwise prepend storage path
            if (str_starts_with($image, 'http')) {
                return $image;
            }

            return asset('storage/' . $image);
        }

        // Fallback to robohash cats
        return 'https://robohash.org/' . urlencode($this->url) . '?set=set4&size=200x200';
    }

    /**
     * Get background settings.
     *
     * Returns background from theme if set, then inline settings, then defaults.
     */
    public function getBackground(): array
    {
        // Check if theme is set and has background
        if ($this->theme_id && $this->theme) {
            return $this->theme->getBackground();
        }

        // Check inline theme settings
        $themeSettings = $this->getSetting('theme.background');
        if ($themeSettings) {
            return $themeSettings;
        }

        // Fall back to legacy background setting or defaults
        return $this->getSetting('background', [
            'type' => 'color',
            'color' => '#ffffff',
        ]);
    }

    /**
     * Get effective theme settings for this bio.
     *
     * @return array Theme settings array
     */
    public function getThemeSettings(): array
    {
        // Theme from relationship
        if ($this->theme_id && $this->theme) {
            return $this->theme->settings->toArray();
        }

        // Inline theme settings
        $inlineTheme = $this->getSetting('theme');
        if ($inlineTheme && is_array($inlineTheme)) {
            return array_merge(Theme::getDefaultSettings(), $inlineTheme);
        }

        // Default theme
        return Theme::getDefaultSettings();
    }

    /**
     * Get the font family for this bio.
     */
    public function getFontFamily(): string
    {
        $theme = $this->getThemeSettings();

        return $theme['font_family'] ?? 'Inter';
    }

    /**
     * Get button styling for this bio.
     */
    public function getButtonStyle(): array
    {
        $theme = $this->getThemeSettings();

        return $theme['button'] ?? [
            'background_color' => '#000000',
            'text_color' => '#ffffff',
            'border_radius' => '8px',
            'border_width' => '0',
            'border_color' => null,
        ];
    }

    /**
     * Get text colour for this bio.
     */
    public function getTextColor(): string
    {
        $theme = $this->getThemeSettings();

        return $theme['text_color'] ?? '#000000';
    }

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['url', 'type', 'settings', 'is_enabled', 'domain_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "Biolink {$eventName}");
    }

    /**
     * Get the background effect instance for this page.
     */
    public function getBackgroundEffect(): ?BackgroundEffect
    {
        $config = $this->effects['background'] ?? null;

        if (!$config || !isset($config['effect'])) {
            return null;
        }

        $effectClass = Catalog::getBackgroundEffect($config['effect']);

        if (!$effectClass) {
            return null;
        }

        return new $effectClass((array) $config);
    }

    /**
     * Check if this page has a background effect configured.
     */
    public function hasBackgroundEffect(): bool
    {
        return isset($this->effects['background']['effect']);
    }

    /**
     * Apply theme's suggested effects to this page.
     */
    public function applyThemeSuggestedEffects(): void
    {
        if (!$this->theme) {
            return;
        }

        $suggestedEffects = $this->theme->getSuggestedEffects();

        if (!empty($suggestedEffects)) {
            $this->effects = $suggestedEffects;
            $this->save();
        }
    }

    /**
     * Clear all effects from this page.
     */
    public function clearEffects(): void
    {
        $this->effects = null;
        $this->save();
    }

    /**
     * Clear the public page cache for this biolink.
     */
    public function clearPublicCache(): void
    {
        $cacheKey = "biopage:{$this->domain_id}:{$this->url}";
        \Illuminate\Support\Facades\Cache::forget($cacheKey);

        // Also clear sub-page caches if this is a parent
        if ($this->subPages()->exists()) {
            foreach ($this->subPages as $subPage) {
                $subKey = "biopage:{$this->domain_id}:{$this->url}/{$subPage->url}";
                \Illuminate\Support\Facades\Cache::forget($subKey);
            }
        }
    }
}
