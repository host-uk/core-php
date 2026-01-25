<?php

namespace Core\Mod\Tenant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Core\Mod\Tenant\Services\EntitlementResult;
use Core\Mod\Tenant\Services\EntitlementService;

class Workspace extends Model
{
    use HasFactory;

    protected static function newFactory(): \Core\Mod\Tenant\Database\Factories\WorkspaceFactory
    {
        return \Core\Mod\Tenant\Database\Factories\WorkspaceFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'icon',
        'color',
        'description',
        'type',
        'settings',
        'is_active',
        'sort_order',
        // WP Connector fields (secret excluded for security)
        'wp_connector_enabled',
        'wp_connector_url',
        'wp_connector_verified_at',
        'wp_connector_last_sync',
        'wp_connector_config',
        // Billing fields
        'stripe_customer_id',
        'btcpay_customer_id',
        'billing_name',
        'billing_email',
        'billing_address_line1',
        'billing_address_line2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'vat_number',
        'tax_id',
        'tax_exempt',
    ];

    /**
     * Guarded attributes (sensitive data that should not be mass-assigned).
     */
    protected $guarded = [
        'wp_connector_secret',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'wp_connector_enabled' => 'boolean',
        'wp_connector_verified_at' => 'datetime',
        'wp_connector_last_sync' => 'datetime',
        'wp_connector_config' => 'array',
        'tax_exempt' => 'boolean',
    ];

    /**
     * Hidden attributes (sensitive data).
     */
    protected $hidden = [
        'wp_connector_secret',
    ];

    /**
     * Get the users that have access to this workspace.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_workspace')
            ->withPivot(['role', 'is_default'])
            ->withTimestamps();
    }

    /**
     * Get the workspace owner (user with 'owner' role).
     */
    public function owner(): ?User
    {
        return $this->users()
            ->wherePivot('role', 'owner')
            ->first();
    }

    /**
     * Active package assignments for this workspace.
     */
    public function workspacePackages(): HasMany
    {
        return $this->hasMany(WorkspacePackage::class);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Namespace Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get all namespaces owned by this workspace.
     */
    public function namespaces(): MorphMany
    {
        return $this->morphMany(Namespace_::class, 'owner');
    }

    /**
     * Get the workspace's default namespace.
     */
    public function defaultNamespace(): ?Namespace_
    {
        return $this->namespaces()
            ->where('is_default', true)
            ->active()
            ->first()
            ?? $this->namespaces()->active()->ordered()->first();
    }

    /**
     * The package definitions assigned to this workspace.
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Entitlement\Package::class, 'entitlement_workspace_packages', 'workspace_id', 'package_id')
            ->withPivot(['status', 'starts_at', 'expires_at', 'metadata'])
            ->withTimestamps();
    }

    /**
     * Get a setting from the settings JSON column.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Active boosts for this workspace.
     */
    public function boosts(): HasMany
    {
        return $this->hasMany(Boost::class);
    }

    /**
     * Usage records for this workspace.
     */
    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    /**
     * Entitlement logs for this workspace.
     */
    public function entitlementLogs(): HasMany
    {
        return $this->hasMany(EntitlementLog::class);
    }

    // SocialHost Relationships (Native)

    /**
     * Get social accounts for this workspace.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\Account::class);
    }

    /**
     * Get social posts for this workspace.
     */
    public function socialPosts(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\Post::class);
    }

    /**
     * Get social media templates for this workspace.
     */
    public function socialTemplates(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\Template::class);
    }

    /**
     * Get social media files for this workspace.
     */
    public function socialMedia(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\Media::class);
    }

    /**
     * Get social hashtag groups for this workspace.
     */
    public function socialHashtagGroups(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\HashtagGroup::class);
    }

    /**
     * Get social webhooks for this workspace.
     */
    public function socialWebhooks(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\Webhook::class);
    }

    /**
     * Get social analytics for this workspace.
     */
    public function socialAnalytics(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\Analytics::class);
    }

    /**
     * Get social variables for this workspace.
     */
    public function socialVariables(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\Variable::class);
    }

    /**
     * Get posting schedule for this workspace.
     */
    public function socialPostingSchedule(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\PostingSchedule::class);
    }

    /**
     * Get imported posts for this workspace.
     */
    public function socialImportedPosts(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\ImportedPost::class);
    }

    /**
     * Get social metrics for this workspace.
     */
    public function socialMetrics(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\Metric::class);
    }

    /**
     * Get audience data for this workspace.
     */
    public function socialAudience(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\Audience::class);
    }

    /**
     * Get Facebook insights for this workspace.
     */
    public function socialFacebookInsights(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\FacebookInsight::class);
    }

    /**
     * Get Instagram insights for this workspace.
     */
    public function socialInstagramInsights(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\InstagramInsight::class);
    }

    /**
     * Get Pinterest analytics for this workspace.
     */
    public function socialPinterestAnalytics(): HasMany
    {
        return $this->hasMany(\Core\Mod\Social\Models\PinterestAnalytic::class);
    }

    /**
     * Check if this workspace has SocialHost enabled (has connected social accounts).
     */
    public function hasSocialHost(): bool
    {
        return $this->socialAccounts()->exists();
    }

    /**
     * Get count of connected social accounts.
     */
    public function socialAccountsCount(): int
    {
        return $this->socialAccounts()->count();
    }

    // BioHost Relationships

    /**
     * Get bio pages for this workspace.
     */
    public function bioPages(): HasMany
    {
        return $this->hasMany(\App\Models\BioLink\Page::class);
    }

    /**
     * Get bio projects for this workspace.
     */
    public function bioProjects(): HasMany
    {
        return $this->hasMany(\App\Models\BioLink\Project::class);
    }

    /**
     * Get bio domains for this workspace.
     */
    public function bioDomains(): HasMany
    {
        return $this->hasMany(\App\Models\BioLink\Domain::class);
    }

    /**
     * Get bio pixels for this workspace.
     */
    public function bioPixels(): HasMany
    {
        return $this->hasMany(\App\Models\BioLink\Pixel::class);
    }

    // AnalyticsHost Relationships

    /**
     * Get analytics websites for this workspace (AnalyticsHost).
     */
    public function analyticsSites(): HasMany
    {
        return $this->hasMany(\Core\Mod\Analytics\Models\Website::class);
    }

    /**
     * Get social analytics websites for this workspace (legacy, for SocialHost analytics).
     */
    public function socialAnalyticsWebsites(): HasMany
    {
        return $this->hasMany(\Core\Mod\Analytics\Models\AnalyticsWebsite::class);
    }

    /**
     * Get analytics goals for this workspace (AnalyticsHost).
     */
    public function analyticsGoals(): HasMany
    {
        return $this->hasMany(\Core\Mod\Analytics\Models\Goal::class);
    }

    /**
     * Get social analytics goals for this workspace (legacy, for SocialHost analytics).
     */
    public function socialAnalyticsGoals(): HasMany
    {
        return $this->hasMany(\Core\Mod\Analytics\Models\AnalyticsGoal::class);
    }

    // TrustHost Relationships

    /**
     * Get social proof campaigns (TrustHost widgets) for this workspace.
     */
    public function trustWidgets(): HasMany
    {
        return $this->hasMany(\Core\Mod\Trust\Models\Campaign::class);
    }

    /**
     * Get social proof notifications for this workspace.
     */
    public function trustNotifications(): HasMany
    {
        return $this->hasMany(\Core\Mod\Trust\Models\Notification::class);
    }

    // NotifyHost Relationships

    /**
     * Get push notification websites for this workspace.
     */
    public function notificationSites(): HasMany
    {
        return $this->hasMany(\Core\Mod\Notify\Models\PushWebsite::class);
    }

    /**
     * Get push campaigns for this workspace.
     */
    public function pushCampaigns(): HasMany
    {
        return $this->hasMany(\Core\Mod\Notify\Models\PushCampaign::class);
    }

    /**
     * Get push flows for this workspace.
     */
    public function pushFlows(): HasMany
    {
        return $this->hasMany(\Core\Mod\Notify\Models\PushFlow::class);
    }

    /**
     * Get push segments for this workspace.
     */
    public function pushSegments(): HasMany
    {
        return $this->hasMany(\Core\Mod\Notify\Models\PushSegment::class);
    }

    // API & Webhooks Relationships

    /**
     * Get API keys for this workspace.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(\Core\Mod\Api\Models\ApiKey::class);
    }

    /**
     * Get webhook endpoints for this workspace.
     */
    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(\Core\Mod\Api\Models\WebhookEndpoint::class);
    }

    // Trees for Agents Relationships

    /**
     * Get tree plantings for this workspace.
     */
    public function treePlantings(): HasMany
    {
        return $this->hasMany(\Core\Mod\Trees\Models\TreePlanting::class);
    }

    /**
     * Get total trees planted for this workspace.
     */
    public function treesPlanted(): int
    {
        return $this->treePlantings()
            ->whereIn('status', ['confirmed', 'planted'])
            ->sum('trees');
    }

    /**
     * Get trees planted this year for this workspace.
     */
    public function treesThisYear(): int
    {
        return $this->treePlantings()
            ->whereIn('status', ['confirmed', 'planted'])
            ->whereYear('created_at', now()->year)
            ->sum('trees');
    }

    // Content & Media Relationships

    /**
     * Get content items for this workspace.
     */
    public function contentItems(): HasMany
    {
        return $this->hasMany(\Core\Mod\Content\Models\ContentItem::class);
    }

    /**
     * Get content authors for this workspace.
     */
    public function contentAuthors(): HasMany
    {
        return $this->hasMany(\Core\Mod\Content\Models\ContentAuthor::class);
    }

    // Helper Methods

    /**
     * Get the currently active workspace from request context.
     *
     * Returns the Workspace model instance (not array).
     */
    public static function current(): ?self
    {
        // Try to get from request attributes (set by middleware)
        if (request()->attributes->has('workspace_model')) {
            return request()->attributes->get('workspace_model');
        }

        // Try to get from authenticated user's default workspace
        if (auth()->check() && auth()->user() instanceof \Core\Mod\Tenant\Models\User) {
            return auth()->user()->defaultHostWorkspace();
        }

        // Try to resolve from subdomain via WorkspaceService
        $workspaceService = app(\App\Services\WorkspaceService::class);
        $slug = $workspaceService->currentSlug();

        return static::where('slug', $slug)->first();
    }

    /**
     * Check if workspace can use a feature.
     */
    public function can(string $featureCode, int $quantity = 1): EntitlementResult
    {
        return app(EntitlementService::class)->can($this, $featureCode, $quantity);
    }

    /**
     * Record usage of a feature.
     */
    public function recordUsage(string $featureCode, int $quantity = 1, ?User $user = null, ?array $metadata = null): UsageRecord
    {
        return app(EntitlementService::class)->recordUsage($this, $featureCode, $quantity, $user, $metadata);
    }

    /**
     * Get usage summary for all features.
     */
    public function getUsageSummary(): \Illuminate\Support\Collection
    {
        return app(EntitlementService::class)->getUsageSummary($this);
    }

    /**
     * Check if workspace has a specific package.
     */
    public function hasPackage(string $packageCode): bool
    {
        return $this->workspacePackages()
            ->whereHas('package', fn ($q) => $q->where('code', $packageCode))
            ->active()
            ->exists();
    }

    /**
     * Check if workspace has Apollo tier.
     */
    public function isApollo(): bool
    {
        return $this->can('tier.apollo')->isAllowed();
    }

    /**
     * Check if workspace has Hades tier.
     */
    public function isHades(): bool
    {
        return $this->can('tier.hades')->isAllowed();
    }

    /**
     * Get the WordPress URL for this workspace.
     */
    public function getWordPressUrlAttribute(): string
    {
        return 'https://'.$this->domain;
    }

    /**
     * Scope to only active workspaces.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Convert to array format used by WorkspaceService.
     */
    public function toServiceArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'domain' => $this->domain,
            'icon' => $this->icon,
            'color' => $this->color,
            'description' => $this->description,
        ];
    }

    /**
     * Generate a new webhook secret for the WP connector.
     */
    public function generateWpConnectorSecret(): string
    {
        $secret = bin2hex(random_bytes(32));
        $this->update(['wp_connector_secret' => $secret]);

        return $secret;
    }

    /**
     * Enable the WP connector with a URL.
     */
    public function enableWpConnector(string $url): self
    {
        $this->update([
            'wp_connector_enabled' => true,
            'wp_connector_url' => rtrim($url, '/'),
            'wp_connector_secret' => $this->wp_connector_secret ?? bin2hex(random_bytes(32)),
        ]);

        return $this;
    }

    /**
     * Disable the WP connector.
     */
    public function disableWpConnector(): self
    {
        $this->update([
            'wp_connector_enabled' => false,
            'wp_connector_verified_at' => null,
        ]);

        return $this;
    }

    /**
     * Mark the WP connector as verified.
     */
    public function markWpConnectorVerified(): self
    {
        $this->update(['wp_connector_verified_at' => now()]);

        return $this;
    }

    /**
     * Update the last sync timestamp.
     */
    public function touchWpConnectorSync(): self
    {
        $this->update(['wp_connector_last_sync' => now()]);

        return $this;
    }

    /**
     * Check if the WP connector is active and verified.
     */
    public function hasActiveWpConnector(): bool
    {
        return $this->wp_connector_enabled
            && ! empty($this->wp_connector_url)
            && ! empty($this->wp_connector_secret);
    }

    /**
     * Get the webhook URL that WordPress should POST to.
     */
    public function getWpConnectorWebhookUrlAttribute(): string
    {
        return route('api.webhook.content').'?workspace='.$this->slug;
    }

    /**
     * Validate an incoming webhook signature.
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($this->wp_connector_secret)) {
            return false;
        }

        $expected = hash_hmac('sha256', $payload, $this->wp_connector_secret);

        return hash_equals($expected, $signature);
    }
}
