<?php

namespace Core\Mod\Web\Effects;

use Core\Mod\Web\Effects\Background\AutumnLeavesEffect;
use Core\Mod\Web\Effects\Background\BubblesEffect;
use Core\Mod\Web\Effects\Background\GridMotionEffect;
use Core\Mod\Web\Effects\Background\LavaLampPinkEffect;
use Core\Mod\Web\Effects\Background\LavaLampPurpleEffect;
use Core\Mod\Web\Effects\Background\LeavesEffect;
use Core\Mod\Web\Effects\Background\RainEffect;
use Core\Mod\Web\Effects\Background\SnowEffect;
use Core\Mod\Web\Effects\Background\StarsEffect;
use Core\Mod\Web\Effects\Background\WavesEffect;

class Catalog
{
    /**
     * Get all available background effects.
     *
     * @return array<string, class-string<Background\BackgroundEffect>>
     */
    public static function background(): array
    {
        return [
            'rain' => RainEffect::class,
            'leaves' => LeavesEffect::class,
            'autumn_leaves' => AutumnLeavesEffect::class,
            'snow' => SnowEffect::class,
            'waves' => WavesEffect::class,
            'bubbles' => BubblesEffect::class,
            'lava_lamp_pink' => LavaLampPinkEffect::class,
            'lava_lamp_purple' => LavaLampPurpleEffect::class,
            'grid_motion' => GridMotionEffect::class,
            'stars' => StarsEffect::class,
        ];
    }

    /**
     * Get all available block effects (stubbed for future).
     *
     * @return array<string, class-string<Block\BlockEffect>>
     */
    public static function block(): array
    {
        return [
            // Future: glow, parallax, hover, shadow, etc.
        ];
    }

    /**
     * Get a background effect class by slug.
     */
    public static function getBackgroundEffect(string $slug): ?string
    {
        return static::background()[$slug] ?? null;
    }

    /**
     * Get a block effect class by slug.
     */
    public static function getBlockEffect(string $slug): ?string
    {
        return static::block()[$slug] ?? null;
    }

    /**
     * Get background effects formatted for UI selection.
     *
     * @return array<string, array{slug: string, name: string, type: string, defaults: array, parameters: array}>
     */
    public static function backgroundOptions(): array
    {
        $options = [];

        foreach (static::background() as $slug => $class) {
            $options[$slug] = [
                'slug' => $class::slug(),
                'name' => $class::name(),
                'type' => $class::type(),
                'defaults' => $class::defaults(),
                'parameters' => $class::parameters(),
            ];
        }

        return $options;
    }

    /**
     * Get effect categories for grouping in UI.
     */
    public static function backgroundCategories(): array
    {
        return [
            'weather' => [
                'name' => 'Weather',
                'icon' => 'cloud',
                'effects' => ['rain', 'snow'],
            ],
            'nature' => [
                'name' => 'Nature',
                'icon' => 'leaf',
                'effects' => ['leaves', 'autumn_leaves'],
            ],
            'animated' => [
                'name' => 'Animated',
                'icon' => 'wand-magic-sparkles',
                'effects' => ['waves', 'bubbles', 'lava_lamp_pink', 'lava_lamp_purple', 'grid_motion', 'stars'],
            ],
        ];
    }
}
