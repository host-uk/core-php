<?php

declare(strict_types=1);

use Core\Mod\Web\Database\Seeders\BioThemeSeeder;
use Core\Mod\Web\Models\Click;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Project;
use Core\Mod\Web\Models\Theme;
use Core\Mod\Web\View\Modal\Admin\AnalyticsOverview;
use Core\Mod\Web\View\Modal\Admin\ShortLinkIndex;
use Core\Mod\Web\View\Modal\Admin\ThemeGallery;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
});

describe('Route Resolution', function () {
    it('resolves biolink index route', function () {
        expect(route('hub.bio.index'))->toContain('/bio');
    });

    it('resolves shortlinks route', function () {
        expect(route('hub.bio.shortlinks'))->toContain('/bio/shortlinks');
    });

    it('resolves themes route', function () {
        expect(route('hub.bio.themes'))->toContain('/bio/themes');
    });

    it('resolves analytics overview route', function () {
        expect(route('hub.bio.analytics.overview'))->toContain('/bio/analytics');
    });

    it('resolves domains route', function () {
        expect(route('hub.bio.domains'))->toContain('/bio/domains');
    });

    it('resolves projects route', function () {
        expect(route('hub.bio.projects'))->toContain('/bio/projects');
    });

    it('resolves pixels route', function () {
        expect(route('hub.bio.pixels'))->toContain('/bio/pixels');
    });

    it('resolves shortlink create route', function () {
        expect(route('hub.bio.shortlink.create'))->toContain('/bio/short-link/create');
    });

    it('resolves biolink edit route', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test-route',
        ]);

        expect(route('hub.bio.edit', $biolink->id))->toContain('/bio/'.$biolink->id.'/edit');
    });

    it('resolves biolink analytics route', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test-analytics',
        ]);

        expect(route('hub.bio.analytics', $biolink->id))->toContain('/bio/'.$biolink->id.'/analytics');
    });
});

describe('ShortLinkIndex Component', function () {
    it('renders successfully when authenticated', function () {
        Livewire::actingAs($this->user)
            ->test(ShortLinkIndex::class)
            ->assertStatus(200);
    });

    it('only shows links with type=link', function () {
        // Create a biolink page (should not appear)
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'my-bio-page',
        ]);

        // Create short links (should appear)
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'short-1',
            'location_url' => 'https://example.com/1',
        ]);

        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'short-2',
            'location_url' => 'https://example.com/2',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShortLinkIndex::class);

        $shortLinks = $component->instance()->shortLinks;

        expect($shortLinks)->toHaveCount(2);
        expect($shortLinks->pluck('type')->unique()->toArray())->toBe(['link']);
    });

    it('filters links by search query', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'github-link',
            'location_url' => 'https://github.com/test',
        ]);

        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'twitter-link',
            'location_url' => 'https://twitter.com/test',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShortLinkIndex::class)
            ->set('search', 'github');

        $shortLinks = $component->instance()->shortLinks;

        expect($shortLinks)->toHaveCount(1);
        expect($shortLinks->first()->url)->toBe('github-link');
    });

    it('filters links by project', function () {
        $project = Project::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'name' => 'Test Project',
        ]);

        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'project-link',
            'project_id' => $project->id,
            'location_url' => 'https://example.com',
        ]);

        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'no-project-link',
            'location_url' => 'https://example.com',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShortLinkIndex::class)
            ->set('project', $project->id);

        $shortLinks = $component->instance()->shortLinks;

        expect($shortLinks)->toHaveCount(1);
        expect($shortLinks->first()->url)->toBe('project-link');
    });

    it('calculates stats correctly', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'active-link',
            'location_url' => 'https://example.com',
            'is_enabled' => true,
            'clicks' => 50,
        ]);

        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'disabled-link',
            'location_url' => 'https://example.com',
            'is_enabled' => false,
            'clicks' => 30,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ShortLinkIndex::class);

        $stats = $component->instance()->stats;

        expect($stats['total'])->toBe(2);
        expect($stats['enabled'])->toBe(1);
        expect((int) $stats['clicks'])->toBe(80);
    });
});

describe('ThemeGallery Component', function () {
    beforeEach(function () {
        (new BioThemeSeeder)->run();
    });

    it('renders successfully when authenticated', function () {
        Livewire::actingAs($this->user)
            ->test(ThemeGallery::class)
            ->assertStatus(200);
    });

    it('displays system themes', function () {
        $component = Livewire::actingAs($this->user)
            ->test(ThemeGallery::class);

        $themes = $component->instance()->themes;

        expect($themes)->not->toBeEmpty();
        expect($themes->where('is_system', true)->count())->toBeGreaterThan(0);
    });

    it('filters themes by type', function () {
        // Create a custom theme
        Theme::create([
            'user_id' => $this->user->id,
            'name' => 'My Custom Theme',
            'settings' => Theme::getDefaultSettings(),
            'is_system' => false,
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ThemeGallery::class)
            ->set('filter', 'custom');

        $themes = $component->instance()->themes;

        expect($themes)->toHaveCount(1);
        expect($themes->first()->name)->toBe('My Custom Theme');
    });

    it('filters premium themes', function () {
        $component = Livewire::actingAs($this->user)
            ->test(ThemeGallery::class)
            ->set('filter', 'premium');

        $themes = $component->instance()->themes;

        expect($themes->where('is_premium', true)->count())->toBe($themes->count());
    });

    it('lists user biolinks for applying themes', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'apply-theme-test',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(ThemeGallery::class);

        $biolinks = $component->instance()->biolinks;

        expect($biolinks)->toHaveCount(1);
        expect($biolinks->first()->url)->toBe('apply-theme-test');
    });
});

describe('AnalyticsOverview Component', function () {
    it('renders successfully when authenticated', function () {
        Livewire::actingAs($this->user)
            ->test(AnalyticsOverview::class)
            ->assertStatus(200);
    });

    it('returns zero stats when no biolinks exist', function () {
        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsOverview::class);

        $stats = $component->instance()->totalStats;

        expect($stats['clicks'])->toBe(0);
        expect($stats['unique_clicks'])->toBe(0);
        expect($stats['biolinks'])->toBe(0);
    });

    it('aggregates clicks across all biolinks', function () {
        $biolink1 = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'bio-1',
            'clicks' => 100,
        ]);

        $biolink2 = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'bio-2',
            'clicks' => 50,
        ]);

        // Add click records
        Click::create([
            'biolink_id' => $biolink1->id,
            'ip_hash' => hash('sha256', '192.168.1.1'),
            'country_code' => 'GB',
            'device_type' => 'mobile',
            'created_at' => now(),
        ]);

        Click::create([
            'biolink_id' => $biolink2->id,
            'ip_hash' => hash('sha256', '192.168.1.2'),
            'country_code' => 'US',
            'device_type' => 'desktop',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsOverview::class);

        $stats = $component->instance()->totalStats;

        expect($stats['biolinks'])->toBe(2);
        expect($stats['clicks'])->toBeGreaterThanOrEqual(2);
    });

    it('shows top performing biolinks', function () {
        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'popular-bio',
            'clicks' => 500,
        ]);

        Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'less-popular',
            'clicks' => 50,
        ]);

        // Add click records for the period
        Click::create([
            'biolink_id' => Page::where('url', 'popular-bio')->first()->id,
            'ip_hash' => hash('sha256', '192.168.1.1'),
            'country_code' => 'GB',
            'device_type' => 'mobile',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsOverview::class);

        $topBiolinks = $component->instance()->topBiolinks;

        expect($topBiolinks)->not->toBeEmpty();
    });

    it('aggregates country data', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'country-test',
        ]);

        Click::create([
            'biolink_id' => $biolink->id,
            'ip_hash' => hash('sha256', '192.168.1.1'),
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'device_type' => 'mobile',
            'created_at' => now(),
        ]);

        Click::create([
            'biolink_id' => $biolink->id,
            'ip_hash' => hash('sha256', '192.168.1.2'),
            'country_code' => 'US',
            'country_name' => 'United States',
            'device_type' => 'desktop',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsOverview::class);

        $countries = $component->instance()->countries;

        expect($countries)->toHaveCount(2);
    });

    it('aggregates device data', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'device-test',
        ]);

        Click::create([
            'biolink_id' => $biolink->id,
            'ip_hash' => hash('sha256', '192.168.1.1'),
            'country_code' => 'GB',
            'device_type' => 'mobile',
            'created_at' => now(),
        ]);

        Click::create([
            'biolink_id' => $biolink->id,
            'ip_hash' => hash('sha256', '192.168.1.2'),
            'country_code' => 'GB',
            'device_type' => 'desktop',
            'created_at' => now(),
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsOverview::class);

        $devices = $component->instance()->devices;

        expect($devices)->toHaveCount(2);
    });

    it('supports period selection', function () {
        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsOverview::class)
            ->set('period', '30d');

        expect($component->instance()->period)->toBe('30d');
    });

    it('has available periods with upgrade flags', function () {
        $component = Livewire::actingAs($this->user)
            ->test(AnalyticsOverview::class);

        $periods = $component->instance()->availablePeriods;

        expect($periods)->toHaveKey('7d');
        expect($periods)->toHaveKey('30d');
        expect($periods['7d'])->toHaveKey('label');
        expect($periods['7d'])->toHaveKey('available');
    });
});

describe('Sidebar Navigation', function () {
    it('renders biohost submenu items in sidebar', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.index'));

        $response->assertStatus(200);
    });

    it('can access short links page', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.shortlinks'));

        $response->assertStatus(200);
    });

    it('can access themes page', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.themes'));

        $response->assertStatus(200);
    });

    it('can access analytics overview page', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.analytics.overview'));

        $response->assertStatus(200);
    });

    it('can access domains page', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.domains'));

        $response->assertStatus(200);
    });

    it('can access projects page', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.projects'));

        $response->assertStatus(200);
    });

    it('can access pixels page', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.pixels'));

        $response->assertStatus(200);
    });
});

describe('Editor Page', function () {
    it('can access editor page for biolink', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'editor-test',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.edit', $biolink->id));

        $response->assertStatus(200);
    });

    it('can access editor page for biolink with blocks', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'editor-blocks-test',
        ]);

        // Add some blocks to test preview rendering
        $biolink->blocks()->createMany([
            [
                'workspace_id' => $this->workspace->id,
                'type' => 'link',
                'settings' => ['name' => 'Test Link', 'url' => 'https://example.com'],
                'order' => 1,
            ],
            [
                'workspace_id' => $this->workspace->id,
                'type' => 'heading',
                'settings' => ['text' => 'Test Heading'],
                'order' => 2,
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.edit', $biolink->id));

        $response->assertStatus(200);
    });
});

describe('Editor Quick Actions', function () {
    it('can access submissions page from editor', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'submissions-test',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.submissions', $biolink->id));

        $response->assertStatus(200);
    });

    it('can access notifications page from editor', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'notifications-test',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.notifications', $biolink->id));

        $response->assertStatus(200);
    });
});
