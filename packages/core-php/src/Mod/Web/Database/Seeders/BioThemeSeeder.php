<?php

namespace Core\Mod\Web\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Core\Mod\Web\Models\Theme;

/**
 * Seeds themes from the 66biolinks export.
 *
 * Source: doc/vendor/66biolinks-themes-export.json
 */
class BioThemeSeeder extends Seeder
{
    /**
     * Background gradient presets from 66biolinks.
     */
    protected array $presets = [
        'zero' => 'linear-gradient(43deg, #4158D0 0%, #C850C0 46%, #FFCC70 100%)',
        'one' => 'linear-gradient(180deg, #A9C9FF 0%, #FFBBEC 100%)',
        'two' => 'linear-gradient(90deg, #00DBDE 0%, #FC00FF 100%)',
        'three' => 'linear-gradient(0deg, #08AEEA 0%, #2AF598 100%)',
        'four' => 'linear-gradient(0deg, #FF3CAC 0%, #784BA0 50%, #2B86C5 100%)',
        'five' => 'linear-gradient(45deg, #FA8BFF 0%, #2BD2FF 52%, #2BFF88 90%)',
        'six' => 'linear-gradient(180deg, #FFE53B 0%, #FF2525 74%)',
        'seven' => 'linear-gradient(160deg, #0093E9 0%, #80D0C7 100%)',
        'eight' => 'linear-gradient(90deg, #FBDA61 0%, #FF5ACD 100%)',
        'nine' => 'linear-gradient(45deg, #8BC6EC 0%, #9599E2 100%)',
        'ten' => 'linear-gradient(0deg, #FFDEE9 0%, #B5FFFC 100%)',
        'eleven' => 'linear-gradient(62deg, #8EC5FC 0%, #E0C3FC 100%)',
        'twelve' => 'linear-gradient(45deg, #85FFBD 0%, #FFFB7D 100%)',
        'thirteen' => 'linear-gradient(180deg, #A9C9FF 0%, #6713D2 100%)',
    ];

    /**
     * Abstract presets with blend modes.
     */
    protected array $abstractPresets = [
        'one' => 'linear-gradient(120deg, #FF00C7 0%, #51003F 100%), linear-gradient(120deg, #0030AD 0%, #00071A 100%), linear-gradient(180deg, #000346 0%, #FF0000 100%), linear-gradient(60deg, #0029FF 0%, #AA0014 100%), radial-gradient(100% 165% at 100% 100%, #FF00A8 0%, #00FF47 100%), radial-gradient(100% 150% at 0% 0%, #FFF500 0%, #51D500 100%)',
        'two' => 'linear-gradient(115deg, rgb(211, 255, 215) 0%, rgb(0, 0, 0) 100%), radial-gradient(90% 100% at 50% 0%, rgb(200, 200, 200) 0%, rgb(22, 0, 45) 100%), radial-gradient(100% 100% at 80% 0%, rgb(250, 255, 0) 0%, rgb(36, 0, 0) 100%), radial-gradient(150% 210% at 100% 0%, rgb(112, 255, 0) 0%, rgb(20, 175, 125) 0%, rgb(0, 10, 255) 100%), radial-gradient(100% 100% at 100% 30%, rgb(255, 77, 0) 0%, rgba(0, 200, 255, 1) 100%), linear-gradient(60deg, rgb(255, 0, 0) 0%, rgb(120, 86, 255) 100%)',
        'three' => 'linear-gradient(115deg, #000000 0%, #00C508 55%, #000000 100%), linear-gradient(115deg, #0057FF 0%, #020077 100%), conic-gradient(from 110deg at -5% 35%, #000000 0deg, #FAFF00 360deg), conic-gradient(from 220deg at 30% 30%, #FF0000 0deg, #0000FF 220deg, #240060 360deg), conic-gradient(from 235deg at 60% 35%, #0089D7 0deg, #0000FF 180deg, #240060 360deg)',
        'four' => 'linear-gradient(180deg, #FFB7B7 0%, #727272 100%), radial-gradient(60.91% 100% at 50% 0%, #FFD1D1 0%, #260000 100%), linear-gradient(238.72deg, #FFDDDD 0%, #720066 100%), linear-gradient(127.43deg, #00FFFF 0%, #FF4444 100%), radial-gradient(100.22% 100% at 70.57% 0%, #FF0000 0%, #00FFE0 100%), linear-gradient(127.43deg, #B7D500 0%, #3300FF 100%)',
        'five' => 'radial-gradient(100% 225% at 0% 0%, #DE3E3E 0%, #17115C 100%), radial-gradient(100% 225% at 100% 0%, #FF9040 0%, #FF0000 100%), linear-gradient(180deg, #CE63B7 0%, #ED6283 100%), radial-gradient(100% 120% at 75% 0%, #A74600 0%, #000000 100%), linear-gradient(310deg, #0063D8 0%, #16009A 50%)',
        'six' => 'linear-gradient(120deg, #FF0000 0%, #2400FF 100%), linear-gradient(120deg, #FA00FF 0%, #208200 100%), linear-gradient(130deg, #00F0FF 0%, #000000 100%), radial-gradient(110% 140% at 15% 90%, #ffffff 0%, #1700A4 100%), radial-gradient(100% 100% at 50% 0%, #AD00FF 0%, #00FFE0 100%), radial-gradient(100% 100% at 50% 0%, #00FFE0 0%, #7300A9 80%), linear-gradient(30deg, #7ca304 0%, #2200AA 100%)',
        'seven' => 'linear-gradient(180deg, #0C003C 0%, #BFFFAF 100%), linear-gradient(165deg, #480045 25%, #E9EAAF 100%), linear-gradient(145deg, #480045 25%, #E9EAAF 100%), linear-gradient(300deg, rgba(233, 223, 255, 0) 0%, #AF89FF 100%), linear-gradient(90deg, #45EBA5 0%, #45EBA5 30%, #21ABA5 30%, #21ABA5 60%, #1D566E 60%, #1D566E 70%, #163A5F 70%, #163A5F 100%)',
        'eight' => 'linear-gradient(235deg, #BABC4A 0%, #000000 100%), linear-gradient(235deg, #0026AC 0%, #282534 100%), linear-gradient(235deg, #00FFD1 0%, #000000 100%), radial-gradient(120% 185% at 25% -25%, #EEEEEE 0%, #EEEEEE 40%, #7971EA calc(40% + 1px), #7971EA 50%, #393E46 calc(50% + 1px), #393E46 70%, #222831 calc(70% + 1px), #222831 100%), radial-gradient(70% 140% at 90% 10%, #F5F5C6 0%, #F5F5C6 30%, #7DA87B calc(30% + 1px), #7DA87B 60%, #326765 calc(60% + 1px), #326765 80%, #27253D calc(80% + 1px), #27253D 100%)',
    ];

    /**
     * Font mapping from 66biolinks keys to font names.
     */
    protected array $fontMap = [
        'default' => 'Inter',
        'inter' => 'Inter',
        'montserrat' => 'Montserrat',
        'karla' => 'Karla',
        'inconsolata' => 'Inconsolata',
        'trebuchet-ms' => 'Trebuchet MS',
        'verdana' => 'Verdana',
        'comic-sans-ms' => 'Comic Sans MS',
        'lato' => 'Lato',
    ];

    /**
     * Border radius mapping.
     */
    protected array $borderRadiusMap = [
        'straight' => '0px',
        'rounded' => '8px',
        'round' => '24px',
    ];

    /**
     * Effect mapping from 66biolinks to our Effects system.
     */
    protected array $effectMap = [
        'snowfall' => 'snow',
        'rain.svg' => 'rain',
        'leaves.svg' => 'leaves',
        'autumn_leaves.svg' => 'autumn_leaves',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Skip if themes table doesn't exist
        if (! Schema::hasTable('biolink_themes')) {
            return;
        }

        // Skip if workspaces table doesn't exist (FK constraint would fail on SQLite)
        if (! Schema::hasTable('workspaces')) {
            return;
        }

        $sourcePath = base_path('doc/vendor/66biolinks-themes-export.json');

        if (! File::exists($sourcePath)) {
            $this->command?->error('Theme export file not found: ' . $sourcePath);
            return;
        }

        $data = json_decode(File::get($sourcePath), true);

        if (empty($data['themes'])) {
            $this->command?->error('No themes found in export file');
            return;
        }

        foreach ($data['themes'] as $index => $source) {
            $converted = $this->convertTheme($source);

            Theme::updateOrCreate(
                ['slug' => $converted['slug']],
                [
                    'name' => $converted['name'],
                    'settings' => $converted['settings'],
                    'is_system' => true,
                    'is_premium' => false,
                    'is_active' => true,
                    'is_gallery' => true,
                    'category' => $this->guessCategory($converted),
                    'description' => $this->generateDescription($converted),
                    'sort_order' => $index,
                ]
            );
        }
    }

    /**
     * Convert a 66biolinks theme to our format.
     */
    protected function convertTheme(array $source): array
    {
        $settings = $source['settings'];
        $biolink = $settings['biolink'] ?? [];
        $block = $settings['biolink_block'] ?? [];
        $heading = $settings['biolink_block_heading'] ?? [];
        $additional = $settings['additional'] ?? [];

        $background = $this->convertBackground($biolink);
        $suggestedEffects = $this->convertEffects($biolink, $additional);

        return [
            'name' => $source['name'],
            'slug' => Str::slug($source['name']),
            'settings' => [
                'background' => $background,
                'text_color' => $heading['text_color'] ?? '#ffffff',
                'button' => [
                    'background_color' => $block['background_color'] ?? '#000000',
                    'text_color' => $block['text_color'] ?? '#ffffff',
                    'border_radius' => $this->borderRadiusMap[$block['border_radius'] ?? 'rounded'] ?? '8px',
                    'border_width' => ($block['border_width'] ?? '0') . 'px',
                    'border_color' => $block['border_color'] ?? null,
                ],
                'font_family' => $this->fontMap[$biolink['font'] ?? 'default'] ?? 'Inter',
                'background_blur' => (int) ($biolink['background_blur'] ?? 0),
                'background_brightness' => (int) ($biolink['background_brightness'] ?? 100),
            ],
            'suggested_effects' => $suggestedEffects,
        ];
    }

    /**
     * Convert background settings.
     */
    protected function convertBackground(array $biolink): array
    {
        $type = $biolink['background_type'] ?? 'color';
        $value = $biolink['background'] ?? null;

        switch ($type) {
            case 'preset':
                return [
                    'type' => 'gradient',
                    'css' => $this->presets[$value] ?? null,
                    'preset' => $value,
                ];

            case 'preset_abstract':
                return [
                    'type' => 'advanced',
                    'css' => $this->abstractPresets[$value] ?? null,
                    'preset' => $value,
                ];

            case 'gradient':
                return [
                    'type' => 'gradient',
                    'gradient_start' => $biolink['background_color_one'] ?? '#ffffff',
                    'gradient_end' => $biolink['background_color_two'] ?? '#ffffff',
                ];

            case 'color':
                return [
                    'type' => 'color',
                    'color' => $biolink['background_color_one'] ?? '#ffffff',
                ];

            case 'image':
                return [
                    'type' => 'image',
                    'image' => $value,
                    'color' => $biolink['background_color_one'] ?? '#000000',
                ];

            default:
                return [
                    'type' => 'color',
                    'color' => '#ffffff',
                ];
        }
    }

    /**
     * Convert effect/animation settings.
     */
    protected function convertEffects(array $biolink, array $additional): ?array
    {
        $effects = [];
        $blur = (int) ($biolink['background_blur'] ?? 0);
        $brightness = (int) ($biolink['background_brightness'] ?? 100);

        // Check for animation (snowfall)
        $animation = $additional['animation'] ?? null;
        if ($animation && isset($this->effectMap[$animation])) {
            $effects['background'] = [
                'effect' => $this->effectMap[$animation],
                'blur' => $blur,
                'brightness' => $brightness,
                'opacity' => 80,
                'speed' => 1,
                'density' => 100,
            ];
            return $effects;
        }

        // Check for overlay (rain, leaves)
        $overlay = $additional['overlay'] ?? null;
        if ($overlay && isset($this->effectMap[$overlay])) {
            $effects['background'] = [
                'effect' => $this->effectMap[$overlay],
                'blur' => $blur,
                'brightness' => $brightness,
                'opacity' => 80,
                'layers' => 3,
            ];
            return $effects;
        }

        return null;
    }

    /**
     * Guess a category based on theme settings.
     */
    protected function guessCategory(array $converted): string
    {
        $bg = $converted['settings']['background'] ?? [];

        if (($bg['type'] ?? '') === 'advanced') {
            return 'creative';
        }

        if (($bg['type'] ?? '') === 'image') {
            return 'creative';
        }

        if (isset($converted['suggested_effects'])) {
            return 'animated';
        }

        return 'standard';
    }

    /**
     * Generate a description based on theme settings.
     */
    protected function generateDescription(array $converted): string
    {
        $name = $converted['name'];
        $bg = $converted['settings']['background'] ?? [];
        $effects = $converted['suggested_effects']['background']['effect'] ?? null;

        $desc = "{$name} theme";

        if ($effects === 'snow') {
            $desc .= ' with falling snow effect';
        } elseif ($effects === 'rain') {
            $desc .= ' with rain overlay';
        } elseif ($effects === 'leaves' || $effects === 'autumn_leaves') {
            $desc .= ' with falling leaves';
        }

        return $desc . '.';
    }
}
