<?php

use Core\Mod\Web\Database\Seeders\BioThemeGallerySeeder;
use Core\Mod\Web\Database\Seeders\BioThemeSeeder;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Theme;
use Core\Mod\Web\Models\ThemeFavourite;
use Core\Mod\Web\Services\ThemePreviewGenerator;
use Core\Mod\Web\Services\ThemeService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;
use Core\Mod\Tenant\Models\Feature;
use Core\Mod\Tenant\Models\Package;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);

    // Seed base themes
    (new BioThemeSeeder)->run();
});

describe('Gallery Fields Migration', function () {
    it('has gallery fields on biolink_themes table', function () {
        $theme = Theme::create([
            'name' => 'Gallery Test Theme',
            'settings' => Theme::getDefaultSettings(),
            'is_gallery' => true,
            'category' => 'professional',
            'description' => 'A professional theme for business use.',
            'preview_image' => 'https://example.com/preview.jpg',
        ]);

        expect($theme->is_gallery)->toBeTrue()
            ->and($theme->category)->toBe('professional')
            ->and($theme->description)->toBe('A professional theme for business use.')
            ->and($theme->preview_image)->toBe('https://example.com/preview.jpg');
    });

    it('casts is_gallery as boolean', function () {
        $theme = Theme::create([
            'name' => 'Boolean Test',
            'settings' => Theme::getDefaultSettings(),
            'is_gallery' => 1, // Integer
        ]);

        expect($theme->is_gallery)->toBeTrue()
            ->and($theme->is_gallery)->toBeBool();
    });
});

describe('ThemeFavourite Model', function () {
    it('can create a favourite', function () {
        $theme = Theme::first();

        $favourite = ThemeFavourite::create([
            'user_id' => $this->user->id,
            'theme_id' => $theme->id,
        ]);

        expect($favourite)->toBeInstanceOf(ThemeFavourite::class)
            ->and($favourite->user_id)->toBe($this->user->id)
            ->and($favourite->theme_id)->toBe($theme->id);
    });

    it('prevents duplicate favourites with unique constraint', function () {
        $theme = Theme::first();

        ThemeFavourite::create([
            'user_id' => $this->user->id,
            'theme_id' => $theme->id,
        ]);

        // Attempt duplicate
        expect(fn () => ThemeFavourite::create([
            'user_id' => $this->user->id,
            'theme_id' => $theme->id,
        ]))->toThrow(\Exception::class);
    });

    it('toggles favourite on and off', function () {
        $theme = Theme::first();

        // Toggle on
        $result = ThemeFavourite::toggle($this->user, $theme->id);
        expect($result)->toBeTrue();
        expect(ThemeFavourite::isFavourited($this->user, $theme->id))->toBeTrue();

        // Toggle off
        $result = ThemeFavourite::toggle($this->user, $theme->id);
        expect($result)->toBeFalse();
        expect(ThemeFavourite::isFavourited($this->user, $theme->id))->toBeFalse();
    });

    it('has relationship to user', function () {
        $theme = Theme::first();

        $favourite = ThemeFavourite::create([
            'user_id' => $this->user->id,
            'theme_id' => $theme->id,
        ]);

        expect($favourite->user)->toBeInstanceOf(User::class)
            ->and($favourite->user->id)->toBe($this->user->id);
    });

    it('has relationship to theme', function () {
        $theme = Theme::first();

        $favourite = ThemeFavourite::create([
            'user_id' => $this->user->id,
            'theme_id' => $theme->id,
        ]);

        expect($favourite->theme)->toBeInstanceOf(Theme::class)
            ->and($favourite->theme->id)->toBe($theme->id);
    });

    it('cascades delete when theme is deleted', function () {
        $theme = Theme::create([
            'name' => 'To Delete',
            'settings' => Theme::getDefaultSettings(),
        ]);

        $favourite = ThemeFavourite::create([
            'user_id' => $this->user->id,
            'theme_id' => $theme->id,
        ]);

        $favouriteId = $favourite->id;
        $theme->forceDelete();

        expect(ThemeFavourite::find($favouriteId))->toBeNull();
    });
});

describe('BioLinkTheme Gallery Scopes', function () {
    beforeEach(function () {
        // Add gallery metadata to some themes
        Theme::where('slug', 'paris')->update([
            'is_gallery' => true,
            'category' => 'elegant',
        ]);
        Theme::where('slug', 'tokyo')->update([
            'is_gallery' => true,
            'category' => 'vibrant',
        ]);
        Theme::where('slug', 'sydney')->update([
            'is_gallery' => true,
            'category' => 'professional',
        ]);
    });

    it('scopes to only gallery themes', function () {
        $galleryThemes = Theme::gallery()->get();

        expect($galleryThemes)->toHaveCount(3);
        expect($galleryThemes->every(fn ($t) => $t->is_gallery))->toBeTrue();
    });

    it('filters by category', function () {
        $elegantThemes = Theme::gallery()->category('elegant')->get();

        expect($elegantThemes)->toHaveCount(1);
        expect($elegantThemes->first()->slug)->toBe('paris');
    });

    it('searches by name and description', function () {
        Theme::where('slug', 'paris')->update([
            'description' => 'Elegant Parisian design with gold accents.',
        ]);

        $results = Theme::gallery()->search('gold')->get();

        expect($results)->toHaveCount(1);
        expect($results->first()->slug)->toBe('paris');
    });

    it('includes favourite status for user', function () {
        $theme = Theme::where('slug', 'paris')->first();

        ThemeFavourite::create([
            'user_id' => $this->user->id,
            'theme_id' => $theme->id,
        ]);

        $themes = Theme::gallery()
            ->withFavouriteStatus($this->user)
            ->get();

        $paris = $themes->firstWhere('slug', 'paris');
        $tokyo = $themes->firstWhere('slug', 'tokyo');

        expect($paris->is_favourited)->toBeTruthy();
        expect($tokyo->is_favourited)->toBeFalsy();
    });

    it('handles null user for favourite status gracefully', function () {
        $themes = Theme::gallery()
            ->withFavouriteStatus(null)
            ->get();

        expect($themes)->not->toBeEmpty();
    });
});

describe('BioLinkTheme Model Gallery Methods', function () {
    it('checks if theme is favourited by user', function () {
        $theme = Theme::first();

        expect($theme->isFavouritedBy($this->user))->toBeFalse();

        ThemeFavourite::create([
            'user_id' => $this->user->id,
            'theme_id' => $theme->id,
        ]);

        expect($theme->isFavouritedBy($this->user))->toBeTrue();
    });

    it('returns false for null user favourite check', function () {
        $theme = Theme::first();

        expect($theme->isFavouritedBy(null))->toBeFalse();
    });

    it('has favouritedBy relationship', function () {
        $theme = Theme::first();

        ThemeFavourite::create([
            'user_id' => $this->user->id,
            'theme_id' => $theme->id,
        ]);

        expect($theme->favouritedBy)->toHaveCount(1);
        expect($theme->favouritedBy->first()->id)->toBe($this->user->id);
    });

    it('provides list of theme categories', function () {
        $categories = Theme::getCategories();

        expect($categories)->toBeArray()
            ->toHaveKey('professional')
            ->toHaveKey('creative')
            ->toHaveKey('minimal')
            ->toHaveKey('bold')
            ->toHaveKey('elegant');
    });
});

describe('BioThemeGallerySeeder', function () {
    it('updates existing themes with gallery metadata', function () {
        // Run gallery seeder
        (new BioThemeGallerySeeder)->run();

        $paris = Theme::where('slug', 'paris')->first();

        expect($paris->is_gallery)->toBeTrue()
            ->and($paris->category)->toBe('elegant')
            ->and($paris->description)->toContain('Parisian');
    });

    it('seeds 30 total themes', function () {
        (new BioThemeGallerySeeder)->run();

        $totalThemes = Theme::system()->count();

        expect($totalThemes)->toBe(30);
    });

    it('adds 15 new themes beyond existing 15', function () {
        expect(Theme::system()->count())->toBe(15);

        (new BioThemeGallerySeeder)->run();

        expect(Theme::system()->count())->toBe(30);

        // Check new themes exist
        expect(Theme::where('slug', 'stockholm')->exists())->toBeTrue();
        expect(Theme::where('slug', 'monaco')->exists())->toBeTrue();
        expect(Theme::where('slug', 'vancouver')->exists())->toBeTrue();
    });

    it('assigns categories to all themes', function () {
        (new BioThemeGallerySeeder)->run();

        $themes = Theme::gallery()->get();

        expect($themes->every(fn ($t) => $t->category !== null))->toBeTrue();
    });

    it('marks some new themes as premium', function () {
        (new BioThemeGallerySeeder)->run();

        $premiumNewThemes = Theme::where('is_premium', true)
            ->whereIn('slug', ['marrakech', 'copenhagen', 'santorini', 'monaco'])
            ->count();

        expect($premiumNewThemes)->toBeGreaterThan(0);
    });

    it('is idempotent', function () {
        (new BioThemeGallerySeeder)->run();
        (new BioThemeGallerySeeder)->run();

        expect(Theme::system()->count())->toBe(30);
    });
});

describe('ThemePreviewGenerator Service', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('generates preview image for a theme', function () {
        $generator = app(ThemePreviewGenerator::class);
        $theme = Theme::first();

        $url = $generator->generate($theme);

        expect($url)->toBeString();
        expect(Storage::disk('public')->exists("theme-previews/{$theme->slug}.jpg"))->toBeTrue();
    });

    it('checks if preview exists for theme', function () {
        $generator = app(ThemePreviewGenerator::class);
        $theme = Theme::first();

        expect($generator->exists($theme))->toBeFalse();

        $url = $generator->generate($theme);
        $theme->update(['preview_image' => $url]);

        expect($generator->exists($theme))->toBeTrue();
    });

    it('deletes preview image', function () {
        $generator = app(ThemePreviewGenerator::class);
        $theme = Theme::first();

        $url = $generator->generate($theme);
        $theme->update(['preview_image' => $url]);

        expect($generator->exists($theme))->toBeTrue();

        $generator->delete($theme);

        expect($generator->exists($theme))->toBeFalse();
    });

    it('generates previews for all gallery themes', function () {
        (new BioThemeGallerySeeder)->run();

        $generator = app(ThemePreviewGenerator::class);
        $count = $generator->generateAll();

        $galleryThemes = Theme::gallery()->active()->count();

        expect($count)->toBe($galleryThemes);
    });

    it('handles generation errors gracefully', function () {
        // Create a theme with invalid settings that might cause issues
        $theme = Theme::create([
            'name' => 'Invalid Theme',
            'settings' => [], // Empty settings
            'is_gallery' => true,
        ]);

        $generator = app(ThemePreviewGenerator::class);

        // Should not throw exception
        expect(fn () => $generator->generate($theme))->not->toThrow(\Exception::class);
    });
});

describe('Premium Theme Entitlement', function () {
    beforeEach(function () {
        // Create premium themes entitlement feature
        Feature::create([
            'code' => 'bio.themes.premium',
            'name' => 'Premium Themes',
            'description' => 'Access to premium biolink themes',
            'category' => 'biolink',
            'type' => Feature::TYPE_BOOLEAN,
            'reset_type' => Feature::RESET_NONE,
        ]);
    });

    it('checks premium access with entitlement', function () {
        $service = app(ThemeService::class);

        // Without entitlement
        expect($service->hasPremiumAccess($this->workspace))->toBeFalse();

        // Create package with premium themes
        $package = Package::create([
            'code' => 'pro',
            'name' => 'Pro Plan',
            'is_base_package' => true,
        ]);

        $package->features()->attach(
            Feature::where('code', 'bio.themes.premium')->first()->id,
            ['limit_value' => 1]
        );

        // Provision to workspace
        app(EntitlementService::class)->provisionPackage($this->workspace, 'pro');

        // Now should have access
        expect($service->hasPremiumAccess($this->workspace))->toBeTrue();
    });

    it('blocks applying premium theme without entitlement', function () {
        $theme = Theme::create([
            'name' => 'Premium Theme',
            'settings' => Theme::getDefaultSettings(),
            'is_premium' => true,
        ]);

        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'premium-test',
        ]);

        $service = app(ThemeService::class);
        $result = $service->applyTheme($biolink, $theme->id);

        expect($result)->toBeFalse();
        expect($biolink->fresh()->theme_id)->toBeNull();
    });

    it('allows applying premium theme with entitlement', function () {
        // Create package with premium themes
        $package = Package::create([
            'code' => 'pro',
            'name' => 'Pro Plan',
            'is_base_package' => true,
        ]);

        $package->features()->attach(
            Feature::where('code', 'bio.themes.premium')->first()->id,
            ['limit_value' => 1]
        );

        app(EntitlementService::class)->provisionPackage($this->workspace, 'pro');

        $theme = Theme::create([
            'name' => 'Premium Theme',
            'settings' => Theme::getDefaultSettings(),
            'is_premium' => true,
        ]);

        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'premium-allowed',
        ]);

        $service = app(ThemeService::class);
        $result = $service->applyTheme($biolink, $theme->id);

        expect($result)->toBeTrue();
        expect($biolink->fresh()->theme_id)->toBe($theme->id);
    });
});

describe('Public Theme Gallery Route', function () {
    it('is accessible without authentication', function () {
        $response = $this->get('/themes');

        $response->assertStatus(200);
    });
});
