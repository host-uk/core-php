<?php

namespace Core\Mod\Web\Effects\Block;

/**
 * Base class for block-level effects.
 * Stubbed for future implementation.
 *
 * Future effects might include:
 * - Glow/shadow effects
 * - Hover animations
 * - Parallax scrolling
 * - Entrance animations
 * - Pulse/breathe effects
 */
abstract class BlockEffect
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge(static::defaults(), $config);
    }

    abstract public static function slug(): string;

    abstract public static function name(): string;

    abstract public static function defaults(): array;

    abstract public function render(string $blockId): string;

    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function config(): array
    {
        return $this->config;
    }
}
