<?php
/*
 * Core PHP Framework
 *
 * Licensed under the European Union Public Licence (EUPL) v1.2.
 * See LICENSE file for details.
 */

declare(strict_types=1);

namespace Core\Crypt;

/**
 * LTHN Protocol QuasiHash.
 *
 * A lightweight, deterministic identifier generator for workspace/domain scoping.
 * Used to create vBucket IDs for CDN path isolation.
 *
 * ## Security Properties
 *
 * This is a "QuasiHash" - a deterministic identifier generator, NOT a cryptographic hash.
 *
 * **What it provides:**
 * - Deterministic output: same input always produces same output
 * - Uniform distribution: outputs are evenly distributed across the hash space
 * - Avalanche effect: small input changes produce significantly different outputs
 * - Collision resistance proportional to output length (see table below)
 *
 * **What it does NOT provide:**
 * - Pre-image resistance: attackers can potentially reverse the hash
 * - Cryptographic security: the key map is not a secret
 * - Protection against brute force: short hashes can be enumerated
 *
 * ## Collision Resistance by Length
 *
 * | Length | Bits | Collision Probability (10k items) | Use Case |
 * |--------|------|-----------------------------------|----------|
 * | 16     | 64   | ~1 in 3.4 billion                 | Internal IDs, low-volume |
 * | 24     | 96   | ~1 in 79 quintillion              | Cross-system IDs |
 * | 32     | 128  | ~1 in 3.4e38                      | Long-term storage |
 * | 64     | 256  | Negligible                        | Maximum security |
 *
 * ## Key Rotation
 *
 * The class supports multiple key maps for rotation. When verifying, all registered
 * key maps are tried in order (newest first). This allows gradual migration:
 *
 * 1. Add new key map with `addKeyMap()`
 * 2. New hashes use the new key map
 * 3. Verification tries new key first, falls back to old
 * 4. After migration period, remove old key map with `removeKeyMap()`
 *
 * ## NOT Suitable For
 *
 * - Password hashing (use `password_hash()` instead)
 * - Security tokens (use `random_bytes()` instead)
 * - Cryptographic signatures
 * - Any security-sensitive operations
 */
class LthnHash
{
    /**
     * Default output length for short hash (16 hex chars = 64 bits).
     */
    public const SHORT_LENGTH = 16;

    /**
     * Medium output length for improved collision resistance (24 hex chars = 96 bits).
     */
    public const MEDIUM_LENGTH = 24;

    /**
     * Long output length for high collision resistance (32 hex chars = 128 bits).
     */
    public const LONG_LENGTH = 32;

    /**
     * Default key map identifier.
     */
    public const DEFAULT_KEY = 'default';

    /**
     * Character-swapping key maps for quasi-salting.
     * Swaps pairs of characters during encoding.
     *
     * Multiple key maps can be registered for key rotation.
     * The first key map is used for new hashes; all are tried during verification.
     *
     * @var array<string, array<string, string>>
     */
    protected static array $keyMaps = [
        'default' => [
            'a' => '7', 'b' => 'x', 'c' => '3', 'd' => 'w',
            'e' => '3', 'f' => 'v', 'g' => '2', 'h' => 'u',
            'i' => '8', 'j' => 't', 'k' => '1', 'l' => 's',
            'm' => '6', 'n' => 'r', 'o' => '4', 'p' => 'q',
            '0' => 'z', '5' => 'y',
            's' => 'z', 't' => '7',
        ],
    ];

    /**
     * The currently active key map identifier for generating new hashes.
     */
    protected static string $activeKey = self::DEFAULT_KEY;

    /**
     * Generate a deterministic quasi-hash from input.
     *
     * Creates a salt by reversing the input and applying character
     * substitution, then hashes input + salt with SHA-256.
     *
     * @param  string  $input  The input string to hash
     * @param  string|null  $keyId  Key map identifier (null uses active key)
     * @return string 64-character SHA-256 hex string
     */
    public static function hash(string $input, ?string $keyId = null): string
    {
        $keyId ??= self::$activeKey;

        // Create salt by reversing input and applying substitution
        $reversed = strrev($input);
        $salt = self::applyKeyMap($reversed, $keyId);

        // Hash input + salt
        return hash('sha256', $input.$salt);
    }

    /**
     * Generate a short hash (prefix of full hash).
     *
     * @param  string  $input  The input string to hash
     * @param  int  $length  Output length in hex characters (default: SHORT_LENGTH)
     */
    public static function shortHash(string $input, int $length = self::SHORT_LENGTH): string
    {
        if ($length < 1 || $length > 64) {
            throw new \InvalidArgumentException('Hash length must be between 1 and 64');
        }

        return substr(self::hash($input), 0, $length);
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
     * Tries all registered key maps in order (active key first, then others).
     * This supports key rotation: old hashes remain verifiable while new hashes
     * use the current active key.
     *
     * @param  string  $input  The original input
     * @param  string  $hash  The hash to verify
     * @return bool True if the hash matches with any registered key map
     */
    public static function verify(string $input, string $hash): bool
    {
        $hashLength = strlen($hash);

        // Try active key first
        $computed = self::hash($input, self::$activeKey);
        if ($hashLength < 64) {
            $computed = substr($computed, 0, $hashLength);
        }
        if (hash_equals($computed, $hash)) {
            return true;
        }

        // Try other key maps for rotation support
        foreach (array_keys(self::$keyMaps) as $keyId) {
            if ($keyId === self::$activeKey) {
                continue;
            }

            $computed = self::hash($input, $keyId);
            if ($hashLength < 64) {
                $computed = substr($computed, 0, $hashLength);
            }
            if (hash_equals($computed, $hash)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply the key map character swapping.
     *
     * @param  string  $input  The input string to transform
     * @param  string  $keyId  Key map identifier
     */
    protected static function applyKeyMap(string $input, string $keyId): string
    {
        $keyMap = self::$keyMaps[$keyId] ?? self::$keyMaps[self::DEFAULT_KEY];
        $output = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];
            $output .= $keyMap[$char] ?? $char;
        }

        return $output;
    }

    /**
     * Get the current active key map.
     *
     * @return array<string, string>
     */
    public static function getKeyMap(): array
    {
        return self::$keyMaps[self::$activeKey] ?? self::$keyMaps[self::DEFAULT_KEY];
    }

    /**
     * Get all registered key maps.
     *
     * @return array<string, array<string, string>>
     */
    public static function getKeyMaps(): array
    {
        return self::$keyMaps;
    }

    /**
     * Set a custom key map (replaces the active key map).
     *
     * @param  array<string, string>  $keyMap  Character substitution map
     */
    public static function setKeyMap(array $keyMap): void
    {
        self::$keyMaps[self::$activeKey] = $keyMap;
    }

    /**
     * Add a new key map for rotation.
     *
     * @param  string  $keyId  Unique identifier for this key map
     * @param  array<string, string>  $keyMap  Character substitution map
     * @param  bool  $setActive  Whether to make this the active key for new hashes
     */
    public static function addKeyMap(string $keyId, array $keyMap, bool $setActive = true): void
    {
        self::$keyMaps[$keyId] = $keyMap;

        if ($setActive) {
            self::$activeKey = $keyId;
        }
    }

    /**
     * Remove a key map.
     *
     * Cannot remove the default key map or the currently active key map.
     *
     * @param  string  $keyId  Key map identifier to remove
     *
     * @throws \InvalidArgumentException If attempting to remove default or active key
     */
    public static function removeKeyMap(string $keyId): void
    {
        if ($keyId === self::DEFAULT_KEY) {
            throw new \InvalidArgumentException('Cannot remove the default key map');
        }

        if ($keyId === self::$activeKey) {
            throw new \InvalidArgumentException('Cannot remove the active key map. Set a different active key first.');
        }

        unset(self::$keyMaps[$keyId]);
    }

    /**
     * Get the active key map identifier.
     */
    public static function getActiveKey(): string
    {
        return self::$activeKey;
    }

    /**
     * Set the active key map for generating new hashes.
     *
     * @param  string  $keyId  Key map identifier (must already be registered)
     *
     * @throws \InvalidArgumentException If key map does not exist
     */
    public static function setActiveKey(string $keyId): void
    {
        if (! isset(self::$keyMaps[$keyId])) {
            throw new \InvalidArgumentException("Key map '{$keyId}' does not exist");
        }

        self::$activeKey = $keyId;
    }

    /**
     * Reset to default state.
     *
     * Removes all custom key maps and resets to the default key map.
     */
    public static function reset(): void
    {
        self::$keyMaps = [
            self::DEFAULT_KEY => [
                'a' => '7', 'b' => 'x', 'c' => '3', 'd' => 'w',
                'e' => '3', 'f' => 'v', 'g' => '2', 'h' => 'u',
                'i' => '8', 'j' => 't', 'k' => '1', 'l' => 's',
                'm' => '6', 'n' => 'r', 'o' => '4', 'p' => 'q',
                '0' => 'z', '5' => 'y',
                's' => 'z', 't' => '7',
            ],
        ];
        self::$activeKey = self::DEFAULT_KEY;
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
