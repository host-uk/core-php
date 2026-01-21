<?php

declare(strict_types=1);

namespace Core\Helpers;

use Illuminate\Support\Str;

/**
 * Two-factor authentication recovery code generator.
 *
 * Generates cryptographically secure recovery codes for 2FA backup access.
 */
class RecoveryCode
{
    /**
     * Generate a new recovery code in format: XXXXX-XXXXX.
     */
    public static function generate(): string
    {
        return Str::random(10).'-'.Str::random(10);
    }
}
