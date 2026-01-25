<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Config\Models;

use Core\Config\ConfigResolver;
use Core\Config\Enums\ScopeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Configuration value (junction table).
 *
 * Links profiles to keys with actual values.
 * The `locked` flag implements FINAL - prevents child override.
 * The `channel_id` adds voice/context dimension to scoping.
 *
 * @property int $id
 * @property int $profile_id
 * @property int $key_id
 * @property int|null $channel_id
 * @property mixed $value
 * @property bool $locked
 * @property int|null $inherited_from
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ConfigValue extends Model
{
    protected $table = 'config_values';

    protected $fillable = [
        'profile_id',
        'key_id',
        'channel_id',
        'value',
        'locked',
        'inherited_from',
    ];

    protected $casts = [
        'value' => 'json',
        'locked' => 'boolean',
    ];

    /**
     * The profile this value belongs to.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConfigProfile::class, 'profile_id');
    }

    /**
     * The key this value is for.
     */
    public function key(): BelongsTo
    {
        return $this->belongsTo(ConfigKey::class, 'key_id');
    }

    /**
     * Profile this value was inherited from (if any).
     */
    public function inheritedFromProfile(): BelongsTo
    {
        return $this->belongsTo(ConfigProfile::class, 'inherited_from');
    }

    /**
     * The channel this value is scoped to (null = all channels).
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    /**
     * Get typed value.
     */
    public function getTypedValue(): mixed
    {
        $key = $this->key;

        if ($key === null) {
            return $this->value;
        }

        return $key->type->cast($this->value);
    }

    /**
     * Check if this value is locked (FINAL).
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Check if this value was inherited.
     */
    public function isInherited(): bool
    {
        return $this->inherited_from !== null;
    }

    /**
     * Find value for a profile, key, and optional channel.
     */
    public static function findValue(int $profileId, int $keyId, ?int $channelId = null): ?self
    {
        return static::where('profile_id', $profileId)
            ->where('key_id', $keyId)
            ->where('channel_id', $channelId)
            ->first();
    }

    /**
     * Set or update a value.
     *
     * Automatically invalidates the resolved hash so the next read
     * will recompute the value with the new setting.
     */
    public static function setValue(
        int $profileId,
        int $keyId,
        mixed $value,
        bool $locked = false,
        ?int $inheritedFrom = null,
        ?int $channelId = null,
    ): self {
        $configValue = static::updateOrCreate(
            [
                'profile_id' => $profileId,
                'key_id' => $keyId,
                'channel_id' => $channelId,
            ],
            [
                'value' => $value,
                'locked' => $locked,
                'inherited_from' => $inheritedFrom,
            ]
        );

        // Invalidate hash for this key (all scopes)
        // The value will be recomputed on next access
        $key = ConfigKey::find($keyId);
        if ($key === null) {
            return $configValue;
        }

        ConfigResolver::clear($key->code);

        // Also clear from resolved table for this scope
        $profile = ConfigProfile::find($profileId);
        if ($profile === null) {
            return $configValue;
        }

        $workspaceId = $profile->scope_type === ScopeType::WORKSPACE
            ? $profile->scope_id
            : null;

        ConfigResolved::where('key_code', $key->code)
            ->where('workspace_id', $workspaceId)
            ->where('channel_id', $channelId)
            ->delete();

        return $configValue;
    }

    /**
     * Get all values for a key across profiles and channels.
     *
     * Used for batch resolution to avoid N+1.
     *
     * @param  array<int>  $profileIds
     * @param  array<int>|null  $channelIds  Include null for "all channels" values
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function forKeyInProfiles(int $keyId, array $profileIds, ?array $channelIds = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('key_id', $keyId)
            ->whereIn('profile_id', $profileIds)
            ->when($channelIds !== null, function ($query) use ($channelIds) {
                $query->where(function ($q) use ($channelIds) {
                    $q->whereIn('channel_id', $channelIds)
                        ->orWhereNull('channel_id');
                });
            })
            ->get();
    }
}
