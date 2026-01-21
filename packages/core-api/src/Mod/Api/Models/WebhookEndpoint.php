<?php

declare(strict_types=1);

namespace Core\Mod\Api\Models;

use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Webhook Endpoint - receives event notifications.
 *
 * Uses HMAC signatures for security and auto-disables after failures.
 */
class WebhookEndpoint extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Available webhook events.
     */
    public const EVENTS = [
        // Workspace events
        'workspace.created',
        'workspace.updated',
        'workspace.deleted',

        // Subscription events
        'subscription.created',
        'subscription.updated',
        'subscription.cancelled',
        'subscription.renewed',

        // Invoice events
        'invoice.created',
        'invoice.paid',
        'invoice.failed',

        // BioLink events
        'bio.created',
        'bio.updated',
        'bio.deleted',

        // Link events
        'link.created',
        'link.updated',
        'link.deleted',
        'link.clicked', // High volume - opt-in only

        // QR Code events
        'qrcode.created',
        'qrcode.scanned', // High volume - opt-in only

        // MCP events
        'mcp.tool.executed', // Tool execution completed
    ];

    protected $fillable = [
        'workspace_id',
        'url',
        'secret',
        'events',
        'active',
        'description',
        'last_triggered_at',
        'failure_count',
        'disabled_at',
    ];

    protected $casts = [
        'events' => 'array',
        'active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
    ];

    /**
     * Create a new webhook endpoint with auto-generated secret.
     */
    public static function createForWorkspace(
        int $workspaceId,
        string $url,
        array $events,
        ?string $description = null
    ): static {
        return static::create([
            'workspace_id' => $workspaceId,
            'url' => $url,
            'secret' => Str::random(64),
            'events' => $events,
            'description' => $description,
            'active' => true,
        ]);
    }

    /**
     * Generate signature for payload.
     */
    public function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }

    /**
     * Check if endpoint should receive an event.
     */
    public function shouldReceive(string $eventType): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->disabled_at !== null) {
            return false;
        }

        return in_array($eventType, $this->events, true)
            || in_array('*', $this->events, true);
    }

    /**
     * Record successful delivery.
     */
    public function recordSuccess(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'failure_count' => 0,
        ]);
    }

    /**
     * Record failed delivery.
     * Auto-disables after 10 consecutive failures.
     */
    public function recordFailure(): void
    {
        $failureCount = $this->failure_count + 1;

        $updates = [
            'failure_count' => $failureCount,
            'last_triggered_at' => now(),
        ];

        // Auto-disable after 10 consecutive failures
        if ($failureCount >= 10) {
            $updates['disabled_at'] = now();
            $updates['active'] = false;
        }

        $this->update($updates);
    }

    /**
     * Re-enable a disabled endpoint.
     */
    public function enable(): void
    {
        $this->update([
            'active' => true,
            'disabled_at' => null,
            'failure_count' => 0,
        ]);
    }

    /**
     * Rotate the webhook secret.
     */
    public function rotateSecret(): string
    {
        $newSecret = Str::random(64);
        $this->update(['secret' => $newSecret]);

        return $newSecret;
    }

    // Relationships
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true)
            ->whereNull('disabled_at');
    }

    public function scopeForWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeForEvent($query, string $eventType)
    {
        return $query->where(function ($q) use ($eventType) {
            $q->whereJsonContains('events', $eventType)
                ->orWhereJsonContains('events', '*');
        });
    }
}
