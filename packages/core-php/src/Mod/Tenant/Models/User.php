<?php

namespace Core\Mod\Tenant\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Pennant\Concerns\HasFeatures;
use Core\Mod\Tenant\Enums\UserTier;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasFeatures, Notifiable;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): \Core\Mod\Tenant\Database\Factories\UserFactory
    {
        return \Core\Mod\Tenant\Database\Factories\UserFactory::new();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tier',
        'tier_expires_at',
        'referred_by',
        'referral_count',
        'referral_activated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tier' => UserTier::class,
            'tier_expires_at' => 'datetime',
            'cached_stats' => 'array',
            'stats_computed_at' => 'datetime',
            'referral_activated_at' => 'datetime',
        ];
    }

    /**
     * Get all workspaces this user has access to.
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'user_workspace')
            ->withPivot(['role', 'is_default'])
            ->withTimestamps();
    }

    /**
     * Alias for workspaces() - kept for backward compatibility.
     */
    public function hostWorkspaces(): BelongsToMany
    {
        return $this->workspaces();
    }

    /**
     * Get the workspaces owned by this user.
     */
    public function ownedWorkspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class, 'user_workspace')
            ->wherePivot('role', 'owner')
            ->withPivot(['role', 'is_default'])
            ->withTimestamps();
    }

    /**
     * Get the user's tier.
     */
    public function getTier(): UserTier
    {
        // Check if tier has expired
        if ($this->tier_expires_at && $this->tier_expires_at->isPast()) {
            return UserTier::FREE;
        }

        return $this->tier ?? UserTier::FREE;
    }

    /**
     * Check if user is on a paid tier.
     */
    public function isPaid(): bool
    {
        $tier = $this->getTier();

        return $tier === UserTier::APOLLO || $tier === UserTier::HADES;
    }

    /**
     * Check if user is on Hades tier.
     */
    public function isHades(): bool
    {
        return $this->getTier() === UserTier::HADES;
    }

    /**
     * Check if user is on Apollo tier.
     */
    public function isApollo(): bool
    {
        return $this->getTier() === UserTier::APOLLO;
    }

    /**
     * Check if user has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return $this->getTier()->hasFeature($feature);
    }

    /**
     * Get the maximum number of workspaces for this user.
     */
    public function maxWorkspaces(): int
    {
        return $this->getTier()->maxWorkspaces();
    }

    /**
     * Check if user can add more Host Hub workspaces.
     */
    public function canAddHostWorkspace(): bool
    {
        $max = $this->maxWorkspaces();
        if ($max === -1) {
            return true; // Unlimited
        }

        return $this->hostWorkspaces()->count() < $max;
    }

    /**
     * Get the user's default Host Hub workspace.
     */
    public function defaultHostWorkspace(): ?Workspace
    {
        return $this->hostWorkspaces()
            ->wherePivot('is_default', true)
            ->first() ?? $this->hostWorkspaces()->first();
    }

    /**
     * Check if user's email has been verified.
     * Hades accounts are always considered verified.
     */
    public function hasVerifiedEmail(): bool
    {
        // Hades accounts bypass email verification
        if ($this->isHades()) {
            return true;
        }

        return $this->email_verified_at !== null;
    }

    /**
     * Mark the user's email as verified.
     */
    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \Illuminate\Auth\Notifications\VerifyEmail);
    }

    /**
     * Get the email address that should be used for verification.
     */
    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BioLink Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all biolinks owned by this user.
     */
    public function biolinks(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * Get all biolink projects (folders) owned by this user.
     */
    public function biolinkProjects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get all custom domains owned by this user.
     */
    public function biolinkDomains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get all tracking pixels owned by this user.
     */
    public function biolinkPixels(): HasMany
    {
        return $this->hasMany(Pixel::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Analytics Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all analytics websites owned by this user.
     */
    public function analyticsWebsites(): HasMany
    {
        return $this->hasMany(AnalyticsWebsite::class);
    }

    /**
     * Get all analytics goals owned by this user.
     */
    public function analyticsGoals(): HasMany
    {
        return $this->hasMany(AnalyticsGoal::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Push Notification Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all push websites owned by this user.
     */
    public function pushWebsites(): HasMany
    {
        return $this->hasMany(PushWebsite::class);
    }

    /**
     * Get all push campaigns owned by this user.
     */
    public function pushCampaigns(): HasMany
    {
        return $this->hasMany(PushCampaign::class);
    }

    /**
     * Get all push segments owned by this user.
     */
    public function pushSegments(): HasMany
    {
        return $this->hasMany(PushSegment::class);
    }

    /**
     * Get all push flows owned by this user.
     */
    public function pushFlows(): HasMany
    {
        return $this->hasMany(PushFlow::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Social Proof Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all social proof campaigns owned by this user.
     */
    public function socialProofCampaigns(): HasMany
    {
        return $this->hasMany(TrustCampaign::class);
    }

    /**
     * Get all social proof notifications owned by this user.
     */
    public function socialProofNotifications(): HasMany
    {
        return $this->hasMany(TrustNotification::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Entitlement Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all boosts owned by this user.
     */
    public function boosts(): HasMany
    {
        return $this->hasMany(Boost::class);
    }

    /**
     * Get all orders placed by this user.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Check if user can claim a vanity URL.
     *
     * Requires either:
     * - A paid subscription (Creator/Agency package)
     * - A one-time vanity URL boost purchase
     */
    public function canClaimVanityUrl(): bool
    {
        // Check for vanity URL boost
        $hasBoost = $this->boosts()
            ->where('feature_code', 'bio.vanity_url')
            ->where('status', Boost::STATUS_ACTIVE)
            ->exists();

        if ($hasBoost) {
            return true;
        }

        // Check for paid subscription (Creator or Agency package)
        // An order with total > 0 and status = 'paid' indicates a paid subscription
        $hasPaidSubscription = $this->orders()
            ->where('status', 'paid')
            ->where('total', '>', 0)
            ->whereHas('items', function ($query) {
                $query->whereIn('item_code', ['creator', 'agency']);
            })
            ->exists();

        return $hasPaidSubscription;
    }

    /**
     * Get the user's bio.pages entitlement (base + boosts).
     */
    public function getBioPagesLimit(): int
    {
        // Base: 1 page for all tiers
        $base = 1;

        // Add from boosts
        $boostPages = $this->boosts()
            ->where('feature_code', 'bio.pages')
            ->where('status', Boost::STATUS_ACTIVE)
            ->sum('limit_value');

        return $base + (int) $boostPages;
    }

    /**
     * Check if user can create more bio pages.
     */
    public function canCreateBioPage(): bool
    {
        return $this->biolinks()->rootPages()->count() < $this->getBioPagesLimit();
    }

    /**
     * Get remaining bio page slots.
     */
    public function remainingBioPageSlots(): int
    {
        return max(0, $this->getBioPagesLimit() - $this->biolinks()->rootPages()->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sub-Page Entitlements
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the user's sub-page limit (0 base + boosts).
     */
    public function getSubPagesLimit(): int
    {
        // Base: 0 sub-pages (free tier)
        $base = 0;

        // Add from boosts
        $boostPages = $this->boosts()
            ->where('feature_code', 'webpage.sub_pages')
            ->where('status', Boost::STATUS_ACTIVE)
            ->sum('limit_value');

        return $base + (int) $boostPages;
    }

    /**
     * Get the total sub-pages count across all root pages.
     */
    public function getSubPagesCount(): int
    {
        return $this->biolinks()->subPages()->count();
    }

    /**
     * Check if user can create more sub-pages.
     */
    public function canCreateSubPage(): bool
    {
        return $this->getSubPagesCount() < $this->getSubPagesLimit();
    }

    /**
     * Get remaining sub-page slots.
     */
    public function remainingSubPageSlots(): int
    {
        return max(0, $this->getSubPagesLimit() - $this->getSubPagesCount());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Referral Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the user who referred this user.
     */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by');
    }

    /**
     * Get all users referred by this user.
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by');
    }

    /**
     * Check if user has activated referrals.
     */
    public function hasActivatedReferrals(): bool
    {
        return $this->referral_activated_at !== null;
    }

    /**
     * Activate referrals for this user.
     */
    public function activateReferrals(): void
    {
        if (! $this->hasActivatedReferrals()) {
            $this->update(['referral_activated_at' => now()]);
        }
    }

    /**
     * Get referral ranking (1-based position among all users by referral count).
     */
    public function getReferralRank(): int
    {
        if ($this->referral_count === 0) {
            return 0; // Not ranked if no referrals
        }

        return self::where('referral_count', '>', $this->referral_count)->count() + 1;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Orderable Interface
    // ─────────────────────────────────────────────────────────────────────────

    public function getBillingName(): ?string
    {
        return $this->name;
    }

    public function getBillingEmail(): string
    {
        return $this->email;
    }

    public function getBillingAddress(): ?array
    {
        return null;
    }

    public function getTaxCountry(): ?string
    {
        return null;
    }
}
