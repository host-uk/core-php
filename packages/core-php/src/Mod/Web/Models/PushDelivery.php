<?php

namespace Core\Mod\Web\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tracks delivery of a push notification to a specific subscriber.
 *
 * Used for retry logic and detailed analytics on notification
 * delivery, opens, and clicks.
 */
class PushDelivery extends Model
{
    protected $table = 'biolink_push_deliveries';

    protected $fillable = [
        'notification_id',
        'subscriber_id',
        'status',
        'error_message',
        'retry_count',
        'sent_at',
        'delivered_at',
        'clicked_at',
    ];

    protected $casts = [
        'retry_count' => 'integer',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_CLICKED = 'clicked';

    public const STATUS_FAILED = 'failed';

    /**
     * Maximum retry attempts.
     */
    public const MAX_RETRIES = 3;

    /**
     * Get the notification this delivery belongs to.
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(PushNotification::class, 'notification_id');
    }

    /**
     * Get the subscriber this delivery is for.
     */
    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(PushSubscriber::class, 'subscriber_id');
    }

    /**
     * Scope to pending deliveries.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to failed deliveries that can be retried.
     */
    public function scopeRetryable($query)
    {
        return $query->where('status', self::STATUS_FAILED)
            ->where('retry_count', '<', self::MAX_RETRIES);
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
     * Mark as delivered (via service worker confirmation).
     */
    public function markDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Mark as clicked.
     */
    public function markClicked(): void
    {
        $this->update([
            'status' => self::STATUS_CLICKED,
            'clicked_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    /**
     * Check if this delivery can be retried.
     */
    public function canRetry(): bool
    {
        return $this->status === self::STATUS_FAILED
            && $this->retry_count < self::MAX_RETRIES;
    }
}
