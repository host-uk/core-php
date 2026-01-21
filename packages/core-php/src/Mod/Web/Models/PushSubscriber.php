<?php

namespace Core\Mod\Web\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A fan who has subscribed to push notifications for a bio.
 *
 * Stores the Web Push subscription details needed to send
 * notifications to this subscriber's browser/device.
 */
class PushSubscriber extends Model
{
    protected $table = 'biolink_push_subscribers';

    protected $fillable = [
        'biolink_id',
        'subscriber_hash',
        'endpoint',
        'key_auth',
        'key_p256dh',
        'country_code',
        'city_name',
        'os_name',
        'browser_name',
        'browser_language',
        'device_type',
        'is_active',
        'last_notification_at',
        'notifications_received',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_notification_at' => 'datetime',
        'notifications_received' => 'integer',
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    /**
     * Get the biolink this subscriber belongs to.
     */
    public function biolink(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'biolink_id');
    }

    /**
     * Scope to active subscribers only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by device type.
     */
    public function scopeDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope to filter by country.
     */
    public function scopeCountry($query, string $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Generate a hash for deduplication.
     */
    public static function generateHash(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /**
     * Get the Web Push subscription array for minishlink/web-push.
     */
    public function toSubscription(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'auth' => $this->key_auth,
                'p256dh' => $this->key_p256dh,
            ],
        ];
    }

    /**
     * Mark as unsubscribed (soft - keeps record for analytics).
     */
    public function unsubscribe(): void
    {
        $this->update([
            'is_active' => false,
            'unsubscribed_at' => now(),
        ]);
    }

    /**
     * Record that a notification was sent.
     */
    public function recordNotificationSent(): void
    {
        $this->update([
            'last_notification_at' => now(),
            'notifications_received' => $this->notifications_received + 1,
        ]);
    }
}
