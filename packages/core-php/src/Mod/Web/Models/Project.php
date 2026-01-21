<?php

namespace Core\Mod\Web\Models;

use Core\Mod\Tenant\Concerns\BelongsToWorkspace;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use BelongsToWorkspace;
    use SoftDeletes;

    protected $table = 'biolink_projects';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'name',
        'color',
    ];

    /**
     * Default colours for the colour picker.
     */
    public const COLOURS = [
        '#6366f1' => 'Indigo',
        '#8b5cf6' => 'Violet',
        '#ec4899' => 'Pink',
        '#ef4444' => 'Red',
        '#f97316' => 'Orange',
        '#eab308' => 'Yellow',
        '#22c55e' => 'Green',
        '#14b8a6' => 'Teal',
        '#0ea5e9' => 'Sky',
        '#3b82f6' => 'Blue',
        '#6b7280' => 'Grey',
        '#1f2937' => 'Dark',
    ];

    /**
     * Get the workspace that owns this project.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * Get the user that created this project.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all biolinks in this project.
     */
    public function biolinks(): HasMany
    {
        return $this->hasMany(Page::class, 'project_id');
    }

    /**
     * Get the count of biolinks in this project.
     */
    public function getBiolinksCountAttribute(): int
    {
        return $this->biolinks()->count();
    }

    /**
     * Get the count of enabled biolinks in this project.
     */
    public function getActiveBiolinksCountAttribute(): int
    {
        return $this->biolinks()->where('is_enabled', true)->count();
    }

    /**
     * Get the total clicks across all biolinks in this project.
     */
    public function getTotalClicksAttribute(): int
    {
        return $this->biolinks()->sum('clicks');
    }

    /**
     * Scope a query to filter by workspace.
     */
    public function scopeForWorkspace($query, Workspace $workspace)
    {
        return $query->where('workspace_id', $workspace->id);
    }
}
