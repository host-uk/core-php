<?php

namespace Core\Mod\Web\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Minishlink\WebPush\VAPID;

/**
 * Push notification configuration for a bio.
 *
 * Each biolink has its own VAPID keys for push notification
 * isolation - subscribers are specific to that creator's page.
 */
class PushConfig extends Model
{
    protected $table = 'biolink_push_configs';

    protected $fillable = [
        'biolink_id',
        'vapid_public_key',
        'vapid_private_key',
        'default_icon_url',
        'prompt_enabled',
        'prompt_delay_seconds',
        'prompt_min_pageviews',
        'is_enabled',
    ];

    protected $casts = [
        'prompt_enabled' => 'boolean',
        'prompt_delay_seconds' => 'integer',
        'prompt_min_pageviews' => 'integer',
        'is_enabled' => 'boolean',
    ];

    /**
     * Hide private key from serialisation.
     */
    protected $hidden = [
        'vapid_private_key',
    ];

    /**
     * Get the biolink this config belongs to.
     */
    public function biolink(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'biolink_id');
    }

    /**
     * Get all subscribers for this push config.
     */
    public function subscribers(): HasMany
    {
        return $this->hasMany(PushSubscriber::class, 'biolink_id', 'biolink_id');
    }

    /**
     * Get active subscribers count.
     */
    public function getActiveSubscribersCountAttribute(): int
    {
        return $this->subscribers()->where('is_active', true)->count();
    }

    /**
     * Generate new VAPID keys for this config.
     */
    public static function generateVapidKeys(): array
    {
        return VAPID::createVapidKeys();
    }

    /**
     * Create a new push config with fresh VAPID keys.
     */
    public static function createForBiolink(int $biolinkId, array $attributes = []): self
    {
        $keys = self::generateVapidKeys();

        return self::create(array_merge([
            'biolink_id' => $biolinkId,
            'vapid_public_key' => $keys['publicKey'],
            'vapid_private_key' => $keys['privateKey'],
        ], $attributes));
    }
}
