<?php

namespace Core\Mod\Web\Models;

use Core\Helpers\PrivacyHelper;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BioLink form submission storage.
 *
 * Stores submissions from email_collector, phone_collector, and contact_collector blocks.
 */
class Submission extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'biolink_submissions';

    protected $fillable = [
        'biolink_id',
        'block_id',
        'type',
        'data',
        'ip_hash',
        'country_code',
        'notification_sent',
        'notified_at',
    ];

    protected $casts = [
        'data' => AsArrayObject::class,
        'notification_sent' => 'boolean',
        'notified_at' => 'datetime',
    ];

    /**
     * Valid submission types.
     */
    public const TYPES = ['email', 'phone', 'contact'];

    // ─────────────────────────────────────────────────────────────────────────
    // Relationships
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get the biolink this submission belongs to.
     */
    public function biolink(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'biolink_id');
    }

    /**
     * Get the block this submission was made through.
     */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class, 'block_id');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Scopes
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Scope to email submissions.
     */
    public function scopeEmails($query)
    {
        return $query->where('type', 'email');
    }

    /**
     * Scope to phone submissions.
     */
    public function scopePhones($query)
    {
        return $query->where('type', 'phone');
    }

    /**
     * Scope to contact submissions.
     */
    public function scopeContacts($query)
    {
        return $query->where('type', 'contact');
    }

    /**
     * Scope to submissions needing notification.
     */
    public function scopeNeedsNotification($query)
    {
        return $query->where('notification_sent', false);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Accessors
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Get email from data (if present).
     */
    public function getEmailAttribute(): ?string
    {
        return $this->data['email'] ?? null;
    }

    /**
     * Get phone from data (if present).
     */
    public function getPhoneAttribute(): ?string
    {
        return $this->data['phone'] ?? null;
    }

    /**
     * Get name from data (if present).
     */
    public function getNameAttribute(): ?string
    {
        return $this->data['name'] ?? null;
    }

    /**
     * Get message from data (if present - contact type only).
     */
    public function getMessageAttribute(): ?string
    {
        return $this->data['message'] ?? null;
    }

    /**
     * Get a summary of the submission for display.
     */
    public function getSummaryAttribute(): string
    {
        return match ($this->type) {
            'email' => $this->email ?? 'Unknown email',
            'phone' => $this->phone ?? 'Unknown phone',
            'contact' => $this->email ?? $this->name ?? 'Unknown contact',
            default => 'Unknown submission',
        };
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Create a submission from form data.
     */
    public static function createFromForm(
        Block $block,
        string $type,
        array $data,
        ?string $ip = null,
        ?string $countryCode = null
    ): self {
        return self::create([
            'biolink_id' => $block->biolink_id,
            'block_id' => $block->id,
            'type' => $type,
            'data' => $data,
            'ip_hash' => $ip ? PrivacyHelper::hashIpDaily($ip) : null,
            'country_code' => $countryCode,
        ]);
    }

    /**
     * Mark as notified.
     */
    public function markNotified(): void
    {
        $this->update([
            'notification_sent' => true,
            'notified_at' => now(),
        ]);
    }

    /**
     * Export submission data as array.
     */
    public function toExportArray(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'message' => $this->message,
            'country' => $this->country_code,
            'submitted_at' => $this->created_at->toIso8601String(),
        ];
    }
}
