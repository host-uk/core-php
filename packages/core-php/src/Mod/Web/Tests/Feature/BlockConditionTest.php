<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\BlockConditionService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Http\Request;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
    $this->biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'testconditions',
    ]);
    $this->conditionService = app(BlockConditionService::class);
});

it('shows enabled block with no conditions', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
    ]);

    $request = Request::create('/testconditions', 'GET');

    expect($this->conditionService->shouldDisplay($block, $request))->toBeTrue();
});

it('hides disabled block', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => false,
    ]);

    $request = Request::create('/testconditions', 'GET');

    expect($this->conditionService->shouldDisplay($block, $request))->toBeFalse();
});

it('respects schedule start date', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'start_date' => now()->addDays(1), // Starts tomorrow
    ]);

    $request = Request::create('/testconditions', 'GET');

    expect($this->conditionService->shouldDisplay($block, $request))->toBeFalse();
});

it('respects schedule end date', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'end_date' => now()->subDays(1), // Ended yesterday
    ]);

    $request = Request::create('/testconditions', 'GET');

    expect($this->conditionService->shouldDisplay($block, $request))->toBeFalse();
});

it('shows block within schedule', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'start_date' => now()->subDays(1),
        'end_date' => now()->addDays(1),
    ]);

    $request = Request::create('/testconditions', 'GET');

    expect($this->conditionService->shouldDisplay($block, $request))->toBeTrue();
});

it('filters by mobile device', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'settings' => [
            'conditions' => [
                'devices' => ['mobile'],
            ],
        ],
    ]);

    // Desktop user agent
    $desktopRequest = Request::create('/testconditions', 'GET');
    $desktopRequest->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    // Mobile user agent
    $mobileRequest = Request::create('/testconditions', 'GET');
    $mobileRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15');

    expect($this->conditionService->shouldDisplay($block, $desktopRequest))->toBeFalse()
        ->and($this->conditionService->shouldDisplay($block, $mobileRequest))->toBeTrue();
});

it('filters by country code from CDN header', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'settings' => [
            'conditions' => [
                'countries' => ['GB', 'US'],
            ],
        ],
    ]);

    // UK request
    $ukRequest = Request::create('/testconditions', 'GET');
    $ukRequest->headers->set('CF-IPCountry', 'GB');

    // France request
    $frRequest = Request::create('/testconditions', 'GET');
    $frRequest->headers->set('CF-IPCountry', 'FR');

    expect($this->conditionService->shouldDisplay($block, $ukRequest))->toBeTrue()
        ->and($this->conditionService->shouldDisplay($block, $frRequest))->toBeFalse();
});

it('excludes specified countries', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'settings' => [
            'conditions' => [
                'exclude_countries' => ['CN', 'RU'],
            ],
        ],
    ]);

    // Excluded country
    $blockedRequest = Request::create('/testconditions', 'GET');
    $blockedRequest->headers->set('CF-IPCountry', 'CN');

    // Allowed country
    $allowedRequest = Request::create('/testconditions', 'GET');
    $allowedRequest->headers->set('CF-IPCountry', 'GB');

    expect($this->conditionService->shouldDisplay($block, $blockedRequest))->toBeFalse()
        ->and($this->conditionService->shouldDisplay($block, $allowedRequest))->toBeTrue();
});

it('filters by browser', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'settings' => [
            'conditions' => [
                'browsers' => ['Safari'],
            ],
        ],
    ]);

    // Safari request
    $safariRequest = Request::create('/testconditions', 'GET');
    $safariRequest->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15');

    // Chrome request
    $chromeRequest = Request::create('/testconditions', 'GET');
    $chromeRequest->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    expect($this->conditionService->shouldDisplay($block, $safariRequest))->toBeTrue()
        ->and($this->conditionService->shouldDisplay($block, $chromeRequest))->toBeFalse();
});

it('filters by language preference', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'settings' => [
            'conditions' => [
                'languages' => ['es', 'pt'],
            ],
        ],
    ]);

    // Spanish request
    $esRequest = Request::create('/testconditions', 'GET');
    $esRequest->headers->set('Accept-Language', 'es-ES,es;q=0.9');

    // English request
    $enRequest = Request::create('/testconditions', 'GET');
    $enRequest->headers->set('Accept-Language', 'en-GB,en;q=0.9');

    expect($this->conditionService->shouldDisplay($block, $esRequest))->toBeTrue()
        ->and($this->conditionService->shouldDisplay($block, $enRequest))->toBeFalse();
});

it('combines multiple conditions with AND logic', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'settings' => [
            'conditions' => [
                'devices' => ['mobile'],
                'countries' => ['GB'],
            ],
        ],
    ]);

    // UK mobile request - should show
    $ukMobileRequest = Request::create('/testconditions', 'GET');
    $ukMobileRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)');
    $ukMobileRequest->headers->set('CF-IPCountry', 'GB');

    // UK desktop request - should hide (fails device check)
    $ukDesktopRequest = Request::create('/testconditions', 'GET');
    $ukDesktopRequest->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    $ukDesktopRequest->headers->set('CF-IPCountry', 'GB');

    // US mobile request - should hide (fails country check)
    $usMobileRequest = Request::create('/testconditions', 'GET');
    $usMobileRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)');
    $usMobileRequest->headers->set('CF-IPCountry', 'US');

    expect($this->conditionService->shouldDisplay($block, $ukMobileRequest))->toBeTrue()
        ->and($this->conditionService->shouldDisplay($block, $ukDesktopRequest))->toBeFalse()
        ->and($this->conditionService->shouldDisplay($block, $usMobileRequest))->toBeFalse();
});

it('provides static options for editor UI', function () {
    $options = BlockConditionService::getConditionOptions();

    expect($options)
        ->toHaveKey('devices')
        ->toHaveKey('browsers')
        ->toHaveKey('operating_systems')
        ->toHaveKey('days_of_week');

    expect($options['devices'])
        ->toHaveKey('desktop')
        ->toHaveKey('mobile')
        ->toHaveKey('tablet');
});

it('provides common countries list', function () {
    $countries = BlockConditionService::getCommonCountries();

    expect($countries)
        ->toHaveKey('GB', 'United Kingdom')
        ->toHaveKey('US', 'United States');
});

it('reports hasConditions correctly', function () {
    $blockWithConditions = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'settings' => [
            'conditions' => [
                'devices' => ['mobile'],
            ],
        ],
    ]);

    $blockWithoutConditions = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 2,
        'is_enabled' => true,
    ]);

    expect($blockWithConditions->hasConditions())->toBeTrue()
        ->and($blockWithoutConditions->hasConditions())->toBeFalse();
});

it('provides conditions summary for editor', function () {
    $block = $this->biolink->blocks()->create([
        'workspace_id' => $this->workspace->id,
        'type' => 'link',
        'order' => 1,
        'is_enabled' => true,
        'settings' => [
            'conditions' => [
                'devices' => ['mobile', 'tablet'],
                'countries' => ['GB', 'US'],
            ],
        ],
    ]);

    $summary = $block->getConditionsSummary();

    expect($summary)
        ->toHaveKey('devices', 'mobile, tablet')
        ->toHaveKey('countries', 'GB, US');
});
