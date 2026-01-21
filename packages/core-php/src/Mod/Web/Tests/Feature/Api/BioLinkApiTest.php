<?php

use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;
use Core\Mod\Tenant\Models\Feature;
use Core\Mod\Tenant\Models\Package;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();

    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();

    // Set up workspaces for both users
    $this->workspace = Workspace::factory()->create(['name' => 'User Workspace']);
    $this->otherWorkspace = Workspace::factory()->create(['name' => 'Other Workspace']);

    $this->user->hostWorkspaces()->attach($this->workspace, ['role' => 'owner', 'is_default' => true]);
    $this->otherUser->hostWorkspaces()->attach($this->otherWorkspace, ['role' => 'owner', 'is_default' => true]);

    // Set up entitlement features for BioHost
    $biolinkPagesFeature = Feature::create([
        'code' => 'bio.pages',
        'name' => 'Bio Pages',
        'description' => 'Number of biolink pages allowed',
        'category' => 'biolink',
        'type' => Feature::TYPE_LIMIT,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $shortlinksFeature = Feature::create([
        'code' => 'bio.shortlinks',
        'name' => 'Short Links',
        'description' => 'Number of short links allowed',
        'category' => 'biolink',
        'type' => Feature::TYPE_LIMIT,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $analyticsFeature = Feature::create([
        'code' => 'bio.analytics_days',
        'name' => 'Analytics Retention',
        'description' => 'Days of analytics history',
        'category' => 'biolink',
        'type' => Feature::TYPE_LIMIT,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 3,
    ]);

    $analyticsAccessFeature = Feature::create([
        'code' => 'bio.analytics',
        'name' => 'Analytics Access',
        'description' => 'Access to biolink analytics',
        'category' => 'biolink',
        'type' => Feature::TYPE_BOOLEAN,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 4,
    ]);

    // Create package with generous limits for testing
    $package = Package::create([
        'code' => 'biolink-test',
        'name' => 'BioLink Test',
        'description' => 'Test package for API tests',
        'is_stackable' => false,
        'is_base_package' => true,
        'is_active' => true,
        'is_public' => true,
        'sort_order' => 1,
    ]);

    $package->features()->attach($biolinkPagesFeature->id, ['limit_value' => 100]);
    $package->features()->attach($shortlinksFeature->id, ['limit_value' => 100]);
    $package->features()->attach($analyticsFeature->id, ['limit_value' => 90]);
    $package->features()->attach($analyticsAccessFeature->id, ['limit_value' => null]); // Boolean feature

    // Provision packages for both workspaces
    $entitlementService = app(EntitlementService::class);
    $entitlementService->provisionPackage($this->workspace, 'biolink-test');
    $entitlementService->provisionPackage($this->otherWorkspace, 'biolink-test');
});

// ─────────────────────────────────────────────────────────────────────────────
// BioLinks CRUD API
// ─────────────────────────────────────────────────────────────────────────────

describe('BioLinks CRUD API', function () {
    it('requires authentication', function () {
        $this->getJson('/api/v1/bio')
            ->assertStatus(401);
    });

    it('can list user biolinks', function () {
        $biolink = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'url' => 'test-link',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/bio')
            ->assertOk()
            ->assertJsonPath('data.0.url', 'test-link');
    });

    it('does not return other users biolinks', function () {
        Page::factory()->create([
            'workspace_id' => $this->otherWorkspace->id,
            'user_id' => $this->otherUser->id,
            'url' => 'other-link',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/bio')
            ->assertOk()
            ->assertJsonMissing(['url' => 'other-link']);
    });

    it('can filter biolinks by type', function () {
        Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'bio-page',
        ]);

        Page::factory()->shortLink()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'url' => 'short-link',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/bio?type=link')
            ->assertOk()
            ->assertJsonPath('data.0.url', 'short-link')
            ->assertJsonMissing(['url' => 'bio-page']);
    });

    it('can search biolinks by URL', function () {
        Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'url' => 'my-awesome-bio',
        ]);

        Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'url' => 'other-page',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/bio?search=awesome')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.url', 'my-awesome-bio');
    });

    it('can create a biolink', function () {
        $this->actingAs($this->user)
            ->postJson('/api/v1/bio', [
                'url' => 'new-bio-page',
                'type' => 'biolink',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.url', 'new-bio-page');

        $this->assertDatabaseHas('biolinks', [
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'url' => 'new-bio-page',
        ]);
    });

    it('validates URL format on create', function () {
        $this->actingAs($this->user)
            ->postJson('/api/v1/bio', [
                'url' => 'invalid url with spaces',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    });

    it('prevents duplicate URLs', function () {
        Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'url' => 'taken-url',
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/v1/bio', [
                'url' => 'taken-url',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.url.0', 'This URL is already taken.');
    });

    it('can show a biolink with blocks', function () {
        $biolink = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        Block::factory()->create([
            'workspace_id' => $this->workspace->id,
            'biolink_id' => $biolink->id,
            'type' => 'link',
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$biolink->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $biolink->id)
            ->assertJsonCount(1, 'data.blocks');
    });

    it('returns 404 for other users biolink', function () {
        $biolink = Page::factory()->create([
            'workspace_id' => $this->otherWorkspace->id,
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$biolink->id}")
            ->assertStatus(404);
    });

    it('can update a biolink', function () {
        $biolink = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'url' => 'original-url',
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/bio/{$biolink->id}", [
                'url' => 'updated-url',
                'is_enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.url', 'updated-url')
            ->assertJsonPath('data.is_enabled', false);
    });

    it('can delete a biolink', function () {
        $biolink = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/bio/{$biolink->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('biolinks', ['id' => $biolink->id]);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// BioLink Blocks API
// ─────────────────────────────────────────────────────────────────────────────

describe('BioLink Blocks API', function () {
    beforeEach(function () {
        $this->biolink = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);
    });

    it('can list blocks for a biolink', function () {
        Block::factory()->create([
            'workspace_id' => $this->workspace->id,
            'biolink_id' => $this->biolink->id,
            'type' => 'link',
            'order' => 0,
        ]);

        Block::factory()->create([
            'workspace_id' => $this->workspace->id,
            'biolink_id' => $this->biolink->id,
            'type' => 'heading',
            'order' => 1,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$this->biolink->id}/blocks")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    });

    it('can create a block', function () {
        $this->actingAs($this->user)
            ->postJson("/api/v1/bio/{$this->biolink->id}/blocks", [
                'type' => 'link',
                'location_url' => 'https://example.com',
                'settings' => ['name' => 'Visit Example'],
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'link')
            ->assertJsonPath('data.location_url', 'https://example.com');

        $this->assertDatabaseHas('biolink_blocks', [
            'biolink_id' => $this->biolink->id,
            'type' => 'link',
        ]);
    });

    it('validates block type exists', function () {
        $this->actingAs($this->user)
            ->postJson("/api/v1/bio/{$this->biolink->id}/blocks", [
                'type' => 'nonexistent_type',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.type.0', 'Invalid block type.');
    });

    it('can update a block', function () {
        $block = Block::factory()->create([
            'workspace_id' => $this->workspace->id,
            'biolink_id' => $this->biolink->id,
            'is_enabled' => true,
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/blocks/{$block->id}", [
                'is_enabled' => false,
                'location_url' => 'https://updated.com',
            ])
            ->assertOk()
            ->assertJsonPath('data.is_enabled', false)
            ->assertJsonPath('data.location_url', 'https://updated.com');
    });

    it('can delete a block', function () {
        $block = Block::factory()->create([
            'workspace_id' => $this->workspace->id,
            'biolink_id' => $this->biolink->id,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/blocks/{$block->id}")
            ->assertStatus(204);

        $this->assertDatabaseMissing('biolink_blocks', ['id' => $block->id]);
    });

    it('can reorder blocks', function () {
        $block1 = Block::factory()->create([
            'workspace_id' => $this->workspace->id,
            'biolink_id' => $this->biolink->id,
            'order' => 0,
        ]);

        $block2 = Block::factory()->create([
            'workspace_id' => $this->workspace->id,
            'biolink_id' => $this->biolink->id,
            'order' => 1,
        ]);

        $block3 = Block::factory()->create([
            'workspace_id' => $this->workspace->id,
            'biolink_id' => $this->biolink->id,
            'order' => 2,
        ]);

        // Reorder: 3, 1, 2
        $this->actingAs($this->user)
            ->postJson("/api/v1/bio/{$this->biolink->id}/blocks/reorder", [
                'order' => [$block3->id, $block1->id, $block2->id],
            ])
            ->assertOk();

        $this->assertEquals(0, $block3->fresh()->order);
        $this->assertEquals(1, $block1->fresh()->order);
        $this->assertEquals(2, $block2->fresh()->order);
    });

    it('validates block IDs belong to biolink on reorder', function () {
        $otherBiolink = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $otherBlock = Block::factory()->create([
            'workspace_id' => $this->workspace->id,
            'biolink_id' => $otherBiolink->id,
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/bio/{$this->biolink->id}/blocks/reorder", [
                'order' => [$otherBlock->id],
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.order.0', 'One or more blocks do not belong to this bio.');
    });

    it('can duplicate a block', function () {
        $block = Block::factory()->create([
            'workspace_id' => $this->workspace->id,
            'biolink_id' => $this->biolink->id,
            'type' => 'link',
            'order' => 0,
            'clicks' => 100,
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/v1/bio/{$this->biolink->id}/blocks/{$block->id}/duplicate")
            ->assertSuccessful()
            ->assertJsonPath('data.type', 'link')
            ->assertJsonPath('data.order', 1)
            ->assertJsonPath('data.clicks', 0); // Clicks should reset

        $this->assertDatabaseCount('biolink_blocks', 2);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// BioLink Analytics API
// ─────────────────────────────────────────────────────────────────────────────

describe('BioLink Analytics API', function () {
    beforeEach(function () {
        $this->biolink = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'clicks' => 500,
            'unique_clicks' => 300,
        ]);
    });

    it('can get analytics summary', function () {
        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$this->biolink->id}/analytics")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'biolink_id',
                    'period',
                    'date_range' => ['start', 'end', 'limited', 'max_days'],
                    'summary',
                ],
            ]);
    });

    it('can get clicks over time', function () {
        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$this->biolink->id}/analytics/clicks?period=7d")
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'period',
                'start',
                'end',
            ]);
    });

    it('can get geographic breakdown', function () {
        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$this->biolink->id}/analytics/geo")
            ->assertOk()
            ->assertJsonStructure(['data', 'period', 'start', 'end']);
    });

    it('can get device breakdown', function () {
        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$this->biolink->id}/analytics/devices")
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['devices', 'browsers', 'operating_systems'],
                'period',
                'start',
                'end',
            ]);
    });

    it('can get referrer breakdown', function () {
        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$this->biolink->id}/analytics/referrers")
            ->assertOk()
            ->assertJsonStructure(['data', 'period', 'start', 'end']);
    });

    it('can get UTM breakdown', function () {
        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$this->biolink->id}/analytics/utm")
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['sources', 'campaigns'],
                'period',
                'start',
                'end',
            ]);
    });

    it('returns 404 for other users biolink analytics', function () {
        $otherBiolink = Page::factory()->create([
            'workspace_id' => $this->otherWorkspace->id,
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$otherBiolink->id}/analytics")
            ->assertStatus(404);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Short Links API
// ─────────────────────────────────────────────────────────────────────────────

describe('Short Links API', function () {
    it('can list short links (only type=link)', function () {
        Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'bio-page',
        ]);

        Page::factory()->shortLink()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'url' => 'short-link',
        ]);

        $this->actingAs($this->user)
            ->getJson('/api/v1/shortlinks')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.url', 'short-link');
    });

    it('can create a short link', function () {
        $this->actingAs($this->user)
            ->postJson('/api/v1/shortlinks', [
                'destination_url' => 'https://example.com/long-url',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.destination_url', 'https://example.com/long-url');

        $this->assertDatabaseHas('biolinks', [
            'workspace_id' => $this->workspace->id,
            'type' => 'link',
            'location_url' => 'https://example.com/long-url',
        ]);
    });

    it('can create a short link with custom URL', function () {
        $this->actingAs($this->user)
            ->postJson('/api/v1/shortlinks', [
                'url' => 'my-custom-link',
                'destination_url' => 'https://example.com',
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.url', 'my-custom-link');
    });

    it('requires destination URL', function () {
        $this->actingAs($this->user)
            ->postJson('/api/v1/shortlinks', [
                'url' => 'my-link',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['destination_url']);
    });

    it('can update a short link', function () {
        $shortlink = Page::factory()->shortLink()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->putJson("/api/v1/shortlinks/{$shortlink->id}", [
                'destination_url' => 'https://new-destination.com',
                'is_enabled' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_enabled', false);

        $this->assertDatabaseHas('biolinks', [
            'id' => $shortlink->id,
            'location_url' => 'https://new-destination.com',
        ]);
    });

    it('can delete a short link', function () {
        $shortlink = Page::factory()->shortLink()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/shortlinks/{$shortlink->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('biolinks', ['id' => $shortlink->id]);
    });

    it('returns 404 when accessing biolink page via shortlinks endpoint', function () {
        $biolink = Page::factory()->create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/shortlinks/{$biolink->id}")
            ->assertStatus(404);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Workspace Isolation
// ─────────────────────────────────────────────────────────────────────────────

describe('Workspace Isolation', function () {
    it('prevents accessing biolinks from other workspaces', function () {
        $otherBiolink = Page::factory()->create([
            'workspace_id' => $this->otherWorkspace->id,
            'user_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/bio/{$otherBiolink->id}")
            ->assertStatus(404);

        $this->actingAs($this->user)
            ->putJson("/api/v1/bio/{$otherBiolink->id}", ['url' => 'hacked'])
            ->assertStatus(404);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/bio/{$otherBiolink->id}")
            ->assertStatus(404);
    });

    it('prevents accessing blocks from other workspaces', function () {
        $otherBiolink = Page::factory()->create([
            'workspace_id' => $this->otherWorkspace->id,
            'user_id' => $this->otherUser->id,
        ]);

        $otherBlock = Block::factory()->create([
            'workspace_id' => $this->otherWorkspace->id,
            'biolink_id' => $otherBiolink->id,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/v1/blocks/{$otherBlock->id}")
            ->assertStatus(403);

        $this->actingAs($this->user)
            ->putJson("/api/v1/blocks/{$otherBlock->id}", ['is_enabled' => false])
            ->assertStatus(403);

        $this->actingAs($this->user)
            ->deleteJson("/api/v1/blocks/{$otherBlock->id}")
            ->assertStatus(403);
    });
});
