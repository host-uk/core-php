<?php

namespace Core\Mod\Web\Effects\Background;

class StarsEffect extends AnimatedSvgEffect
{
    public static function slug(): string
    {
        return 'stars';
    }

    public static function name(): string
    {
        return 'Twinkling Stars';
    }

    public static function svgFile(): string
    {
        return '567753a3181cb7d22b3c4e300e502986.svg';
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
