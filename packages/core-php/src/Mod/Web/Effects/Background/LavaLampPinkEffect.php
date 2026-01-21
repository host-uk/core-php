<?php

namespace Core\Mod\Web\Effects\Background;

class LavaLampPinkEffect extends AnimatedSvgEffect
{
    public static function slug(): string
    {
        return 'lava_lamp_pink';
    }

    public static function name(): string
    {
        return 'Lava Lamp (Pink)';
    }

    public static function svgFile(): string
    {
        return '2d120dd791037a99206d0dc856f4a0f4.svg';
    }

    public static function defaults(): array
    {
        return [
            'blur' => 0,
            'brightness' => 100,
            'opacity' => 100,
        ];
    }
}
