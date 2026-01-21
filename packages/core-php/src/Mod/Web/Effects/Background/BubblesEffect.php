<?php

namespace Core\Mod\Web\Effects\Background;

class BubblesEffect extends AnimatedSvgEffect
{
    public static function slug(): string
    {
        return 'bubbles';
    }

    public static function name(): string
    {
        return 'Bubbles';
    }

    public static function svgFile(): string
    {
        return '289e2f60e6eb4d5a8a57394b9aabb8d7.svg';
    }

    public static function defaults(): array
    {
        return [
            'blur' => 5,
            'brightness' => 115,
            'opacity' => 100,
        ];
    }
}
