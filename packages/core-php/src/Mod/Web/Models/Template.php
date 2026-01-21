<?php

namespace Core\Mod\Web\Models;

use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Template extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'biolink_templates';

    protected $fillable = [
        'user_id',
        'workspace_id',
        'name',
        'slug',
        'category',
        'description',
        'blocks_json',
        'settings_json',
        'placeholders',
        'preview_image',
        'tags',
        'is_system',
        'is_premium',
        'is_active',
        'sort_order',
        'usage_count',
    ];

    protected $casts = [
        'blocks_json' => AsArrayObject::class,
        'settings_json' => AsArrayObject::class,
        'placeholders' => AsArrayObject::class,
        'tags' => 'array',
        'is_system' => 'boolean',
        'is_premium' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'usage_count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Template $template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });
    }

    /**
     * Get the user who created this template.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workspace this template belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Scope to only system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to only custom (user-created) templates.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope to only active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only free templates.
     */
    public function scopeFree($query)
    {
        return $query->where('is_premium', false);
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
     * Check if this is a system template.
     */
    public function isSystem(): bool
    {
        return $this->is_system;
    }

    /**
     * Check if this is a user-created custom template.
     */
    public function isCustom(): bool
    {
        return ! $this->is_system;
    }

    /**
     * Increment the usage counter.
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get all placeholder variable names from the template.
     *
     * Scans blocks and settings for {{variable}} patterns.
     */
    public function getPlaceholderVariables(): array
    {
        $variables = [];

        // Extract from blocks
        $this->extractPlaceholdersFromArray($this->blocks_json->toArray(), $variables);

        // Extract from settings
        $this->extractPlaceholdersFromArray($this->settings_json->toArray(), $variables);

        return array_unique($variables);
    }

    /**
     * Recursively extract {{variable}} patterns from an array.
     */
    protected function extractPlaceholdersFromArray(array $data, array &$variables): void
    {
        foreach ($data as $value) {
            if (is_string($value)) {
                preg_match_all('/\{\{(\w+)\}\}/', $value, $matches);
                if (! empty($matches[1])) {
                    $variables = array_merge($variables, $matches[1]);
                }
            } elseif (is_array($value)) {
                $this->extractPlaceholdersFromArray($value, $variables);
            }
        }
    }

    /**
     * Replace placeholder variables in a value.
     */
    public function replacePlaceholders(string $value, array $replacements): string
    {
        foreach ($replacements as $key => $replacement) {
            $value = str_replace('{{'.$key.'}}', $replacement, $value);
        }

        return $value;
    }

    /**
     * Replace placeholders in the entire blocks array.
     */
    public function getBlocksWithReplacements(array $replacements): array
    {
        return $this->replacePlaceholdersInArray($this->blocks_json->toArray(), $replacements);
    }

    /**
     * Replace placeholders in the entire settings array.
     */
    public function getSettingsWithReplacements(array $replacements): array
    {
        return $this->replacePlaceholdersInArray($this->settings_json->toArray(), $replacements);
    }

    /**
     * Recursively replace placeholders in an array.
     */
    protected function replacePlaceholdersInArray(array $data, array $replacements): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $result[$key] = $this->replacePlaceholders($value, $replacements);
            } elseif (is_array($value)) {
                $result[$key] = $this->replacePlaceholdersInArray($value, $replacements);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Get default placeholder values.
     */
    public function getDefaultPlaceholders(): array
    {
        if ($this->placeholders) {
            return $this->placeholders->toArray();
        }

        return [];
    }

    /**
     * Get available template categories.
     */
    public static function getCategories(): array
    {
        return [
            'business' => 'Business',
            'creative' => 'Creative',
            'portfolio' => 'Portfolio',
            'personal' => 'Personal',
            'events' => 'Events',
            'ecommerce' => 'E-commerce',
            'nonprofit' => 'Non-profit',
            'restaurant' => 'Restaurant & Food',
            'healthcare' => 'Healthcare',
            'education' => 'Education',
            'other' => 'Other',
        ];
    }

    /**
     * Get category display name.
     */
    public function getCategoryName(): string
    {
        $categories = self::getCategories();

        return $categories[$this->category] ?? ucfirst($this->category);
    }
}
