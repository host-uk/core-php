<?php

namespace Core\Mod\Web\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Click extends Model
{
    protected $table = 'biolink_clicks';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    protected $fillable = [
        'biolink_id',
        'block_id',
        'visitor_hash',
        'country_code',
        'region',
        'device_type',
        'os_name',
        'browser_name',
        'referrer_host',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'is_unique',
        'created_at',
    ];

    protected $casts = [
        'is_unique' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the biolink this click belongs to.
     */
    public function biolink(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'biolink_id');
    }

    /**
     * Get the block this click was on (if any).
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'block_id');
    }

    /**
     * Scope a query to only include unique clicks.
     */
    public function scopeUnique($query)
    {
        return $query->where('is_unique', true);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeInDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    /**
     * Scope a query to filter by country.
     */
    public function scopeFromCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope a query to filter by device type.
     */
    public function scopeOnDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Get device type label.
     */
    public function getDeviceLabel(): string
    {
        return match ($this->device_type) {
            'desktop' => 'Desktop',
            'tablet' => 'Tablet',
            'mobile' => 'Mobile',
            default => 'Unknown',
        };
    }

    /**
     * Check if this was an organic visit (no referrer).
     */
    public function isOrganic(): bool
    {
        return empty($this->referrer_host);
    }

    /**
     * Check if this came from a UTM campaign.
     */
    public function isFromCampaign(): bool
    {
        return ! empty($this->utm_source) || ! empty($this->utm_campaign);
    }
}
