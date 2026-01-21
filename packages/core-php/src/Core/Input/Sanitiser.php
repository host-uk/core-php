<?php

declare(strict_types=1);

namespace Core\Input;

use Normalizer;
use Psr\Log\LoggerInterface;

/**
 * Input sanitiser - makes data safe, not valid.
 *
 * One job: strip dangerous control characters.
 * One C call: filter_var_array.
 *
 * Laravel validates. We sanitise.
 *
 * Features:
 * - Configurable filter rules per field via schema
 * - Unicode NFC normalization for consistent string handling
 * - Optional audit logging when content is modified
 */
class Sanitiser
{
    /**
     * Schema for per-field filter rules.
     *
     * Format: ['field_name' => ['filters' => [...], 'options' => [...]]]
     *
     * @var array<string, array{filters?: int[], options?: int[], skip_control_strip?: bool, skip_normalize?: bool}>
     */
    protected array $schema = [];

    /**
     * Optional logger for audit logging.
     */
    protected ?LoggerInterface $logger = null;

    /**
     * Whether to enable audit logging.
     */
    protected bool $auditEnabled = false;

    /**
     * Whether to normalize Unicode to NFC form.
     */
    protected bool $normalizeUnicode = true;

    /**
     * Create a new Sanitiser instance.
     *
     * @param array<string, array{filters?: int[], options?: int[], skip_control_strip?: bool, skip_normalize?: bool}> $schema Per-field filter rules
     * @param LoggerInterface|null $logger Optional PSR-3 logger for audit logging
     * @param bool $auditEnabled Whether to enable audit logging (requires logger)
     * @param bool $normalizeUnicode Whether to normalize Unicode to NFC form
     */
    public function __construct(
        array $schema = [],
        ?LoggerInterface $logger = null,
        bool $auditEnabled = false,
        bool $normalizeUnicode = true
    ) {
        $this->schema = $schema;
        $this->logger = $logger;
        $this->auditEnabled = $auditEnabled && $logger !== null;
        $this->normalizeUnicode = $normalizeUnicode;
    }

    /**
     * Set the per-field filter schema.
     *
     * Schema format:
     * [
     *     'field_name' => [
     *         'filters' => [FILTER_SANITIZE_EMAIL, ...], // Additional filters to apply
     *         'options' => [FILTER_FLAG_STRIP_HIGH, ...], // Additional flags
     *         'skip_control_strip' => false, // Skip control character stripping
     *         'skip_normalize' => false, // Skip Unicode normalization
     *     ],
     * ]
     *
     * @param array<string, array{filters?: int[], options?: int[], skip_control_strip?: bool, skip_normalize?: bool}> $schema
     * @return static
     */
    public function withSchema(array $schema): static
    {
        $clone = clone $this;
        $clone->schema = $schema;

        return $clone;
    }

    /**
     * Set the logger for audit logging.
     *
     * @param LoggerInterface $logger
     * @param bool $enabled Whether to enable audit logging
     * @return static
     */
    public function withLogger(LoggerInterface $logger, bool $enabled = true): static
    {
        $clone = clone $this;
        $clone->logger = $logger;
        $clone->auditEnabled = $enabled;

        return $clone;
    }

    /**
     * Enable or disable Unicode NFC normalization.
     *
     * @param bool $enabled
     * @return static
     */
    public function withNormalization(bool $enabled): static
    {
        $clone = clone $this;
        $clone->normalizeUnicode = $enabled;

        return $clone;
    }

    /**
     * Strip dangerous control characters from all values.
     *
     * Only strips ASCII 0-31 (null bytes, control characters).
     * Preserves Unicode (UTF-8 high bytes) for international input.
     * Handles nested arrays recursively.
     * Applies per-field filter rules from schema.
     * Normalizes Unicode to NFC form (if enabled).
     */
    public function filter(array $input): array
    {
        if (empty($input)) {
            return [];
        }

        return $this->filterRecursive($input, '');
    }

    /**
     * Recursively filter array values.
     *
     * @param array $input
     * @param string $path Current path for nested arrays (for logging)
     * @return array
     */
    protected function filterRecursive(array $input, string $path = ''): array
    {
        $result = [];

        foreach ($input as $key => $value) {
            $currentPath = $path === '' ? (string) $key : $path . '.' . $key;

            if (is_array($value)) {
                // Recursively filter nested arrays
                $result[$key] = $this->filterRecursive($value, $currentPath);
            } elseif (is_string($value)) {
                // Apply filters to string values
                $result[$key] = $this->filterString($value, $currentPath, (string) $key);
            } else {
                // Pass through non-string values unchanged
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Apply filters to a string value.
     *
     * @param string $value
     * @param string $path Full path for logging
     * @param string $fieldName Top-level field name for schema lookup
     * @return string
     */
    protected function filterString(string $value, string $path, string $fieldName): string
    {
        $original = $value;
        $fieldSchema = $this->schema[$fieldName] ?? [];

        // Step 1: Unicode NFC normalization (unless skipped)
        $skipNormalize = $fieldSchema['skip_normalize'] ?? false;
        if ($this->normalizeUnicode && !$skipNormalize && $this->isNormalizerAvailable()) {
            $normalized = Normalizer::normalize($value, Normalizer::FORM_C);
            if ($normalized !== false) {
                $value = $normalized;
            }
        }

        // Step 2: Strip control characters (unless skipped)
        $skipControlStrip = $fieldSchema['skip_control_strip'] ?? false;
        if (!$skipControlStrip) {
            $value = filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW) ?? '';
        }

        // Step 3: Apply additional schema-defined filters
        $additionalFilters = $fieldSchema['filters'] ?? [];
        $additionalOptions = $fieldSchema['options'] ?? [];

        foreach ($additionalFilters as $filter) {
            $options = 0;
            foreach ($additionalOptions as $option) {
                $options |= $option;
            }

            $filtered = $options > 0
                ? filter_var($value, $filter, $options)
                : filter_var($value, $filter);
            if ($filtered !== false) {
                $value = $filtered;
            }
        }

        // Step 4: Audit logging if content was modified
        if ($this->auditEnabled && $this->logger !== null && $value !== $original) {
            $this->logSanitisation($path, $original, $value);
        }

        return $value;
    }

    /**
     * Log when content is modified during sanitisation.
     *
     * @param string $path Field path
     * @param string $original Original value
     * @param string $sanitised Sanitised value
     */
    protected function logSanitisation(string $path, string $original, string $sanitised): void
    {
        // Truncate long values for logging
        $maxLength = 100;
        $originalTruncated = mb_strlen($original) > $maxLength
            ? mb_substr($original, 0, $maxLength) . '...'
            : $original;
        $sanitisedTruncated = mb_strlen($sanitised) > $maxLength
            ? mb_substr($sanitised, 0, $maxLength) . '...'
            : $sanitised;

        // Convert control characters to visible representation for logging
        $originalVisible = $this->makeControlCharsVisible($originalTruncated);
        $sanitisedVisible = $this->makeControlCharsVisible($sanitisedTruncated);

        $this->logger->info('Input sanitised', [
            'field' => $path,
            'original' => $originalVisible,
            'sanitised' => $sanitisedVisible,
            'original_length' => mb_strlen($original),
            'sanitised_length' => mb_strlen($sanitised),
        ]);
    }

    /**
     * Convert control characters to visible Unicode representation.
     *
     * @param string $value
     * @return string
     */
    protected function makeControlCharsVisible(string $value): string
    {
        // Replace control characters (0x00-0x1F) with Unicode Control Pictures (U+2400-U+241F)
        return preg_replace_callback(
            '/[\x00-\x1F]/',
            fn ($matches) => mb_chr(0x2400 + ord($matches[0])),
            $value
        ) ?? $value;
    }

    /**
     * Check if the Normalizer class is available.
     *
     * @return bool
     */
    protected function isNormalizerAvailable(): bool
    {
        return class_exists(Normalizer::class);
    }
}
