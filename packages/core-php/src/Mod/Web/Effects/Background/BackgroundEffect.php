<?php

namespace Core\Mod\Web\Effects\Background;

abstract class BackgroundEffect
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge(static::defaults(), $config);
    }

    /**
     * Unique identifier for this effect.
     */
    abstract public static function slug(): string;

    /**
     * Human-readable name.
     */
    abstract public static function name(): string;

    /**
     * Effect type: 'overlay' (layered on background) or 'animated' (replaces background).
     */
    abstract public static function type(): string;

    /**
     * Default configuration values.
     */
    abstract public static function defaults(): array;

    /**
     * Render the effect as CSS/HTML to be injected into the page.
     */
    abstract public function render(): string;

    /**
     * Get the asset path for this effect (SVG file, etc.).
     */
    public function assetPath(): ?string
    {
        return null;
    }

    /**
     * Get a config value with optional default.
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Get all config values.
     */
    public function config(): array
    {
        return $this->config;
    }

    /**
     * Get the configurable parameters for the editor UI.
     *
     * @return array<string, array{type: string, label: string, min?: int, max?: int, step?: float}>
     */
    public static function parameters(): array
    {
        return [
            'blur' => [
                'type' => 'range',
                'label' => 'Blur',
                'min' => 0,
                'max' => 10,
                'step' => 1,
            ],
            'brightness' => [
                'type' => 'range',
                'label' => 'Brightness',
                'min' => 50,
                'max' => 150,
                'step' => 5,
            ],
            'opacity' => [
                'type' => 'range',
                'label' => 'Opacity',
                'min' => 0,
                'max' => 100,
                'step' => 5,
            ],
        ];
    }
}
