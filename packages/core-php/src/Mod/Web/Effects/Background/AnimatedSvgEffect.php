<?php

namespace Core\Mod\Web\Effects\Background;

/**
 * Base class for animated SVG background effects.
 * These effects use an animated SVG as the full background.
 */
abstract class AnimatedSvgEffect extends BackgroundEffect
{
    public static function type(): string
    {
        return 'animated';
    }

    public static function defaults(): array
    {
        return [
            'blur' => 0,
            'brightness' => 100,
            'opacity' => 100,
        ];
    }

    /**
     * The SVG filename in storage/theme-backgrounds/
     */
    abstract public static function svgFile(): string;

    public function assetPath(): string
    {
        return 'theme-backgrounds/' . static::svgFile();
    }

    public function render(): string
    {
        $blur = $this->get('blur');
        $brightness = $this->get('brightness');
        $opacity = $this->get('opacity') / 100;
        $asset = asset('storage/' . $this->assetPath());

        $css = <<<CSS
.effect-animated-bg {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: -1;
    background-image: url('{$asset}');
    background-size: cover;
    background-position: center;
    opacity: {$opacity};
}
CSS;

        if ($blur > 0 || $brightness !== 100) {
            $css .= <<<CSS

.effect-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 0;
    backdrop-filter: blur({$blur}px) brightness({$brightness}%);
    -webkit-backdrop-filter: blur({$blur}px) brightness({$brightness}%);
}
CSS;
        }

        return "<style>{$css}</style><div class=\"effect-animated-bg\"></div>";
    }
}
