<?php

declare(strict_types=1);

namespace Core\Mod\Api\Models;

use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * API Key - authenticates SDK and REST API requests.
 *
 * Keys are prefixed with 'hk_' for identification.
 * The actual key is hashed and never stored in plain text.
 */
class ApiKey extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Scopes available for API keys.
     */
    public const SCOPE_READ = 'read';

    public const SCOPE_WRITE = 'write';

    public const SCOPE_DELETE = 'delete';

    public const ALL_SCOPES = [
        self::SCOPE_READ,
        self::SCOPE_WRITE,
        self::SCOPE_DELETE,
    ];

    protected $fillable = [
        'workspace_id',
        'user_id',
        'name',
        'key',
        'prefix',
        'scopes',
        'server_scopes',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'server_scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'key', // Never expose the hashed key
    ];

    /**
     * Generate a new API key for a workspace.
     *
     * Returns both the ApiKey model and the plain key (only available once).
     *
     * @return array{api_key: ApiKey, plain_key: string}
     */
    public static function generate(
        int $workspaceId,
        int $userId,
        string $name,
        array $scopes = [self::SCOPE_READ, self::SCOPE_WRITE],
        ?\DateTimeInterface $expiresAt = null
    ): array {
        $plainKey = Str::random(48);
        $prefix = 'hk_'.Str::random(8);

        $apiKey = static::create([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'name' => $name,
            'key' => hash('sha256', $plainKey),
            'prefix' => $prefix,
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
        ]);

        // Return plain key only once - never stored
        return [
            'api_key' => $apiKey,
            'plain_key' => "{$prefix}_{$plainKey}",
        ];
    }

    /**
     * Find an API key by its plain text value.
     */
    public static function findByPlainKey(string $plainKey): ?static
    {
        // Expected format: hk_xxxxxxxx_xxxxx...
        if (! str_starts_with($plainKey, 'hk_')) {
            return null;
        }

        $parts = explode('_', $plainKey, 3);
        if (count($parts) !== 3) {
            return null;
        }

        $prefix = $parts[0].'_'.$parts[1]; // hk_xxxxxxxx
        $key = $parts[2];

        return static::where('prefix', $prefix)
            ->where('key', hash('sha256', $key))
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    /**
     * Record API key usage.
     */
    public function recordUsage(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Check if key has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes ?? [], true);
    }

    /**
     * Check if key has all specified scopes.
     */
    public function hasScopes(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if (! $this->hasScope($scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if key is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if key has access to a specific MCP server.
     */
    public function hasServerAccess(string $serverId): bool
    {
        // Null means all servers
        if ($this->server_scopes === null) {
            return true;
        }

        return in_array($serverId, $this->server_scopes, true);
    }

    /**
     * Get list of allowed servers (null = all).
     */
    public function getAllowedServers(): ?array
    {
        return $this->server_scopes;
    }

    /**
     * Revoke this API key.
     */
    public function revoke(): void
    {
        $this->delete();
    }

    /**
     * Get the masked key for display.
     * Shows prefix and last 4 characters.
     */
    public function getMaskedKeyAttribute(): string
    {
        return "{$this->prefix}_****";
    }

    // Relationships
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForWorkspace($query, int $workspaceId)
    {
        return $query->where('workspace_id', $workspaceId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}
