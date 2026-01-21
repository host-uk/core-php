<?php

declare(strict_types=1);

namespace Core\Service;

/**
 * Represents a service version with deprecation information.
 *
 * Follows semantic versioning (major.minor.patch) with support
 * for deprecation notices and sunset dates.
 */
final readonly class ServiceVersion
{
    public function __construct(
        public int $major,
        public int $minor = 0,
        public int $patch = 0,
        public bool $deprecated = false,
        public ?string $deprecationMessage = null,
        public ?\DateTimeInterface $sunsetDate = null,
    ) {}

    /**
     * Create a version from a string (e.g., "1.2.3").
     */
    public static function fromString(string $version): self
    {
        $parts = explode('.', ltrim($version, 'v'));

        return new self(
            major: (int) ($parts[0] ?? 1),
            minor: (int) ($parts[1] ?? 0),
            patch: (int) ($parts[2] ?? 0),
        );
    }

    /**
     * Create a version 1.0.0 instance.
     */
    public static function initial(): self
    {
        return new self(1, 0, 0);
    }

    /**
     * Mark this version as deprecated.
     */
    public function deprecate(string $message, ?\DateTimeInterface $sunsetDate = null): self
    {
        return new self(
            major: $this->major,
            minor: $this->minor,
            patch: $this->patch,
            deprecated: true,
            deprecationMessage: $message,
            sunsetDate: $sunsetDate,
        );
    }

    /**
     * Check if the version is past its sunset date.
     */
    public function isPastSunset(): bool
    {
        if ($this->sunsetDate === null) {
            return false;
        }

        return $this->sunsetDate < new \DateTimeImmutable;
    }

    /**
     * Compare with another version.
     *
     * @return int -1 if less, 0 if equal, 1 if greater
     */
    public function compare(self $other): int
    {
        if ($this->major !== $other->major) {
            return $this->major <=> $other->major;
        }

        if ($this->minor !== $other->minor) {
            return $this->minor <=> $other->minor;
        }

        return $this->patch <=> $other->patch;
    }

    /**
     * Check if this version is compatible with a minimum version.
     * Compatible if same major version and >= minor.patch.
     */
    public function isCompatibleWith(self $minimum): bool
    {
        if ($this->major !== $minimum->major) {
            return false;
        }

        return $this->compare($minimum) >= 0;
    }

    /**
     * Get version as string.
     */
    public function toString(): string
    {
        return "{$this->major}.{$this->minor}.{$this->patch}";
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'version' => $this->toString(),
            'deprecated' => $this->deprecated ?: null,
            'deprecation_message' => $this->deprecationMessage,
            'sunset_date' => $this->sunsetDate?->format('Y-m-d'),
        ], fn ($v) => $v !== null);
    }
}
