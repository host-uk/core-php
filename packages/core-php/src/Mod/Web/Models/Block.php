<?php

namespace Core\Mod\Web\Models;

use Core\Mod\Web\Services\BlockConditionService;
use Core\Mod\Tenant\Models\User;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class Block extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Core\Mod\Web\Database\Factories\BlockFactory::new();
    }

    protected $table = 'biolink_blocks';

    /**
     * Valid region codes for HLCRF layout.
     */
    public const REGION_HEADER = 'header';

    public const REGION_LEFT = 'left';

    public const REGION_CONTENT = 'content';

    public const REGION_RIGHT = 'right';

    public const REGION_FOOTER = 'footer';

    /**
     * Map of region codes to short codes used in rendering.
     */
    public const REGION_SHORT_CODES = [
        'header' => 'H',
        'left' => 'L',
        'content' => 'C',
        'right' => 'R',
        'footer' => 'F',
    ];

    protected $fillable = [
        'workspace_id',
        'biolink_id',
        'type',
        'region',
        'location_url',
        'settings',
        'order',
        'region_order',
        'breakpoint_visibility',
        'clicks',
        'start_date',
        'end_date',
        'is_enabled',
    ];

    protected $casts = [
        'settings' => AsArrayObject::class,
        'breakpoint_visibility' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_enabled' => 'boolean',
        'order' => 'integer',
        'region_order' => 'integer',
        'clicks' => 'integer',
    ];

    /**
     * Get the biolink this block belongs to.
     */
    public function biolink(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'biolink_id');
    }

    /**
     * Get the user that owns this block (through biolink).
     */
    public function user(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Page::class,
            'id',        // Foreign key on biolinks
            'id',        // Foreign key on users
            'biolink_id', // Local key on blocks
            'user_id'    // Local key on biolinks
        );
    }

    /**
     * Get all clicks for this block.
     */
    public function clickRecords(): HasMany
    {
        return $this->hasMany(Click::class, 'block_id');
    }

    /**
     * Scope a query to only include enabled blocks.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope a query to only include active blocks (enabled and within schedule).
     */
    public function scopeActive($query)
    {
        return $query->where('is_enabled', true)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', now()))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()));
    }

    /**
     * Scope a query to blocks in a specific region.
     */
    public function scopeInRegion($query, string $region)
    {
        return $query->where('region', $region)->orderBy('region_order');
    }

    /**
     * Scope a query to blocks in the content region (default).
     */
    public function scopeInContent($query)
    {
        return $query->inRegion(self::REGION_CONTENT);
    }

    /**
     * Check if this block is visible at a specific breakpoint.
     *
     * If breakpoint_visibility is null, block is visible at all breakpoints.
     */
    public function isVisibleAt(string $breakpoint): bool
    {
        if ($this->breakpoint_visibility === null) {
            return true;
        }

        return in_array($breakpoint, $this->breakpoint_visibility, true);
    }

    /**
     * Get the short region code (H, L, C, R, F) for this block.
     */
    public function getRegionShortCode(): string
    {
        return self::REGION_SHORT_CODES[$this->region] ?? 'C';
    }

    /**
     * Get the HLCRF hierarchical ID for this block.
     *
     * Format: {region}-{position} e.g. H-1, C-3, F-2
     * For nested layouts: {parent}-{region}-{position} e.g. H-C-1
     *
     * @param  string|null  $parentPrefix  Parent path for nested layouts (e.g. 'H-' for header's children)
     */
    public function getHlcrfId(?string $parentPrefix = null): string
    {
        $regionCode = $this->getRegionShortCode();
        $position = $this->region_order + 1; // 1-indexed for humans

        if ($parentPrefix) {
            return $parentPrefix.$regionCode.'-'.$position;
        }

        return $regionCode.'-'.$position;
    }

    /**
     * Check if this block is in the content region.
     */
    public function isInContent(): bool
    {
        return $this->region === self::REGION_CONTENT || $this->region === null;
    }

    /**
     * Check if this block type is allowed in the specified region.
     */
    public function isAllowedInRegion(string $region): bool
    {
        $allowedRegions = $this->getAllowedRegions();

        // If no restrictions, only content is allowed (safe default)
        if ($allowedRegions === null) {
            return $region === self::REGION_CONTENT;
        }

        $shortCode = self::REGION_SHORT_CODES[$region] ?? 'C';

        return in_array($shortCode, $allowedRegions, true);
    }

    /**
     * Get allowed regions for this block type from config.
     *
     * Returns null if not configured (defaults to content-only).
     */
    public function getAllowedRegions(): ?array
    {
        return config("bio.block_types.{$this->type}.allowed_regions");
    }

    /**
     * Check if this block is currently active (basic schedule check only).
     *
     * For full conditional display checks (device, geo, etc.),
     * use shouldDisplay() with a Request object.
     */
    public function isActive(): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if this block should be displayed for a given request.
     *
     * Evaluates all conditions: schedule, device, geo, browser, OS, language.
     */
    public function shouldDisplay(?Request $request = null): bool
    {
        // Without a request, fall back to basic isActive check
        if (! $request) {
            return $this->isActive();
        }

        // Use the condition service for full evaluation
        $conditionService = app(BlockConditionService::class);

        return $conditionService->shouldDisplay($this, $request);
    }

    /**
     * Check if this block has any display conditions set.
     */
    public function hasConditions(): bool
    {
        $conditions = $this->getSetting('conditions', []);

        return ! empty($conditions);
    }

    /**
     * Get a summary of conditions for display in the editor.
     */
    public function getConditionsSummary(): array
    {
        $conditions = $this->getSetting('conditions', []);
        $summary = [];

        if (! empty($conditions['devices'])) {
            $summary['devices'] = implode(', ', $conditions['devices']);
        }

        if (! empty($conditions['countries'])) {
            $summary['countries'] = implode(', ', $conditions['countries']);
        }

        if (! empty($conditions['exclude_countries'])) {
            $summary['exclude_countries'] = implode(', ', $conditions['exclude_countries']);
        }

        if (! empty($conditions['browsers'])) {
            $summary['browsers'] = implode(', ', $conditions['browsers']);
        }

        if (! empty($conditions['operating_systems'])) {
            $summary['operating_systems'] = implode(', ', $conditions['operating_systems']);
        }

        if (! empty($conditions['languages'])) {
            $summary['languages'] = implode(', ', $conditions['languages']);
        }

        if (! empty($conditions['schedule'])) {
            $schedule = $conditions['schedule'];
            $parts = [];
            if (isset($schedule['start'])) {
                $parts[] = 'from '.$schedule['start'];
            }
            if (isset($schedule['end'])) {
                $parts[] = 'until '.$schedule['end'];
            }
            if (isset($schedule['days'])) {
                $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                $days = array_map(fn ($d) => $dayNames[$d] ?? $d, $schedule['days']);
                $parts[] = implode(', ', $days);
            }
            if ($parts) {
                $summary['schedule'] = implode(' ', $parts);
            }
        }

        return $summary;
    }

    /**
     * Render the block using its Blade component.
     */
    public function render(): string
    {
        $viewName = "lthn::bio.blocks.{$this->type}";

        // Fall back to generic block if specific template doesn't exist
        if (! View::exists($viewName)) {
            $viewName = 'lthn::bio.blocks.generic';
        }

        return view($viewName, [
            'block' => $this,
            'settings' => $this->settings ?? new \ArrayObject,
        ])->render();
    }

    /**
     * Get setting value with dot notation support.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Get block type configuration.
     */
    public function getTypeConfig(): array
    {
        return config("bio.block_types.{$this->type}", [
            'name' => ucfirst($this->type),
            'icon' => 'square',
            'category' => 'other',
        ]);
    }

    /**
     * Increment the click counter.
     */
    public function recordClick(): void
    {
        $this->increment('clicks');
    }

    /**
     * Check if this block is a link type that should track clicks.
     */
    public function isClickable(): bool
    {
        return in_array($this->type, [
            'link',
            'socials',
            'youtube',
            'spotify',
            'soundcloud',
            'tiktok_video',
            'twitch',
            'vimeo',
            'paypal',
            'vcard',
        ]) || ! empty($this->location_url);
    }
}
