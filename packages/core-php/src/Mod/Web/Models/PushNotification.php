<?php

namespace Core\Mod\Web\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A push notification message to be sent to subscribers.
 *
 * Creators can send notifications to their fans when they
 * go live, release new content, or have announcements.
 */
class PushNotification extends Model
{
    protected $table = 'biolink_push_notifications';

    protected $fillable = [
        'biolink_id',
        'title',
        'body',
        'url',
        'icon_url',
        'badge_url',
        'segment',
        'segment_value',
        'total_subscribers',
        'sent_count',
        'delivered_count',
        'clicked_count',
        'failed_count',
        'status',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'total_subscribers' => 'integer',
        'sent_count' => 'integer',
        'delivered_count' => 'integer',
        'clicked_count' => 'integer',
        'failed_count' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_SENDING = 'sending';

    public const STATUS_SENT = 'sent';

    public const STATUS_FAILED = 'failed';

    /**
     * Segment constants.
     */
    public const SEGMENT_ALL = 'all';

    public const SEGMENT_DESKTOP = 'desktop';

    public const SEGMENT_MOBILE = 'mobile';

    public const SEGMENT_COUNTRY = 'country';

    /**
     * Get the biolink this notification belongs to.
     */
    public function biolink(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'biolink_id');
    }

    /**
     * Get delivery records for this notification.
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(PushDelivery::class, 'notification_id');
    }

    /**
     * Scope to pending scheduled notifications.
     */
    public function scopeReadyToSend($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Get the notification payload for Web Push.
     */
    public function toPayload(): array
    {
        return array_filter([
            'title' => $this->title,
            'description' => $this->body,
            'url' => $this->url ?? $this->biolink->full_url,
            'icon' => $this->icon_url ?? $this->biolink->pushConfig?->default_icon_url,
            'badge' => $this->badge_url,
        ]);
    }

    /**
     * Get targeted subscribers based on segment.
     */
    public function getTargetedSubscribers()
    {
        $query = PushSubscriber::where('biolink_id', $this->biolink_id)
            ->active();

        return match ($this->segment) {
            self::SEGMENT_DESKTOP => $query->device('desktop'),
            self::SEGMENT_MOBILE => $query->whereIn('device_type', ['mobile', 'tablet']),
            self::SEGMENT_COUNTRY => $query->country($this->segment_value),
            default => $query,
        };
    }

    /**
     * Mark as sending.
     */
    public function markSending(): void
    {
        $this->update(['status' => self::STATUS_SENDING]);
    }

    /**
     * Mark as sent.
     */
    public function markSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Get delivery success rate.
     */
    public function getDeliveryRateAttribute(): float
    {
        if ($this->sent_count === 0) {
            return 0;
        }

        return round(($this->delivered_count / $this->sent_count) * 100, 1);
    }

    /**
     * Get click-through rate.
     */
    public function getClickRateAttribute(): float
    {
        if ($this->delivered_count === 0) {
            return 0;
        }

        return round(($this->clicked_count / $this->delivered_count) * 100, 1);
    }
}
