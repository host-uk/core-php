<?php

namespace Core\Mod\Web\Models;

use Core\Mod\Tenant\Concerns\BelongsToWorkspace;
use Core\Mod\Tenant\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use BelongsToWorkspace;
    use SoftDeletes;

    protected $table = 'biolink_domains';

    protected $fillable = [
        'workspace_id',
        'user_id',
        'host',
        'scheme',
        'biolink_id',
        'custom_index_url',
        'custom_not_found_url',
        'is_enabled',
        'verification_status',
        'verification_token',
        'verified_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Verification status options.
     */
    public const VERIFICATION_PENDING = 'pending';

    public const VERIFICATION_VERIFIED = 'verified';

    public const VERIFICATION_FAILED = 'failed';

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'verification_status' => 'pending',
        'scheme' => 'https',
        'is_enabled' => false,
    ];

    /**
     * Get the user that owns this domain.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the exclusive biolink for this domain (if any).
     */
    public function exclusiveLink(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'biolink_id');
    }

    /**
     * Get all biolinks using this domain.
     */
    public function biolinks(): HasMany
    {
        return $this->hasMany(Page::class, 'domain_id');
    }

    /**
     * Scope a query to only include enabled domains.
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Get the full base URL for this domain.
     */
    public function getBaseUrlAttribute(): string
    {
        return $this->scheme.'://'.$this->host;
    }

    /**
     * Check if this domain is exclusive to a single bio.
     */
    public function isExclusive(): bool
    {
        return ! is_null($this->biolink_id);
    }

    /**
     * Get the index URL or default.
     */
    public function getIndexUrl(): ?string
    {
        if ($this->custom_index_url) {
            return $this->custom_index_url;
        }

        if ($this->isExclusive()) {
            return $this->base_url;
        }

        return null;
    }

    /**
     * Get the 404 URL or null for default handling.
     */
    public function getNotFoundUrl(): ?string
    {
        return $this->custom_not_found_url;
    }

    /**
     * Scope to only verified domains.
     */
    public function scopeVerified($query)
    {
        return $query->where('verification_status', self::VERIFICATION_VERIFIED);
    }

    /**
     * Check if domain is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === self::VERIFICATION_VERIFIED;
    }

    /**
     * Generate a new verification token.
     */
    public function generateVerificationToken(): string
    {
        $this->verification_token = bin2hex(random_bytes(32));
        $this->verification_status = self::VERIFICATION_PENDING;
        $this->save();

        return $this->verification_token;
    }

    /**
     * Mark domain as verified.
     */
    public function markAsVerified(): void
    {
        $this->update([
            'verification_status' => self::VERIFICATION_VERIFIED,
            'verified_at' => now(),
            'is_enabled' => true,
        ]);
    }

    /**
     * Mark verification as failed.
     */
    public function markVerificationFailed(): void
    {
        $this->update([
            'verification_status' => self::VERIFICATION_FAILED,
        ]);
    }

    /**
     * Get the DNS TXT record value for verification.
     */
    public function getDnsVerificationRecord(): string
    {
        return "host-uk-verify={$this->verification_token}";
    }
}
