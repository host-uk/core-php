<?php

namespace Core\Mod\Web\Effects\Background;

class AutumnLeavesEffect extends BackgroundEffect
{
    public static function slug(): string
    {
        return 'autumn_leaves';
    }

    public static function name(): string
    {
        return 'Autumn Leaves';
    }

    public static function type(): string
    {
        return 'overlay';
    }

    public static function defaults(): array
    {
        return [
            'blur' => 3,
            'brightness' => 100,
            'opacity' => 80,
            'speed' => 1.0,
            'layers' => 3,
        ];
    }

    public static function parameters(): array
    {
        return array_merge(parent::parameters(), [
            'speed' => [
                'type' => 'range',
                'label' => 'Speed',
                'min' => 0.5,
                'max' => 2.0,
                'step' => 0.1,
            ],
            'layers' => [
                'type' => 'range',
                'label' => 'Density',
                'min' => 1,
                'max' => 5,
                'step' => 1,
            ],
        ]);
    }

    public function assetPath(): string
    {
        return 'theme-backgrounds/autumn_leaves.svg';
    }

    public function render(): string
    {
        $blur = $this->get('blur');
        $brightness = $this->get('brightness');
        $opacity = $this->get('opacity') / 100;
        $layers = $this->get('layers');
        $asset = asset('storage/' . $this->assetPath());

        $images = [];
        $sizes = [];
        $baseSize = 60;

        for ($i = 0; $i < $layers; $i++) {
            $images[] = "url('{$asset}')";
            $sizes[] = ($baseSize - ($i * 15)) . '%';
        }

        $imageList = implode(',', $images);
        $sizeList = implode(',', $sizes);

        $css = <<<CSS
.effect-overlay-autumn-leaves {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    pointer-events: none;
    z-index: 1;
    background-image: {$imageList};
    background-size: {$sizeList};
    background-repeat: repeat;
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
.bio { position: relative; z-index: 1; }
CSS;
        }

        return "<style>{$css}</style><div class=\"effect-overlay-autumn-leaves\"></div>";
    }
}
