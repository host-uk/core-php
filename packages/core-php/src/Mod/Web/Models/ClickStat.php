<?php

namespace Core\Mod\Web\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aggregated click statistics for efficient dashboard queries.
 *
 * Raw click events are rolled up into this table by hour/day,
 * allowing fast queries without scanning millions of rows.
 */
class ClickStat extends Model
{
    protected $table = 'biolink_click_stats';

    protected $fillable = [
        'biolink_id',
        'block_id',
        'date',
        'hour',
        'clicks',
        'unique_clicks',
        'country_code',
        'device_type',
        'referrer_host',
        'utm_source',
    ];

    protected $casts = [
        'date' => 'date',
        'hour' => 'integer',
        'clicks' => 'integer',
        'unique_clicks' => 'integer',
    ];

    /**
     * Device type options.
     */
    public const DEVICE_DESKTOP = 'desktop';

    public const DEVICE_MOBILE = 'mobile';

    public const DEVICE_TABLET = 'tablet';

    public const DEVICE_OTHER = 'other';

    /**
     * Get the biolink this stat belongs to.
     */
    public function biolink(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'biolink_id');
    }

    /**
     * Get the block this stat belongs to (if applicable).
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'block_id');
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to get daily totals (exclude hourly breakdowns).
     */
    public function scopeDailyTotals($query)
    {
        return $query->whereNull('hour');
    }

    /**
     * Scope to filter by country.
     */
    public function scopeCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope to filter by device type.
     */
    public function scopeDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Get total clicks for a biolink within a date range.
     */
    public static function totalClicksForBiolink(int $biolinkId, $startDate, $endDate): int
    {
        return static::where('biolink_id', $biolinkId)
            ->dailyTotals()
            ->dateRange($startDate, $endDate)
            ->whereNull('country_code')
            ->whereNull('device_type')
            ->whereNull('referrer_host')
            ->whereNull('utm_source')
            ->sum('clicks');
    }

    /**
     * Get clicks grouped by country for a bio.
     */
    public static function clicksByCountry(int $biolinkId, $startDate, $endDate): array
    {
        return static::where('biolink_id', $biolinkId)
            ->dailyTotals()
            ->dateRange($startDate, $endDate)
            ->whereNotNull('country_code')
            ->selectRaw('country_code, SUM(clicks) as total_clicks, SUM(unique_clicks) as total_unique')
            ->groupBy('country_code')
            ->orderByDesc('total_clicks')
            ->get()
            ->toArray();
    }

    /**
     * Get clicks grouped by device type for a bio.
     */
    public static function clicksByDevice(int $biolinkId, $startDate, $endDate): array
    {
        return static::where('biolink_id', $biolinkId)
            ->dailyTotals()
            ->dateRange($startDate, $endDate)
            ->whereNotNull('device_type')
            ->selectRaw('device_type, SUM(clicks) as total_clicks, SUM(unique_clicks) as total_unique')
            ->groupBy('device_type')
            ->orderByDesc('total_clicks')
            ->get()
            ->toArray();
    }

    /**
     * Get clicks grouped by referrer for a bio.
     */
    public static function clicksByReferrer(int $biolinkId, $startDate, $endDate, int $limit = 10): array
    {
        return static::where('biolink_id', $biolinkId)
            ->dailyTotals()
            ->dateRange($startDate, $endDate)
            ->whereNotNull('referrer_host')
            ->selectRaw('referrer_host, SUM(clicks) as total_clicks, SUM(unique_clicks) as total_unique')
            ->groupBy('referrer_host')
            ->orderByDesc('total_clicks')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get daily click trends for a bio.
     */
    public static function dailyTrend(int $biolinkId, $startDate, $endDate): array
    {
        return static::where('biolink_id', $biolinkId)
            ->dailyTotals()
            ->dateRange($startDate, $endDate)
            ->whereNull('country_code')
            ->whereNull('device_type')
            ->whereNull('referrer_host')
            ->whereNull('utm_source')
            ->selectRaw('date, SUM(clicks) as total_clicks, SUM(unique_clicks) as total_unique')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
