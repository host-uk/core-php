<?php

declare(strict_types=1);

namespace Core\Mod\Api\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Webhook Delivery - individual delivery attempt.
 *
 * Tracks status, retries, and response details.
 */
class WebhookDelivery extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETRYING = 'retrying';

    public const MAX_RETRIES = 5;

    /**
     * Retry delays in minutes for each attempt.
     */
    public const RETRY_DELAYS = [
        1 => 1,      // 1 minute
        2 => 5,      // 5 minutes
        3 => 30,     // 30 minutes
        4 => 120,    // 2 hours
        5 => 1440,   // 24 hours
    ];

    protected $fillable = [
        'webhook_endpoint_id',
        'event_id',
        'event_type',
        'payload',
        'response_code',
        'response_body',
        'attempt',
        'status',
        'delivered_at',
        'next_retry_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivered_at' => 'datetime',
        'next_retry_at' => 'datetime',
    ];

    /**
     * Create a new delivery for an event.
     */
    public static function createForEvent(
        WebhookEndpoint $endpoint,
        string $eventType,
        array $data,
        ?int $workspaceId = null
    ): static {
        return static::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event_id' => 'evt_'.Str::random(24),
            'event_type' => $eventType,
            'payload' => [
                'id' => 'evt_'.Str::random(24),
                'type' => $eventType,
                'created_at' => now()->toIso8601String(),
                'data' => $data,
                'workspace_id' => $workspaceId,
            ],
            'status' => self::STATUS_PENDING,
            'attempt' => 1,
        ]);
    }

    /**
     * Mark as successfully delivered.
     */
    public function markSuccess(int $responseCode, ?string $responseBody = null): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'response_code' => $responseCode,
            'response_body' => $responseBody ? Str::limit($responseBody, 10000) : null,
            'delivered_at' => now(),
            'next_retry_at' => null,
        ]);

        $this->endpoint->recordSuccess();
    }

    /**
     * Mark as failed and schedule retry if attempts remain.
     */
    public function markFailed(int $responseCode, ?string $responseBody = null): void
    {
        $this->endpoint->recordFailure();

        if ($this->attempt >= self::MAX_RETRIES) {
            $this->update([
                'status' => self::STATUS_FAILED,
                'response_code' => $responseCode,
                'response_body' => $responseBody ? Str::limit($responseBody, 10000) : null,
            ]);

            return;
        }

        // Schedule retry
        $nextAttempt = $this->attempt + 1;
        $delayMinutes = self::RETRY_DELAYS[$nextAttempt] ?? 1440;

        $this->update([
            'status' => self::STATUS_RETRYING,
            'response_code' => $responseCode,
            'response_body' => $responseBody ? Str::limit($responseBody, 10000) : null,
            'attempt' => $nextAttempt,
            'next_retry_at' => now()->addMinutes($delayMinutes),
        ]);
    }

    /**
     * Check if delivery can be retried.
     */
    public function canRetry(): bool
    {
        return $this->attempt < self::MAX_RETRIES
            && $this->status !== self::STATUS_SUCCESS;
    }

    /**
     * Get formatted payload with signature headers.
     */
    public function getDeliveryPayload(): array
    {
        $jsonPayload = json_encode($this->payload);

        return [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-HostHub-Event' => $this->event_type,
                'X-HostHub-Delivery' => $this->event_id,
                'X-HostHub-Signature' => $this->endpoint->generateSignature($jsonPayload),
            ],
            'body' => $jsonPayload,
        ];
    }

    // Relationships
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeRetrying($query)
    {
        return $query->where('status', self::STATUS_RETRYING)
            ->where('next_retry_at', '<=', now());
    }

    public function scopeNeedsDelivery($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_PENDING)
                ->orWhere(function ($q2) {
                    $q2->where('status', self::STATUS_RETRYING)
                        ->where('next_retry_at', '<=', now());
                });
        });
    }
}
