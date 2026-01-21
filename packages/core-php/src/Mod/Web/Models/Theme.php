<?php

namespace Core\Mod\Web\Models;

use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Theme extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'biolink_themes';

    protected $fillable = [
        'user_id',
        'workspace_id',
        'name',
        'slug',
        'settings',
        'is_system',
        'is_premium',
        'is_active',
        'is_gallery',
        'category',
        'preview_image',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'settings' => AsArrayObject::class,
        'is_system' => 'boolean',
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'is_gallery' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Theme $theme) {
            if (empty($theme->slug)) {
                $theme->slug = Str::slug($theme->name);
            }
        });
    }

    /**
     * Get the user who created this theme.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workspace this theme belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get biolinks using this theme.
     */
    public function biolinks(): HasMany
    {
        return $this->hasMany(Page::class, 'theme_id');
    }

    /**
     * Get users who have favourited this theme.
     */
    public function favouritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'theme_favourites', 'theme_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Scope to only system themes.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to only custom (user-created) themes.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope to only active themes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only free themes.
     */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
    }

    /**
     * Scope to only gallery themes (visible in public gallery).
     */
    public function scopeGallery($query)
    {
        return $query->where('is_gallery', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search by name or description.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to include favourite status for a specific user.
     */
    public function scopeWithFavouriteStatus($query, ?User $user)
    {
        if (! $user) {
            return $query;
        }

        return $query->addSelect([
            'is_favourited' => function ($q) use ($user) {
                $q->selectRaw('COUNT(*) > 0')
                    ->from('theme_favourites')
                    ->whereColumn('theme_favourites.theme_id', 'biolink_themes.id')
                    ->where('theme_favourites.user_id', $user->id);
            },
        ]);
    }

    /**
     * Check if this is a system theme.
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Check if this is a user-created custom theme.
     */
    public function isCustom(): bool
    {
        return ! $this->is_system;
    }

    /**
     * Check if this theme is favourited by a user.
     */
    public function isFavouritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->favouritedBy()->where('user_id', $user->id)->exists();
    }

    /**
     * Get available categories for themes.
     */
    public static function getCategories(): array
    {
        return [
            'professional' => 'Professional',
            'creative' => 'Creative',
            'minimal' => 'Minimal',
            'bold' => 'Bold',
            'elegant' => 'Elegant',
            'modern' => 'Modern',
            'classic' => 'Classic',
            'vibrant' => 'Vibrant',
        ];
    }

    /**
     * Get a specific setting with dot notation.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Get the background configuration.
     */
    public function getBackground(): array
    {
        return $this->getSetting('background', [
            'type' => 'color',
            'color' => '#ffffff',
        ]);
    }

    /**
     * Get the button configuration.
     */
    public function getButton(): array
    {
        return $this->getSetting('button', [
            'background_color' => '#000000',
            'text_color' => '#ffffff',
            'border_radius' => '8px',
            'border_width' => '0',
            'border_color' => null,
        ]);
    }

    /**
     * Get the text colour.
     */
    public function getTextColor(): string
    {
        return $this->getSetting('text_color', '#000000');
    }

    /**
     * Get the font family.
     */
    public function getFontFamily(): string
    {
        return $this->getSetting('font_family', 'Inter');
    }

    /**
     * Generate CSS variables from theme settings.
     */
    public function toCssVariables(): array
    {
        $background = $this->getBackground();
        $button = $this->getButton();

        return [
            '--biolink-bg' => $background['color'] ?? '#ffffff',
            '--biolink-bg-type' => $background['type'] ?? 'color',
            '--biolink-bg-gradient-start' => $background['gradient_start'] ?? $background['color'] ?? '#ffffff',
            '--biolink-bg-gradient-end' => $background['gradient_end'] ?? $background['color'] ?? '#ffffff',
            '--biolink-text' => $this->getTextColor(),
            '--biolink-btn-bg' => $button['background_color'] ?? '#000000',
            '--biolink-btn-text' => $button['text_color'] ?? '#ffffff',
            '--biolink-btn-radius' => $button['border_radius'] ?? '8px',
            '--biolink-btn-border-width' => $button['border_width'] ?? '0',
            '--biolink-btn-border-color' => $button['border_color'] ?? 'transparent',
            '--biolink-font' => "'".$this->getFontFamily()."', sans-serif",
        ];
    }

    /**
     * Generate inline CSS style string from variables.
     */
    public function toCssString(): string
    {
        $variables = $this->toCssVariables();

        return collect($variables)
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->implode('; ');
    }

    /**
     * Get default theme settings structure.
     */
    public static function getDefaultSettings(): array
    {
        return [
            'background' => [
                'type' => 'color',
                'color' => '#ffffff',
                'gradient_start' => null,
                'gradient_end' => null,
            ],
            'text_color' => '#000000',
            'button' => [
                'background_color' => '#000000',
                'text_color' => '#ffffff',
                'border_radius' => '8px',
                'border_width' => '0',
                'border_color' => null,
            ],
            'font_family' => 'Inter',
        ];
    }

    /**
     * Get suggested effects for pages using this theme.
     *
     * Maps legacy overlay/blur settings to the new Effects system.
     */
    public function getSuggestedEffects(): array
    {
        $effects = [];
        $background = $this->getBackground();
        $blur = (int) $this->getSetting('background_blur', 0);
        $brightness = (int) $this->getSetting('background_brightness', 100);

        // Map overlay SVGs to effect slugs
        $overlayMap = [
            'rain.svg' => 'rain',
            'leaves.svg' => 'leaves',
            'autumn_leaves.svg' => 'autumn_leaves',
        ];

        // Map animated SVG backgrounds to effect slugs
        $animatedBgMap = [
            'fd6c5d094781b750b77408b7ec03a90f.svg' => 'waves',
            '289e2f60e6eb4d5a8a57394b9aabb8d7.svg' => 'bubbles',
            '2d120dd791037a99206d0dc856f4a0f4.svg' => 'lava_lamp_pink',
            'b634495133c9091655dab3c3c916722e.svg' => 'lava_lamp_purple',
            'a74b6b499633319c462fe4c12a8e9c1d.svg' => 'grid_motion',
            '567753a3181cb7d22b3c4e300e502986.svg' => 'stars',
        ];

        // Check for overlay effects (rain, leaves, etc.)
        $overlay = $background['overlay'] ?? null;
        if ($overlay && isset($overlayMap[$overlay])) {
            $effects['background'] = [
                'effect' => $overlayMap[$overlay],
                'blur' => $blur,
                'brightness' => $brightness ?: 100,
                'opacity' => 80,
                'layers' => 3,
            ];

            return $effects;
        }

        // Check for animated SVG backgrounds
        $image = $background['image'] ?? null;
        if ($image && isset($animatedBgMap[$image])) {
            $effects['background'] = [
                'effect' => $animatedBgMap[$image],
                'blur' => $blur,
                'brightness' => $brightness ?: 100,
                'opacity' => 100,
            ];

            return $effects;
        }

        // If no specific effect but has blur, still include backdrop settings
        if ($blur > 0 || ($brightness > 0 && $brightness !== 100)) {
            $effects['background'] = [
                'effect' => null,
                'blur' => $blur,
                'brightness' => $brightness ?: 100,
            ];
        }

        return $effects;
    }

    /**
     * Check if this theme has suggested effects.
     */
    public function hasSuggestedEffects(): bool
    {
        return !empty($this->getSuggestedEffects());
    }
}
