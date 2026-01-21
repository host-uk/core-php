<?php

namespace Core\Mod\Web\Effects\Background;

class WavesEffect extends AnimatedSvgEffect
{
    public static function slug(): string
    {
        return 'waves';
    }

    public static function name(): string
    {
        return 'Waves';
    }

    public static function svgFile(): string
    {
        return 'fd6c5d094781b750b77408b7ec03a90f.svg';
    }

    public static function defaults(): array
    {
        return [
            'blur' => 3,
            'brightness' => 110,
            'opacity' => 100,
        ];
    }
}
