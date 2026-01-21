<?php

namespace Core\Mod\Web\Effects\Background;

class SnowEffect extends BackgroundEffect
{
    public static function slug(): string
    {
        return 'snow';
    }

    public static function name(): string
    {
        return 'Snow';
    }

    public static function type(): string
    {
        return 'canvas';
    }

    public static function defaults(): array
    {
        return [
            'blur' => 2,
            'brightness' => 100,
            'opacity' => 80,
            'speed' => 1.0,
            'density' => 100,
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
            'density' => [
                'type' => 'range',
                'label' => 'Density',
                'min' => 20,
                'max' => 200,
                'step' => 10,
            ],
        ]);
    }

    public function render(): string
    {
        $blur = $this->get('blur');
        $brightness = $this->get('brightness');
        $opacity = $this->get('opacity') / 100;
        $speed = $this->get('speed');
        $density = $this->get('density');

        $css = <<<CSS
#snow-canvas {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 1;
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

        $js = <<<JS
(function() {
    const canvas = document.getElementById('snow-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const speed = {$speed};
    const density = {$density};
    let snowflakes = [];

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        initSnowflakes();
    }

    function initSnowflakes() {
        snowflakes = [];
        for (let i = 0; i < density; i++) {
            snowflakes.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 3 + 1,
                d: Math.random() * density,
                speed: Math.random() * speed + 0.5
            });
        }
    }

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
        ctx.beginPath();
        for (let i = 0; i < snowflakes.length; i++) {
            const s = snowflakes[i];
            ctx.moveTo(s.x, s.y);
            ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2, true);
        }
        ctx.fill();
        update();
    }

    function update() {
        for (let i = 0; i < snowflakes.length; i++) {
            const s = snowflakes[i];
            s.y += s.speed;
            s.x += Math.sin(s.d) * 0.5;
            if (s.y > canvas.height) {
                s.y = -5;
                s.x = Math.random() * canvas.width;
            }
        }
    }

    resize();
    window.addEventListener('resize', resize);
    setInterval(draw, 33);
})();
JS;

        return "<style>{$css}</style><canvas id=\"snow-canvas\"></canvas><script>{$js}</script>";
    }
}
