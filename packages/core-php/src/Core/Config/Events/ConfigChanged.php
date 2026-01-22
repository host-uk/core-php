<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Config\Events;

use Core\Config\Models\ConfigProfile;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a config value is set or updated.
 *
 * Modules can listen to invalidate caches or trigger side effects.
 */
class ConfigChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $keyCode,
        public readonly mixed $value,
        public readonly mixed $previousValue,
        public readonly ConfigProfile $profile,
        public readonly ?int $channelId = null,
    ) {}
}
