<?php

use Core\Mod\Web\Models\Click;
use Core\Mod\Web\Models\ClickStat;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\AnalyticsService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Carbon\Carbon;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);

    $this->biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'analytics-test',
    ]);

    $this->analyticsService = app(AnalyticsService::class);
});

describe('AnalyticsService', function () {
    it('returns empty stats when no clicks exist', function () {
        $start = now()->subDays(7)->startOfDay();
        $end = now()->endOfDay();

        $summary = $this->analyticsService->getSummary($this->biolink, $start, $end);

        expect($summary['clicks'])->toBe(0)
            ->and($summary['unique_clicks'])->toBe(0);
    });

    it('calculates summary stats from raw clicks', function () {
        // Create some raw clicks
        Click::insert([
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash1',
                'country_code' => 'GB',
                'device_type' => 'desktop',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash2',
                'country_code' => 'US',
                'device_type' => 'mobile',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash1',
                'country_code' => 'GB',
                'device_type' => 'desktop',
                'is_unique' => false,
                'created_at' => now()->subHours(2),
            ],
        ]);

        $start = now()->subDays(7)->startOfDay();
        $end = now()->endOfDay();

        $summary = $this->analyticsService->getSummary($this->biolink, $start, $end);

        expect($summary['clicks'])->toBe(3)
            ->and($summary['unique_clicks'])->toBe(2);
    });

    it('groups clicks by country', function () {
        Click::insert([
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash1',
                'country_code' => 'GB',
                'device_type' => 'desktop',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash2',
                'country_code' => 'GB',
                'device_type' => 'mobile',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash3',
                'country_code' => 'US',
                'device_type' => 'desktop',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
        ]);

        $start = now()->subDays(7)->startOfDay();
        $end = now()->endOfDay();

        $countries = $this->analyticsService->getClicksByCountry($this->biolink, $start, $end);

        expect($countries)->toHaveCount(2)
            ->and($countries[0]['country_code'])->toBe('GB')
            ->and($countries[0]['clicks'])->toBe(2)
            ->and($countries[1]['country_code'])->toBe('US')
            ->and($countries[1]['clicks'])->toBe(1);
    });

    it('groups clicks by device type', function () {
        Click::insert([
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash1',
                'device_type' => 'desktop',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash2',
                'device_type' => 'mobile',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash3',
                'device_type' => 'mobile',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
        ]);

        $start = now()->subDays(7)->startOfDay();
        $end = now()->endOfDay();

        $devices = $this->analyticsService->getClicksByDevice($this->biolink, $start, $end);

        expect($devices)->toHaveCount(2);

        $mobile = collect($devices)->firstWhere('device_type', 'mobile');
        $desktop = collect($devices)->firstWhere('device_type', 'desktop');

        expect($mobile['clicks'])->toBe(2)
            ->and($desktop['clicks'])->toBe(1);
    });

    it('groups clicks by referrer', function () {
        Click::insert([
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash1',
                'device_type' => 'desktop',
                'referrer_host' => 'google.com',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash2',
                'device_type' => 'mobile',
                'referrer_host' => 'twitter.com',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash3',
                'device_type' => 'mobile',
                'referrer_host' => null,
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
        ]);

        $start = now()->subDays(7)->startOfDay();
        $end = now()->endOfDay();

        $referrers = $this->analyticsService->getClicksByReferrer($this->biolink, $start, $end);

        // Should include direct visits and the two referrers
        expect($referrers)->toHaveCount(3);

        $direct = collect($referrers)->firstWhere('referrer', 'Direct / None');
        expect($direct['clicks'])->toBe(1);
    });

    it('groups clicks by UTM source', function () {
        Click::insert([
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash1',
                'device_type' => 'desktop',
                'utm_source' => 'newsletter',
                'utm_medium' => 'email',
                'utm_campaign' => 'summer-2024',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash2',
                'device_type' => 'mobile',
                'utm_source' => 'twitter',
                'utm_medium' => 'social',
                'utm_campaign' => 'summer-2024',
                'is_unique' => true,
                'created_at' => now()->subDay(),
            ],
        ]);

        $start = now()->subDays(7)->startOfDay();
        $end = now()->endOfDay();

        $sources = $this->analyticsService->getClicksByUtmSource($this->biolink, $start, $end);

        expect($sources)->toHaveCount(2);
    });

    it('returns clicks over time with filled date gaps', function () {
        Click::insert([
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash1',
                'device_type' => 'desktop',
                'is_unique' => true,
                'created_at' => now()->subDays(3),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash2',
                'device_type' => 'mobile',
                'is_unique' => true,
                'created_at' => now()->subDays(1),
            ],
        ]);

        $start = now()->subDays(7)->startOfDay();
        $end = now()->endOfDay();

        $trend = $this->analyticsService->getClicksOverTime($this->biolink, $start, $end);

        // Should have 8 days worth of data (7 days + today)
        expect($trend['labels'])->toHaveCount(8)
            ->and($trend['clicks'])->toHaveCount(8)
            ->and($trend['unique_clicks'])->toHaveCount(8);

        // Most days should be 0
        expect(array_sum($trend['clicks']))->toBe(2);
    });

    it('converts country codes to names', function () {
        expect($this->analyticsService->getCountryName('GB'))->toBe('United Kingdom')
            ->and($this->analyticsService->getCountryName('US'))->toBe('United States')
            ->and($this->analyticsService->getCountryName('XX'))->toBe('XX')
            ->and($this->analyticsService->getCountryName(null))->toBe('Unknown');
    });

    it('calculates date ranges for periods', function () {
        Carbon::setTestNow('2026-01-03 12:00:00');

        [$start, $end] = $this->analyticsService->getDateRangeForPeriod('7d');

        expect($start->format('Y-m-d'))->toBe('2025-12-27')
            ->and($end->format('Y-m-d'))->toBe('2026-01-03');

        [$start, $end] = $this->analyticsService->getDateRangeForPeriod('30d');

        expect($start->format('Y-m-d'))->toBe('2025-12-04');

        Carbon::setTestNow();
    });
});

describe('AggregateBioClicks Command', function () {
    it('aggregates raw clicks into stats table', function () {
        // Create raw clicks
        Click::insert([
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash1',
                'country_code' => 'GB',
                'device_type' => 'desktop',
                'referrer_host' => 'google.com',
                'utm_source' => 'newsletter',
                'is_unique' => true,
                'created_at' => now()->subHour(),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash2',
                'country_code' => 'GB',
                'device_type' => 'mobile',
                'referrer_host' => 'google.com',
                'utm_source' => null,
                'is_unique' => true,
                'created_at' => now()->subHour(),
            ],
        ]);

        expect(ClickStat::count())->toBe(0);

        $this->artisan('bio:aggregate-clicks', ['--hours' => 2])
            ->assertExitCode(0);

        expect(ClickStat::count())->toBeGreaterThan(0);

        // Check that daily totals were created
        $dailyTotal = ClickStat::where('biolink_id', $this->biolink->id)
            ->whereNull('hour')
            ->whereNull('country_code')
            ->whereNull('device_type')
            ->first();

        expect($dailyTotal)->not->toBeNull()
            ->and($dailyTotal->clicks)->toBe(2)
            ->and($dailyTotal->unique_clicks)->toBe(2);
    });

    it('updates biolink denormalised counters', function () {
        Click::insert([
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash1',
                'device_type' => 'desktop',
                'is_unique' => true,
                'created_at' => now()->subMinutes(30),
            ],
            [
                'biolink_id' => $this->biolink->id,
                'visitor_hash' => 'hash1',
                'device_type' => 'desktop',
                'is_unique' => false,
                'created_at' => now()->subMinutes(20),
            ],
        ]);

        expect($this->biolink->fresh()->clicks)->toBe(0);

        $this->artisan('bio:aggregate-clicks', ['--hours' => 2])
            ->assertExitCode(0);

        $biolink = $this->biolink->fresh();
        expect($biolink->clicks)->toBe(2)
            ->and($biolink->unique_clicks)->toBe(1);
    });
});

describe('Analytics Livewire Component', function () {
    it('renders analytics page for authenticated user', function () {
        $this->actingAs($this->user)
            ->get(route('hub.bio.analytics', $this->biolink->id))
            ->assertOk()
            ->assertSee('Analytics');
    });

    it('rejects access to other users biolinks', function () {
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)
            ->get(route('hub.bio.analytics', $this->biolink->id))
            ->assertNotFound();
    });

    it('rejects unauthenticated access', function () {
        $this->get(route('hub.bio.analytics', $this->biolink->id))
            ->assertRedirect(route('login'));
    });
});
