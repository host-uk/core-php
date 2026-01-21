<?php

declare(strict_types=1);

namespace Core\Crypt;

/**
 * LTHN Protocol QuasiHash.
 *
 * A lightweight, deterministic identifier generator for workspace/domain scoping.
 * Used to create vBucket IDs for CDN path isolation.
 *
 * Note: This is NOT cryptographically secure. It's designed for:
 * - Consistent, reproducible identifiers
 * - Human-readable outputs
 * - Fast generation
 *
 * NOT suitable for:
 * - Password hashing
 * - Security tokens
 * - Cryptographic operations
 */
class LthnHash
{
    /**
     * Default output length for short hash.
     */
    public const SHORT_LENGTH = 16;

    /**
     * Character-swapping key map for quasi-salting.
     * Swaps pairs of characters during encoding.
     */
    protected static array $keyMap = [
        'a' => '7', 'b' => 'x', 'c' => '3', 'd' => 'w',
        'e' => '3', 'f' => 'v', 'g' => '2', 'h' => 'u',
        'i' => '8', 'j' => 't', 'k' => '1', 'l' => 's',
        'm' => '6', 'n' => 'r', 'o' => '4', 'p' => 'q',
        '0' => 'z', '5' => 'y',
        // Mappings for salt generation: 'tset' → '7z37'
        's' => 'z', 't' => '7',
    ];

    /**
     * Generate a deterministic quasi-hash from input.
     *
     * Creates a salt by reversing the input and applying character
     * substitution, then hashes input + salt with SHA-256.
     *
     * @param  string  $input  The input string to hash
     * @return string 64-character SHA-256 hex string
     */
    public static function hash(string $input): string
    {
        // Create salt by reversing input and applying substitution
        $reversed = strrev($input);
        $salt = self::applyKeyMap($reversed);

        // Hash input + salt
        return hash('sha256', $input.$salt);
    }

    /**
     * Generate a short hash (prefix of full hash).
     *
     * @param  string  $input  The input string to hash
     */
    public static function shortHash(string $input): string
    {
        return substr(self::hash($input), 0, self::SHORT_LENGTH);
    }

    /**
     * Generate a vBucket ID for a domain/workspace.
     *
     * Format: 64-character SHA-256 hex string
     *
     * @param  string  $domain  The domain or workspace identifier
     */
    public static function vBucketId(string $domain): string
    {
        // Normalize domain (lowercase, trim)
        $normalized = strtolower(trim($domain));

        return self::hash($normalized);
    }

    /**
     * Verify that a hash matches an input.
     *
     * @param  string  $input  The original input
     * @param  string  $hash  The hash to verify
     */
    public static function verify(string $input, string $hash): bool
    {
        $computed = self::hash($input);

        // If hash is shorter, compare prefix
        if (strlen($hash) < 64) {
            $computed = substr($computed, 0, strlen($hash));
        }

        return hash_equals($computed, $hash);
    }

    /**
     * Apply the key map character swapping.
     */
    protected static function applyKeyMap(string $input): string
    {
        $output = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            $output .= self::$keyMap[$char] ?? $char;
        }

        return $output;
    }

    /**
     * Get the current key map.
     */
    public static function getKeyMap(): array
    {
        return self::$keyMap;
    }

    /**
     * Set a custom key map.
     */
    public static function setKeyMap(array $keyMap): void
    {
        self::$keyMap = $keyMap;
    }

    /**
     * Generate a deterministic integer from input.
     * Useful for consistent sharding/partitioning.
     *
     * @param  string  $input  The input string
     * @param  int  $max  Maximum value (exclusive)
     */
    public static function toInt(string $input, int $max = PHP_INT_MAX): int
    {
        $hash = self::hash($input);
        // Use first 15 hex chars (60 bits) for safe int conversion
        $hex = substr($hash, 0, 15);

        return gmp_intval(gmp_mod(gmp_init($hex, 16), $max));
    }
}
