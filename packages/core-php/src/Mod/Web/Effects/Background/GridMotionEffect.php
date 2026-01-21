<?php

namespace Core\Mod\Web\Effects\Background;

class GridMotionEffect extends AnimatedSvgEffect
{
    public static function slug(): string
    {
        return 'grid_motion';
    }

    public static function name(): string
    {
        return 'Grid Motion';
    }

    public static function svgFile(): string
    {
        return 'a74b6b499633319c462fe4c12a8e9c1d.svg';
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
