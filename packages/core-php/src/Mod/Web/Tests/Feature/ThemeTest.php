<?php

use Core\Mod\Web\Database\Seeders\BioThemeSeeder;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Theme;
use Core\Mod\Web\Services\ThemeService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
});

describe('BioLinkTheme Model', function () {
    it('can create a system theme', function () {
        $theme = Theme::create([
            'name' => 'Test Theme',
            'slug' => 'test-theme',
            'settings' => [
                'background' => [
                    'type' => 'color',
                    'color' => '#ff0000',
                ],
                'text_color' => '#ffffff',
                'button' => [
                    'background_color' => '#000000',
                    'text_color' => '#ffffff',
                    'border_radius' => '8px',
                ],
                'font_family' => 'Inter',
            ],
            'is_system' => true,
            'is_premium' => false,
        ]);

        expect($theme)->toBeInstanceOf(Theme::class)
            ->and($theme->name)->toBe('Test Theme')
            ->and($theme->slug)->toBe('test-theme')
            ->and($theme->is_system)->toBeTrue()
            ->and($theme->isSystem())->toBeTrue()
            ->and($theme->isCustom())->toBeFalse();
    });

    it('can create a custom user theme', function () {
        $theme = Theme::create([
            'user_id' => $this->user->id,
            'name' => 'My Custom Theme',
            'settings' => Theme::getDefaultSettings(),
            'is_system' => false,
        ]);

        expect($theme->user_id)->toBe($this->user->id)
            ->and($theme->isSystem())->toBeFalse()
            ->and($theme->isCustom())->toBeTrue();
    });

    it('generates slug automatically from name', function () {
        $theme = Theme::create([
            'name' => 'My Awesome Theme',
            'settings' => Theme::getDefaultSettings(),
        ]);

        expect($theme->slug)->toBe('my-awesome-theme');
    });

    it('returns correct background settings', function () {
        $theme = Theme::create([
            'name' => 'Gradient Theme',
            'settings' => [
                'background' => [
                    'type' => 'gradient',
                    'color' => '#ff0000',
                    'gradient_start' => '#ff0000',
                    'gradient_end' => '#0000ff',
                ],
                'text_color' => '#000000',
                'button' => Theme::getDefaultSettings()['button'],
                'font_family' => 'Inter',
            ],
        ]);

        $bg = $theme->getBackground();

        expect($bg['type'])->toBe('gradient')
            ->and($bg['gradient_start'])->toBe('#ff0000')
            ->and($bg['gradient_end'])->toBe('#0000ff');
    });

    it('generates CSS variables', function () {
        $theme = Theme::create([
            'name' => 'CSS Test Theme',
            'settings' => [
                'background' => [
                    'type' => 'color',
                    'color' => '#f5f5f5',
                ],
                'text_color' => '#333333',
                'button' => [
                    'background_color' => '#007bff',
                    'text_color' => '#ffffff',
                    'border_radius' => '12px',
                    'border_width' => '2px',
                    'border_color' => '#0056b3',
                ],
                'font_family' => 'Poppins',
            ],
        ]);

        $vars = $theme->toCssVariables();

        expect($vars['--biolink-bg'])->toBe('#f5f5f5')
            ->and($vars['--biolink-text'])->toBe('#333333')
            ->and($vars['--biolink-btn-bg'])->toBe('#007bff')
            ->and($vars['--biolink-btn-radius'])->toBe('12px')
            ->and($vars['--biolink-font'])->toContain('Poppins');
    });
});

describe('ThemeSeeder', function () {
    it('seeds 15 city-named themes', function () {
        $seeder = new BioThemeSeeder;
        $seeder->run();

        $themes = Theme::system()->get();

        expect($themes)->toHaveCount(15);

        // Check specific themes exist
        $themeNames = $themes->pluck('name')->toArray();
        expect($themeNames)->toContain('Paris')
            ->toContain('Tokyo')
            ->toContain('London')
            ->toContain('New York')
            ->toContain('Sydney');
    });

    it('marks some themes as premium', function () {
        $seeder = new BioThemeSeeder;
        $seeder->run();

        $premiumThemes = Theme::system()->where('is_premium', true)->get();
        $freeThemes = Theme::system()->where('is_premium', false)->get();

        // First 7 are free, rest are premium
        expect($freeThemes)->toHaveCount(7);
        expect($premiumThemes)->toHaveCount(8);
    });

    it('uses updateOrCreate for idempotency', function () {
        $seeder = new BioThemeSeeder;

        // Run twice
        $seeder->run();
        $seeder->run();

        // Should still have exactly 15
        expect(Theme::system()->count())->toBe(15);
    });
});

describe('ThemeService', function () {
    beforeEach(function () {
        // Seed themes
        (new BioThemeSeeder)->run();
    });

    it('returns available themes for user', function () {
        $service = app(ThemeService::class);
        $themes = $service->getAvailableThemes($this->user);

        expect($themes)->toHaveCount(15); // Just system themes, no custom yet
    });

    it('includes user custom themes in available list', function () {
        // Create custom theme
        Theme::create([
            'user_id' => $this->user->id,
            'name' => 'My Custom',
            'settings' => Theme::getDefaultSettings(),
        ]);

        $service = app(ThemeService::class);
        $themes = $service->getAvailableThemes($this->user);

        expect($themes)->toHaveCount(16); // 15 system + 1 custom
        expect($themes->pluck('name'))->toContain('My Custom');
    });

    it('can apply theme to biolink', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'themed-page',
        ]);

        $theme = Theme::where('slug', 'paris')->first();

        $service = app(ThemeService::class);
        $result = $service->applyTheme($biolink, $theme->id);

        expect($result)->toBeTrue();
        expect($biolink->fresh()->theme_id)->toBe($theme->id);
    });

    it('can remove theme from biolink', function () {
        $theme = Theme::where('slug', 'tokyo')->first();

        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'remove-theme-test',
            'theme_id' => $theme->id,
        ]);

        $service = app(ThemeService::class);
        $service->removeTheme($biolink);

        expect($biolink->fresh()->theme_id)->toBeNull();
    });

    it('can create custom theme for user', function () {
        $service = app(ThemeService::class);

        $theme = $service->createCustomTheme(
            $this->user,
            'Brand Theme',
            [
                'background' => ['type' => 'color', 'color' => '#1a1a1a'],
                'text_color' => '#ffffff',
            ],
            $this->workspace
        );

        expect($theme->user_id)->toBe($this->user->id)
            ->and($theme->workspace_id)->toBe($this->workspace->id)
            ->and($theme->name)->toBe('Brand Theme')
            ->and($theme->is_system)->toBeFalse();
    });

    it('can delete custom theme and unsets from biolinks', function () {
        $service = app(ThemeService::class);

        $theme = $service->createCustomTheme(
            $this->user,
            'To Delete',
            Theme::getDefaultSettings()
        );

        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'delete-theme-test',
            'theme_id' => $theme->id,
        ]);

        $service->deleteCustomTheme($theme);

        expect($biolink->fresh()->theme_id)->toBeNull();
        expect(Theme::find($theme->id))->toBeNull();
    });

    it('cannot delete system theme', function () {
        $theme = Theme::where('is_system', true)->first();

        $service = app(ThemeService::class);
        $result = $service->deleteCustomTheme($theme);

        expect($result)->toBeFalse();
        expect(Theme::find($theme->id))->not->toBeNull();
    });

    it('generates correct effective theme from biolink', function () {
        $theme = Theme::where('slug', 'paris')->first();

        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'effective-theme-test',
            'theme_id' => $theme->id,
        ]);

        $service = app(ThemeService::class);
        $effective = $service->getEffectiveTheme($biolink);

        expect($effective['background']['color'])->toBe('#faf7f2'); // Paris cream colour
        expect($effective['font_family'])->toBe('Playfair Display');
    });

    it('falls back to default theme when none set', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'no-theme-test',
        ]);

        $service = app(ThemeService::class);
        $effective = $service->getEffectiveTheme($biolink);

        // Should be default settings
        expect($effective['background']['type'])->toBe('color')
            ->and($effective['background']['color'])->toBe('#ffffff')
            ->and($effective['font_family'])->toBe('Inter');
    });

    it('provides list of available fonts', function () {
        $fonts = ThemeService::getAvailableFonts();

        expect($fonts)->toBeArray()
            ->toHaveKey('Inter')
            ->toHaveKey('Poppins')
            ->toHaveKey('Playfair Display')
            ->toHaveKey('system-ui');
    });
});

describe('BioLink Theme Integration', function () {
    beforeEach(function () {
        (new BioThemeSeeder)->run();
    });

    it('biolink has theme relationship', function () {
        $theme = Theme::where('slug', 'london')->first();

        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'relationship-test',
            'theme_id' => $theme->id,
        ]);

        expect($biolink->theme)->toBeInstanceOf(Theme::class)
            ->and($biolink->theme->name)->toBe('London');
    });

    it('gets background from theme when set', function () {
        $theme = Theme::where('slug', 'new-york')->first();

        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'bg-from-theme',
            'theme_id' => $theme->id,
        ]);

        $bg = $biolink->getBackground();

        expect($bg['color'])->toBe('#1a1a1a'); // New York dark background
    });

    it('gets background from inline settings when no theme', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'inline-bg-test',
            'settings' => [
                'background' => [
                    'type' => 'gradient',
                    'color' => '#ff0000',
                    'gradient_start' => '#ff0000',
                    'gradient_end' => '#00ff00',
                ],
            ],
        ]);

        $bg = $biolink->getBackground();

        expect($bg['type'])->toBe('gradient')
            ->and($bg['gradient_start'])->toBe('#ff0000');
    });

    it('gets button style from theme', function () {
        $theme = Theme::where('slug', 'tokyo')->first();

        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'button-style-test',
            'theme_id' => $theme->id,
        ]);

        $button = $biolink->getButtonStyle();

        expect($button['background_color'])->toBe('#ff006e'); // Tokyo neon pink
        expect($button['border_color'])->toBe('#00f5ff'); // Tokyo cyan border
    });

    it('gets font family from theme', function () {
        $theme = Theme::where('slug', 'dubai')->first();

        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'font-test',
            'theme_id' => $theme->id,
        ]);

        expect($biolink->getFontFamily())->toBe('Cormorant Garamond');
    });

    it('gets text colour from theme', function () {
        $theme = Theme::where('slug', 'miami')->first();

        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'text-color-test',
            'theme_id' => $theme->id,
        ]);

        expect($biolink->getTextColor())->toBe('#ffffff');
    });
});
