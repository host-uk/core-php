<?php

namespace Core\Mod\Web\Effects\Background;

class LavaLampPurpleEffect extends AnimatedSvgEffect
{
    public static function slug(): string
    {
        return 'lava_lamp_purple';
    }

    public static function name(): string
    {
        return 'Lava Lamp (Purple)';
    }

    public static function svgFile(): string
    {
        return 'b634495133c9091655dab3c3c916722e.svg';
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
