<?php

declare(strict_types=1);

namespace Core\Mod\Web\Models;

use Core\Mod\Tenant\Concerns\BelongsToWorkspace;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Notification handler for biolink events.
 *
 * Supports multiple notification channels:
 * - webhook: POST to external URL with HMAC signature
 * - email: Send email to configured recipients
 * - slack: Send message to Slack channel via webhook
 * - discord: Send message to Discord channel via webhook
 * - telegram: Send message via Telegram Bot API
 *
 * @property int $id
 * @property int $biolink_id
 * @property int $workspace_id
 * @property string $name
 * @property string $type
 * @property \ArrayObject $settings
 * @property array $events
 * @property bool $is_enabled
 * @property int $trigger_count
 * @property \Carbon\Carbon|null $last_triggered_at
 * @property \Carbon\Carbon|null $last_failed_at
 * @property int $consecutive_failures
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read Page $biolink
 * @property-read Workspace $workspace
 */
class NotificationHandler extends Model
{
    use BelongsToWorkspace;
    use HasFactory;
    use SoftDeletes;

    protected $table = 'biolink_notification_handlers';

    /**
     * Supported handler types.
     */
    public const TYPE_WEBHOOK = 'webhook';

    public const TYPE_EMAIL = 'email';

    public const TYPE_SLACK = 'slack';

    public const TYPE_DISCORD = 'discord';

    public const TYPE_TELEGRAM = 'telegram';

    /**
     * Supported event types.
     */
    public const EVENT_CLICK = 'click';

    public const EVENT_BLOCK_CLICK = 'block_click';

    public const EVENT_FORM_SUBMIT = 'form_submit';

    public const EVENT_PAYMENT = 'payment';

    /**
     * Maximum consecutive failures before auto-disabling.
     */
    public const MAX_CONSECUTIVE_FAILURES = 5;

    protected $fillable = [
        'biolink_id',
        'workspace_id',
        'name',
        'type',
        'settings',
        'events',
        'is_enabled',
        'trigger_count',
        'last_triggered_at',
        'last_failed_at',
        'consecutive_failures',
    ];

    protected $casts = [
        'settings' => AsArrayObject::class,
        'events' => 'array',
        'is_enabled' => 'boolean',
        'trigger_count' => 'integer',
        'consecutive_failures' => 'integer',
        'last_triggered_at' => 'datetime',
        'last_failed_at' => 'datetime',
    ];

    /**
     * Get the biolink this handler belongs to.
     */
    public function biolink(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'biolink_id');
    }

    /**
     * Check if this handler should trigger for a given event.
     */
    public function shouldTriggerFor(string $event): bool
    {
        if (! $this->is_enabled) {
            return false;
        }

        // Auto-disabled after too many failures
        if ($this->consecutive_failures >= self::MAX_CONSECUTIVE_FAILURES) {
            return false;
        }

        return in_array($event, $this->events ?? [], true);
    }

    /**
     * Get all valid handler types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_WEBHOOK => 'Webhook',
            self::TYPE_EMAIL => 'Email',
            self::TYPE_SLACK => 'Slack',
            self::TYPE_DISCORD => 'Discord',
            self::TYPE_TELEGRAM => 'Telegram',
        ];
    }

    /**
     * Get all valid event types.
     */
    public static function getEvents(): array
    {
        return [
            self::EVENT_CLICK => 'Page View / Click',
            self::EVENT_BLOCK_CLICK => 'Block Click',
            self::EVENT_FORM_SUBMIT => 'Form Submission',
            self::EVENT_PAYMENT => 'Payment Received',
        ];
    }

    /**
     * Get a setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Record a successful trigger.
     */
    public function recordSuccess(): void
    {
        $this->increment('trigger_count');
        $this->update([
            'last_triggered_at' => now(),
            'consecutive_failures' => 0,
        ]);
    }

    /**
     * Record a failed trigger.
     */
    public function recordFailure(): void
    {
        $this->increment('consecutive_failures');
        $this->update([
            'last_failed_at' => now(),
        ]);

        // Auto-disable after too many failures
        if ($this->consecutive_failures >= self::MAX_CONSECUTIVE_FAILURES) {
            $this->update(['is_enabled' => false]);
        }
    }

    /**
     * Reset failure counter and re-enable.
     */
    public function resetFailures(): void
    {
        $this->update([
            'consecutive_failures' => 0,
            'is_enabled' => true,
        ]);
    }

    /**
     * Check if handler is auto-disabled due to failures.
     */
    public function isAutoDisabled(): bool
    {
        return ! $this->is_enabled && $this->consecutive_failures >= self::MAX_CONSECUTIVE_FAILURES;
    }

    /**
     * Get icon class for this handler type.
     */
    public function getIconClass(): string
    {
        return match ($this->type) {
            self::TYPE_WEBHOOK => 'fa-solid fa-code',
            self::TYPE_EMAIL => 'fa-solid fa-envelope',
            self::TYPE_SLACK => 'fa-brands fa-slack',
            self::TYPE_DISCORD => 'fa-brands fa-discord',
            self::TYPE_TELEGRAM => 'fa-brands fa-telegram',
            default => 'fa-solid fa-bell',
        };
    }

    /**
     * Get display name for this handler type.
     */
    public function getTypeLabel(): string
    {
        return self::getTypes()[$this->type] ?? ucfirst($this->type);
    }

    /**
     * Scope to handlers for a specific event.
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('is_enabled', true)
            ->whereJsonContains('events', $event);
    }

    /**
     * Scope to active handlers (enabled and not auto-disabled).
     */
    public function scopeActive($query)
    {
        return $query->where('is_enabled', true)
            ->where('consecutive_failures', '<', self::MAX_CONSECUTIVE_FAILURES);
    }

    /**
     * Get the required settings fields for a handler type.
     */
    public static function getRequiredSettings(string $type): array
    {
        return match ($type) {
            self::TYPE_WEBHOOK => ['url'],
            self::TYPE_EMAIL => ['recipients'],
            self::TYPE_SLACK => ['webhook_url'],
            self::TYPE_DISCORD => ['webhook_url'],
            self::TYPE_TELEGRAM => ['bot_token', 'chat_id'],
            default => [],
        };
    }

    /**
     * Validate settings for this handler type.
     */
    public function validateSettings(): array
    {
        $errors = [];
        $required = self::getRequiredSettings($this->type);

        foreach ($required as $field) {
            if (empty($this->getSetting($field))) {
                $errors[$field] = "The {$field} field is required.";
            }
        }

        // Type-specific validation
        if ($this->type === self::TYPE_WEBHOOK) {
            $url = $this->getSetting('url');
            if ($url && ! filter_var($url, FILTER_VALIDATE_URL)) {
                $errors['url'] = 'Please enter a valid URL.';
            }
        }

        if ($this->type === self::TYPE_EMAIL) {
            $recipients = $this->getSetting('recipients', []);
            if (is_array($recipients)) {
                foreach ($recipients as $email) {
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors['recipients'] = 'One or more email addresses are invalid.';
                        break;
                    }
                }
            }
        }

        if (in_array($this->type, [self::TYPE_SLACK, self::TYPE_DISCORD])) {
            $url = $this->getSetting('webhook_url');
            if ($url && ! filter_var($url, FILTER_VALIDATE_URL)) {
                $errors['webhook_url'] = 'Please enter a valid webhook URL.';
            }
        }

        return $errors;
    }
}
