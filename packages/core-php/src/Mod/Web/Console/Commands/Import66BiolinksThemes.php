<?php

namespace Core\Mod\Web\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Core\Mod\Web\Models\Theme;

/**
 * Import themes from 66biolinks (AltumCode) software.
 *
 * Reads from SQL dump or JSON export and converts to Host Hub theme format.
 * See doc/vendor/66biolinks-extraction.md for full documentation.
 */
class Import66BiolinksThemes extends Command
{
    protected $signature = 'webpage:import-66biolinks-themes
                            {--source= : Path to schema.sql or themes.json}
                            {--dry-run : Show what would be imported without saving}
                            {--copy-backgrounds : Copy background images to storage}';

    protected $description = 'Import themes from 66biolinks software';

    /**
     * Background preset gradients from 66biolinks.
     */
    protected array $presets = [
        'zero' => 'linear-gradient(43deg, #4158D0 0%, #C850C0 46%, #FFCC70 100%)',
        'one' => 'linear-gradient(19deg, #21D4FD 0%, #B721FF 100%)',
        'two' => 'linear-gradient(109.6deg, #ffb418 11.2%, #f73131 91.1%)',
        'three' => 'linear-gradient(135deg, #79F1A4 10%, #0E5CAD 100%)',
        'four' => 'linear-gradient(to bottom, #ff758c, #ff7eb3)',
        'five' => 'linear-gradient(292.2deg, #3355ff 33.7%, #0088ff 93.7%)',
        'six' => 'linear-gradient(to bottom, #fc5c7d, #6a82fb)',
        'seven' => 'linear-gradient(112.1deg, rgb(32, 38, 57) 11.4%, rgb(63, 76, 119) 70.2%)',
        'eight' => 'radial-gradient(circle farthest-corner at 10% 20%, rgba(100,43,115,1) 0%, rgba(4,0,4,1) 90%)',
        'nine' => 'linear-gradient(62deg, #8EC5FC 0%, #E0C3FC 100%)',
        'ten' => 'linear-gradient(0deg, #FFDEE9 0%, #B5FFFC 100%)',
        'eleven' => 'linear-gradient(90deg, #74EBD5 0%, #9FACE6 100%)',
        'twelve' => 'linear-gradient(45deg, #FBDA61 0%, #FF5ACD 100%)',
        'thirteen' => 'linear-gradient(179.7deg, rgba(249,21,215,1) 1.1%, rgba(22,0,98,1) 99%)',
        'fourteen' => 'radial-gradient(circle farthest-corner at 10% 20%, rgba(2,37,78,1) 0%, rgba(4,56,126,1) 19.7%, rgba(85,245,221,1) 100.2%)',
        'fifteen' => 'linear-gradient(91.2deg, rgba(136,80,226,1) 4%, rgba(16,13,91,1) 96.5%)',
        'sixteen' => 'linear-gradient(109.6deg, rgba(254,253,205,1) 11.2%, rgba(163,230,255,1) 91.1%)',
        'seventeen' => 'linear-gradient(174.2deg, rgba(255,244,228,1) 7.1%, rgba(240,246,238,1) 67.4%)',
        'eighteen' => 'linear-gradient(64.3deg, rgba(254,122,152,0.81) 17.7%, rgba(255,206,134,1) 64.7%, rgba(172,253,163,0.64) 112.1%)',
        'nineteen' => 'radial-gradient(circle farthest-corner at 10.2% 55.8%, rgba(252,37,103,1) 0%, rgba(250,38,151,1) 46.2%, rgba(186,8,181,1) 90.1%)',
        'twenty' => 'radial-gradient(circle farthest-corner at 10% 20%, rgba(97,186,255,1) 0%, rgba(166,239,253,1) 90.1%)',
        'twentyone' => 'radial-gradient(circle 588px at 31.7% 40.2%, rgba(225,200,239,1) 21.4%, rgba(163,225,233,1) 57.1%)',
        'twentytwo' => 'linear-gradient(109.6deg, rgba(209,0,116,1) 11.2%, rgba(110,44,107,1) 91.1%)',
        'twentythree' => 'radial-gradient(circle 685.3px at 47.8% 55.1%, rgba(255,99,152,1) 0%, rgba(251,213,149,1) 90.1%)',
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
        'rounded-lg' => '12px',
        'rounded-pill' => '50px',
    ];

    public function handle(): int
    {
        $source = $this->option('source') ?? $this->findDefaultSource();

        if (! $source || ! File::exists($source)) {
            $this->error('Source file not found. Provide --source or place schema.sql in storage/app/imports/');

            return 1;
        }

        $this->info("Reading themes from: {$source}");

        $themes = $this->extractThemes($source);

        if (empty($themes)) {
            $this->error('No themes found in source file.');

            return 1;
        }

        $this->info('Found '.count($themes).' themes');
        $this->newLine();

        foreach ($themes as $theme) {
            $converted = $this->convertTheme($theme);

            if ($this->option('dry-run')) {
                $this->displayTheme($converted);
            } else {
                $this->saveTheme($converted);
            }
        }

        if ($this->option('copy-backgrounds')) {
            $this->copyBackgrounds($themes);
        }

        $this->newLine();
        $this->info('Done!');

        return 0;
    }

    protected function findDefaultSource(): ?string
    {
        $paths = [
            storage_path('app/imports/66biolinks-themes.json'),
            storage_path('app/imports/schema.sql'),
            base_path('../bio.host.uk.com/database/schema.sql'),
        ];

        foreach ($paths as $path) {
            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }

    protected function extractThemes(string $source): array
    {
        $content = File::get($source);

        // JSON file
        if (Str::endsWith($source, '.json')) {
            return json_decode($content, true) ?? [];
        }

        // SQL dump - extract INSERT statements for biolinks_themes
        return $this->parseSqlDump($content);
    }

    protected function parseSqlDump(string $content): array
    {
        $themes = [];
        $lines = explode("\n", $content);
        $inThemesBlock = false;

        foreach ($lines as $line) {
            // Start of biolinks_themes INSERT
            if (str_contains($line, 'INSERT INTO `biolinks_themes` VALUES')) {
                $inThemesBlock = true;

                continue;
            }

            // End of INSERT block
            if ($inThemesBlock && (str_starts_with(trim($line), '/*!') || str_starts_with(trim($line), 'UNLOCK'))) {
                break;
            }

            // Parse theme row
            if ($inThemesBlock && preg_match("/^\((\d+),'([^']+)'/", trim($line), $match)) {
                $theme = $this->parseThemeRow(trim($line));
                if ($theme) {
                    $themes[] = $theme;
                }
            }
        }

        return $themes;
    }

    protected function parseThemeRow(string $row): ?array
    {
        // Remove outer parentheses
        $row = trim($row, '()');

        // Extract fields by finding the JSON boundaries
        // Format: id,'name','json_settings',is_enabled,order,'last_datetime','datetime'

        // Find ID (first number)
        if (! preg_match('/^(\d+),/', $row, $idMatch)) {
            return null;
        }
        $id = (int) $idMatch[1];
        $row = substr($row, strlen($idMatch[0]));

        // Find name (first quoted string)
        if (! preg_match("/^'([^']+)',/", $row, $nameMatch)) {
            return null;
        }
        $name = $nameMatch[1];
        $row = substr($row, strlen($nameMatch[0]));

        // Find JSON - starts with '{ and ends with }' before a comma
        // Handle escaped quotes within JSON
        if ($row[0] !== "'") {
            return null;
        }

        $jsonStart = 1;
        $jsonEnd = $this->findJsonEnd($row, $jsonStart);
        if ($jsonEnd === false) {
            return null;
        }

        $jsonStr = substr($row, $jsonStart, $jsonEnd - $jsonStart);
        // The SQL dump escapes: \" becomes \\\" in the file
        // First pass: convert \\\" to \"
        $jsonStr = stripslashes($jsonStr);
        // Now we have standard JSON with \" for quotes - this should parse

        $settings = json_decode($jsonStr, true);
        if (! $settings) {
            $this->warn("Failed to parse JSON for theme: {$name}");

            return null;
        }

        // Parse remaining fields
        $remaining = substr($row, $jsonEnd + 2); // Skip closing quote and comma
        $parts = explode(',', $remaining);

        return [
            'id' => $id,
            'name' => $name,
            'settings' => $settings,
            'is_enabled' => (bool) (trim($parts[0] ?? '1')),
            'order' => (int) trim($parts[1] ?? '0'),
        ];
    }

    protected function findJsonEnd(string $str, int $start): int|false
    {
        $len = strlen($str);
        $braceDepth = 0;
        $inJson = false;

        for ($i = $start; $i < $len; $i++) {
            $char = $str[$i];
            $prev = $i > 0 ? $str[$i - 1] : '';

            if ($char === '{') {
                $braceDepth++;
                $inJson = true;
            } elseif ($char === '}') {
                $braceDepth--;
                if ($braceDepth === 0 && $inJson) {
                    return $i + 1; // Position after closing brace
                }
            }
        }

        return false;
    }

    protected function convertTheme(array $source): array
    {
        $settings = $source['settings'];
        $biolink = $settings['biolink'] ?? [];
        $block = $settings['biolink_block'] ?? [];
        $heading = $settings['biolink_block_heading'] ?? [];
        $additional = $settings['additional'] ?? [];

        // Determine background type and value
        $background = $this->convertBackground($biolink);

        // Convert button settings
        $button = [
            'background_color' => $block['background_color'] ?? '#000000',
            'text_color' => $block['text_color'] ?? '#ffffff',
            'border_radius' => $this->borderRadiusMap[$block['border_radius'] ?? 'rounded'] ?? '8px',
            'border_width' => ($block['border_width'] ?? '0').'px',
            'border_color' => $block['border_color'] ?? null,
        ];

        // Build converted theme
        return [
            'name' => $source['name'],
            'slug' => Str::slug($source['name']),
            'is_premium' => false, // We don't paywall nice things
            'is_animated' => ! empty($additional['custom_js']),
            'source_id' => $source['id'],
            'settings' => [
                'background' => $background,
                'text_color' => $heading['text_color'] ?? '#ffffff',
                'button' => $button,
                'font_family' => $this->fontMap[$biolink['font'] ?? 'default'] ?? 'Inter',
                'font_size' => $biolink['font_size'] ?? 16,
                'block_spacing' => $biolink['block_spacing'] ?? 2,
                'background_blur' => $biolink['background_blur'] ?? 0,
                'background_brightness' => $biolink['background_brightness'] ?? 100,
            ],
            'additional' => [
                'custom_css' => $additional['custom_css'] ?? null,
                'custom_js' => $additional['custom_js'] ?? null,
            ],
        ];
    }

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
                    'blend_modes' => true,
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

    protected function displayTheme(array $theme): void
    {
        $this->line("<fg=cyan>{$theme['name']}</> (slug: {$theme['slug']})");
        $this->line('  Animated: '.($theme['is_animated'] ? 'Yes' : 'No'));
        $this->line('  Background: '.$theme['settings']['background']['type']);
        $this->line('  Font: '.$theme['settings']['font_family']);
        $this->newLine();
    }

    protected function saveTheme(array $converted): void
    {
        $existing = Theme::where('slug', $converted['slug'])->first();

        if ($existing) {
            $this->line("<fg=yellow>Updating:</> {$converted['name']}");
            $existing->update([
                'settings' => $converted['settings'],
                'is_premium' => $converted['is_premium'],
            ]);
        } else {
            $this->line("<fg=green>Creating:</> {$converted['name']}");
            Theme::create([
                'name' => $converted['name'],
                'slug' => $converted['slug'],
                'settings' => $converted['settings'],
                'is_system' => true,
                'is_premium' => $converted['is_premium'],
                'is_active' => true,
                'category' => $converted['is_animated'] ? 'animated' : 'standard',
            ]);
        }
    }

    protected function copyBackgrounds(array $themes): void
    {
        $sourcePath = base_path('../66biolinks/product/uploads/backgrounds');
        $destPath = storage_path('app/public/theme-backgrounds');

        if (! File::isDirectory($destPath)) {
            File::makeDirectory($destPath, 0755, true);
        }

        foreach ($themes as $theme) {
            $bg = $theme['settings']['biolink']['background'] ?? null;
            $type = $theme['settings']['biolink']['background_type'] ?? '';

            if ($type === 'image' && $bg) {
                $sourceFile = "{$sourcePath}/{$bg}";
                if (File::exists($sourceFile)) {
                    File::copy($sourceFile, "{$destPath}/{$bg}");
                    $this->line("<fg=blue>Copied:</> {$bg}");
                }
            }
        }
    }
}
