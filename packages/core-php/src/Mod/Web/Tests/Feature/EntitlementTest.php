<?php

use Core\Mod\Web\Actions\CreateBiolink;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\AnalyticsService;
use Core\Mod\Tenant\Exceptions\EntitlementException;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;
use Core\Mod\Tenant\Models\Feature;
use Core\Mod\Tenant\Models\Package;
use Core\Mod\Tenant\Models\UsageRecord;
use Illuminate\Support\Facades\Cache;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    // Create test user and workspace
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);

    // Create BioHost features
    $this->biolinkPagesFeature = Feature::create([
        'code' => 'bio.pages',
        'name' => 'Bio Pages',
        'description' => 'Number of biolink pages allowed',
        'category' => 'biolink',
        'type' => Feature::TYPE_LIMIT,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->shortlinksFeature = Feature::create([
        'code' => 'bio.shortlinks',
        'name' => 'Short Links',
        'description' => 'Number of short links allowed',
        'category' => 'biolink',
        'type' => Feature::TYPE_LIMIT,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 2,
    ]);

    $this->analyticsFeature = Feature::create([
        'code' => 'bio.analytics_days',
        'name' => 'Analytics Retention',
        'description' => 'Days of analytics history',
        'category' => 'biolink',
        'type' => Feature::TYPE_LIMIT,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 3,
    ]);

    $this->proTierFeature = Feature::create([
        'code' => 'bio.tier.pro',
        'name' => 'Pro Block Types',
        'description' => 'Access to pro blocks',
        'category' => 'biolink',
        'type' => Feature::TYPE_BOOLEAN,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 4,
    ]);

    $this->ultimateTierFeature = Feature::create([
        'code' => 'bio.tier.ultimate',
        'name' => 'Ultimate Block Types',
        'description' => 'Access to ultimate blocks',
        'category' => 'biolink',
        'type' => Feature::TYPE_BOOLEAN,
        'reset_type' => Feature::RESET_NONE,
        'is_active' => true,
        'sort_order' => 5,
    ]);

    // Create packages with different limits
    $this->creatorPackage = Package::create([
        'code' => 'creator',
        'name' => 'Creator',
        'description' => 'For individual creators',
        'is_stackable' => false,
        'is_base_package' => true,
        'is_active' => true,
        'is_public' => true,
        'sort_order' => 1,
    ]);

    $this->agencyPackage = Package::create([
        'code' => 'agency',
        'name' => 'Agency',
        'description' => 'For agencies',
        'is_stackable' => false,
        'is_base_package' => true,
        'is_active' => true,
        'is_public' => true,
        'sort_order' => 2,
    ]);

    // Attach features to creator package (limited)
    $this->creatorPackage->features()->attach($this->biolinkPagesFeature->id, ['limit_value' => 3]);
    $this->creatorPackage->features()->attach($this->shortlinksFeature->id, ['limit_value' => 10]);
    $this->creatorPackage->features()->attach($this->analyticsFeature->id, ['limit_value' => 30]);
    // Creator does NOT have pro or ultimate tier access

    // Attach features to agency package (higher limits + tiers)
    $this->agencyPackage->features()->attach($this->biolinkPagesFeature->id, ['limit_value' => 25]);
    $this->agencyPackage->features()->attach($this->shortlinksFeature->id, ['limit_value' => 100]);
    $this->agencyPackage->features()->attach($this->analyticsFeature->id, ['limit_value' => 90]);
    $this->agencyPackage->features()->attach($this->proTierFeature->id, ['limit_value' => null]);

    $this->service = app(EntitlementService::class);
    $this->analyticsService = app(AnalyticsService::class);
});

describe('BioHost Entitlements', function () {
    describe('biolink.pages limit', function () {
        it('allows creation when under limit', function () {
            $this->service->provisionPackage($this->workspace, 'creator');

            $result = $this->service->can($this->workspace, 'bio.pages');

            expect($result->isAllowed())->toBeTrue()
                ->and($result->limit)->toBe(3)
                ->and($result->used)->toBe(0);
        });

        it('denies creation when limit reached', function () {
            $this->service->provisionPackage($this->workspace, 'creator');

            // Record usage up to the limit
            for ($i = 0; $i < 3; $i++) {
                UsageRecord::create([
                    'workspace_id' => $this->workspace->id,
                    'feature_code' => 'bio.pages',
                    'quantity' => 1,
                    'recorded_at' => now(),
                ]);
            }

            Cache::flush();
            $result = $this->service->can($this->workspace, 'bio.pages');

            expect($result->isDenied())->toBeTrue()
                ->and($result->used)->toBe(3)
                ->and($result->limit)->toBe(3);
        });

        it('allows more pages on higher tier', function () {
            $this->service->provisionPackage($this->workspace, 'agency');

            $result = $this->service->can($this->workspace, 'bio.pages');

            expect($result->isAllowed())->toBeTrue()
                ->and($result->limit)->toBe(25);
        });
    });

    describe('biolink.shortlinks limit', function () {
        it('allows creation when under limit', function () {
            $this->service->provisionPackage($this->workspace, 'creator');

            $result = $this->service->can($this->workspace, 'bio.shortlinks');

            expect($result->isAllowed())->toBeTrue()
                ->and($result->limit)->toBe(10)
                ->and($result->used)->toBe(0);
        });

        it('denies creation when limit reached', function () {
            $this->service->provisionPackage($this->workspace, 'creator');

            // Record usage up to the limit
            for ($i = 0; $i < 10; $i++) {
                UsageRecord::create([
                    'workspace_id' => $this->workspace->id,
                    'feature_code' => 'bio.shortlinks',
                    'quantity' => 1,
                    'recorded_at' => now(),
                ]);
            }

            Cache::flush();
            $result = $this->service->can($this->workspace, 'bio.shortlinks');

            expect($result->isDenied())->toBeTrue()
                ->and($result->used)->toBe(10)
                ->and($result->limit)->toBe(10);
        });
    });

    describe('biolink.tier.pro access', function () {
        it('denies pro tier on creator package', function () {
            $this->service->provisionPackage($this->workspace, 'creator');

            $result = $this->service->can($this->workspace, 'bio.tier.pro');

            expect($result->isDenied())->toBeTrue();
        });

        it('allows pro tier on agency package', function () {
            $this->service->provisionPackage($this->workspace, 'agency');

            $result = $this->service->can($this->workspace, 'bio.tier.pro');

            expect($result->isAllowed())->toBeTrue();
        });
    });

    describe('biolink.tier.ultimate access', function () {
        it('denies ultimate tier on creator package', function () {
            $this->service->provisionPackage($this->workspace, 'creator');

            $result = $this->service->can($this->workspace, 'bio.tier.ultimate');

            expect($result->isDenied())->toBeTrue();
        });

        it('denies ultimate tier on agency package without feature', function () {
            $this->service->provisionPackage($this->workspace, 'agency');

            $result = $this->service->can($this->workspace, 'bio.tier.ultimate');

            // Agency has pro but not ultimate
            expect($result->isDenied())->toBeTrue();
        });
    });

    describe('biolink.analytics_days limit', function () {
        it('returns correct retention days for creator', function () {
            $this->service->provisionPackage($this->workspace, 'creator');

            $retentionDays = $this->analyticsService->getRetentionDays($this->workspace);

            expect($retentionDays)->toBe(30);
        });

        it('returns correct retention days for agency', function () {
            $this->service->provisionPackage($this->workspace, 'agency');

            $retentionDays = $this->analyticsService->getRetentionDays($this->workspace);

            expect($retentionDays)->toBe(90);
        });

        it('returns default retention when no workspace', function () {
            $retentionDays = $this->analyticsService->getRetentionDays(null);

            expect($retentionDays)->toBe(30);
        });

        it('enforces date retention limits', function () {
            $this->service->provisionPackage($this->workspace, 'creator');

            $start = now()->subDays(60)->startOfDay(); // 60 days ago
            $end = now()->endOfDay();

            $result = $this->analyticsService->enforceDateRetention($start, $end, $this->workspace);

            expect($result['limited'])->toBeTrue()
                ->and($result['max_days'])->toBe(30)
                // The enforced start should be approximately 30 days ago (within 1 day tolerance)
                ->and((int) $result['start']->diffInDays(now()->startOfDay()))->toBeLessThanOrEqual(30);
        });

        it('does not limit dates within retention period', function () {
            $this->service->provisionPackage($this->workspace, 'creator');

            $start = now()->subDays(7)->startOfDay(); // 7 days ago
            $end = now()->endOfDay();

            $result = $this->analyticsService->enforceDateRetention($start, $end, $this->workspace);

            expect($result['limited'])->toBeFalse()
                ->and($result['start']->toDateString())->toBe($start->toDateString());
        });
    });
});

describe('CreateBiolink Action', function () {
    it('creates biolink when under limit', function () {
        $this->service->provisionPackage($this->workspace, 'creator');

        $action = app(CreateBiolink::class);
        $biolink = $action->handle($this->user, ['type' => 'biolink']);

        expect($biolink)->toBeInstanceOf(Page::class)
            ->and($biolink->type)->toBe('biolink');
    });

    it('throws exception when limit reached', function () {
        $this->service->provisionPackage($this->workspace, 'creator');

        // Use up all biolink slots
        for ($i = 0; $i < 3; $i++) {
            UsageRecord::create([
                'workspace_id' => $this->workspace->id,
                'feature_code' => 'bio.pages',
                'quantity' => 1,
                'recorded_at' => now(),
            ]);
        }

        Cache::flush();

        $action = app(CreateBiolink::class);

        expect(fn () => $action->handle($this->user, ['type' => 'biolink']))
            ->toThrow(EntitlementException::class);
    });

    it('uses correct feature code for shortlinks', function () {
        $this->service->provisionPackage($this->workspace, 'creator');

        $action = app(CreateBiolink::class);
        $biolink = $action->handle($this->user, ['type' => 'link', 'location_url' => 'https://example.com']);

        expect($biolink)->toBeInstanceOf(Page::class)
            ->and($biolink->type)->toBe('link');

        // Verify usage was recorded for shortlinks
        $usage = UsageRecord::where('workspace_id', $this->workspace->id)
            ->where('feature_code', 'bio.shortlinks')
            ->count();

        expect($usage)->toBe(1);
    });

    it('records usage after creation', function () {
        $this->service->provisionPackage($this->workspace, 'creator');

        $action = app(CreateBiolink::class);
        $action->handle($this->user, ['type' => 'biolink']);

        $usage = UsageRecord::where('workspace_id', $this->workspace->id)
            ->where('feature_code', 'bio.pages')
            ->count();

        expect($usage)->toBe(1);
    });
});
