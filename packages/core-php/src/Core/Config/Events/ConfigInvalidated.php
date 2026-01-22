<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Config\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when config cache is invalidated.
 *
 * Modules can listen to refresh their own caches.
 */
class ConfigInvalidated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly ?string $keyCode = null,
        public readonly ?int $workspaceId = null,
        public readonly ?int $channelId = null,
    ) {}

    /**
     * Is this a full invalidation?
     */
    public function isFull(): bool
    {
        return $this->keyCode === null && $this->workspaceId === null;
    }

    /**
     * Does this invalidation affect a specific key?
     */
    public function affectsKey(string $key): bool
    {
        if ($this->keyCode === null) {
            return true; // Full invalidation affects all keys
        }

        // Exact match or prefix match
        return $this->keyCode === $key || str_starts_with($key, $this->keyCode.'.');
    }
}
