<?php

declare(strict_types=1);

namespace Core\Crypt\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Exception thrown when encrypted data cannot be decrypted.
 *
 * This may indicate data corruption, key rotation issues, or tampering.
 * Only thrown when strict mode is enabled in configuration.
 */
class EncryptedDataCorruptException extends RuntimeException
{
    public function __construct(
        public readonly string $attributeKey,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            "Failed to decrypt attribute [{$attributeKey}]. This may indicate data corruption or an APP_KEY mismatch.",
            0,
            $previous
        );
    }
}
