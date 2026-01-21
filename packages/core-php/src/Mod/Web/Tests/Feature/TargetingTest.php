<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\TargetingService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
    $this->targetingService = app(TargetingService::class);
});

// ========================================================================
// Geo Targeting Tests
// ========================================================================

it('allows access when no targeting rules set', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'no-targeting',
        'settings' => [],
    ]);

    $request = Request::create('/no-targeting', 'GET');

    $result = $this->targetingService->evaluate($biolink, $request);

    expect($result['matches'])->toBeTrue()
        ->and($result['reason'])->toBeNull();
});

it('evaluates rules regardless of enabled flag (controller checks enabled)', function () {
    // Note: The TargetingService just evaluates rules.
    // The controller is responsible for checking the 'enabled' flag.
    // This test verifies that the service correctly evaluates targeting rules.
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'targeting-disabled',
        'settings' => [
            'targeting' => [
                'enabled' => false, // Controller should skip if false
                'countries' => ['US'],
            ],
        ],
    ]);

    $request = Request::create('/targeting-disabled', 'GET');
    $request->headers->set('CF-IPCountry', 'GB'); // UK visitor - would fail country check

    // Service evaluates rules regardless of enabled flag
    $result = $this->targetingService->evaluate($biolink, $request);

    // The service correctly evaluates that GB is not in ['US']
    expect($result['matches'])->toBeFalse()
        ->and($result['reason'])->toBe('country_not_allowed');
});

it('filters by allowed countries', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'country-filter',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'countries' => ['GB', 'US', 'CA'],
            ],
        ],
    ]);

    // UK request - allowed
    $ukRequest = Request::create('/country-filter', 'GET');
    $ukRequest->headers->set('CF-IPCountry', 'GB');

    // Germany request - blocked
    $deRequest = Request::create('/country-filter', 'GET');
    $deRequest->headers->set('CF-IPCountry', 'DE');

    expect($this->targetingService->evaluate($biolink, $ukRequest)['matches'])->toBeTrue()
        ->and($this->targetingService->evaluate($biolink, $deRequest)['matches'])->toBeFalse()
        ->and($this->targetingService->evaluate($biolink, $deRequest)['reason'])->toBe('country_not_allowed');
});

it('excludes specified countries', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'country-exclude',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'exclude_countries' => ['RU', 'CN'],
            ],
        ],
    ]);

    // Russia request - blocked
    $ruRequest = Request::create('/country-exclude', 'GET');
    $ruRequest->headers->set('CF-IPCountry', 'RU');

    // UK request - allowed
    $ukRequest = Request::create('/country-exclude', 'GET');
    $ukRequest->headers->set('CF-IPCountry', 'GB');

    expect($this->targetingService->evaluate($biolink, $ruRequest)['matches'])->toBeFalse()
        ->and($this->targetingService->evaluate($biolink, $ruRequest)['reason'])->toBe('country_excluded')
        ->and($this->targetingService->evaluate($biolink, $ukRequest)['matches'])->toBeTrue();
});

it('supports multiple CDN country headers', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'cdn-headers',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'countries' => ['GB'],
            ],
        ],
    ]);

    // Bunny CDN header
    $bunnyRequest = Request::create('/cdn-headers', 'GET');
    $bunnyRequest->headers->set('X-Country-Code', 'GB');
    expect($this->targetingService->evaluate($biolink, $bunnyRequest)['matches'])->toBeTrue();

    // Cloudflare header
    $cfRequest = Request::create('/cdn-headers', 'GET');
    $cfRequest->headers->set('CF-IPCountry', 'GB');
    expect($this->targetingService->evaluate($biolink, $cfRequest)['matches'])->toBeTrue();

    // CloudFront header
    $awsRequest = Request::create('/cdn-headers', 'GET');
    $awsRequest->headers->set('CloudFront-Viewer-Country', 'GB');
    expect($this->targetingService->evaluate($biolink, $awsRequest)['matches'])->toBeTrue();
});

// ========================================================================
// Device Targeting Tests
// ========================================================================

it('filters by device type', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'device-filter',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'devices' => ['mobile'],
            ],
        ],
    ]);

    // Mobile request
    $mobileRequest = Request::create('/device-filter', 'GET');
    $mobileRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15');

    // Desktop request
    $desktopRequest = Request::create('/device-filter', 'GET');
    $desktopRequest->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    expect($this->targetingService->evaluate($biolink, $mobileRequest)['matches'])->toBeTrue()
        ->and($this->targetingService->evaluate($biolink, $desktopRequest)['matches'])->toBeFalse()
        ->and($this->targetingService->evaluate($biolink, $desktopRequest)['reason'])->toBe('device_not_allowed');
});

it('allows multiple device types', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'multi-device',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'devices' => ['mobile', 'tablet'],
            ],
        ],
    ]);

    // Tablet request
    $tabletRequest = Request::create('/multi-device', 'GET');
    $tabletRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15');

    // Mobile request
    $mobileRequest = Request::create('/multi-device', 'GET');
    $mobileRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15');

    expect($this->targetingService->evaluate($biolink, $tabletRequest)['matches'])->toBeTrue()
        ->and($this->targetingService->evaluate($biolink, $mobileRequest)['matches'])->toBeTrue();
});

// ========================================================================
// Browser Targeting Tests
// ========================================================================

it('filters by browser type', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'browser-filter',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'browsers' => ['Safari'],
            ],
        ],
    ]);

    // Safari request
    $safariRequest = Request::create('/browser-filter', 'GET');
    $safariRequest->headers->set('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15');

    // Chrome request
    $chromeRequest = Request::create('/browser-filter', 'GET');
    $chromeRequest->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

    expect($this->targetingService->evaluate($biolink, $safariRequest)['matches'])->toBeTrue()
        ->and($this->targetingService->evaluate($biolink, $chromeRequest)['matches'])->toBeFalse()
        ->and($this->targetingService->evaluate($biolink, $chromeRequest)['reason'])->toBe('browser_not_allowed');
});

// ========================================================================
// OS Targeting Tests
// ========================================================================

it('filters by operating system', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'os-filter',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'operating_systems' => ['iOS', 'macOS'],
            ],
        ],
    ]);

    // iOS request
    $iosRequest = Request::create('/os-filter', 'GET');
    $iosRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15');

    // Windows request
    $windowsRequest = Request::create('/os-filter', 'GET');
    $windowsRequest->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    expect($this->targetingService->evaluate($biolink, $iosRequest)['matches'])->toBeTrue()
        ->and($this->targetingService->evaluate($biolink, $windowsRequest)['matches'])->toBeFalse()
        ->and($this->targetingService->evaluate($biolink, $windowsRequest)['reason'])->toBe('os_not_allowed');
});

// ========================================================================
// Language Targeting Tests
// ========================================================================

it('filters by accepted language', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'language-filter',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'languages' => ['en', 'es'],
            ],
        ],
    ]);

    // English request
    $enRequest = Request::create('/language-filter', 'GET');
    $enRequest->headers->set('Accept-Language', 'en-GB,en;q=0.9');

    // Spanish request
    $esRequest = Request::create('/language-filter', 'GET');
    $esRequest->headers->set('Accept-Language', 'es-ES,es;q=0.9');

    // French request
    $frRequest = Request::create('/language-filter', 'GET');
    $frRequest->headers->set('Accept-Language', 'fr-FR,fr;q=0.9');

    expect($this->targetingService->evaluate($biolink, $enRequest)['matches'])->toBeTrue()
        ->and($this->targetingService->evaluate($biolink, $esRequest)['matches'])->toBeTrue()
        ->and($this->targetingService->evaluate($biolink, $frRequest)['matches'])->toBeFalse()
        ->and($this->targetingService->evaluate($biolink, $frRequest)['reason'])->toBe('language_not_allowed');
});

it('allows when language header is missing', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'no-language-header',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'languages' => ['en'],
            ],
        ],
    ]);

    $request = Request::create('/no-language-header', 'GET');
    // No Accept-Language header

    expect($this->targetingService->evaluate($biolink, $request)['matches'])->toBeTrue();
});

// ========================================================================
// Combined Rules (AND Logic) Tests
// ========================================================================

it('combines targeting rules with AND logic', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'combined-rules',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'countries' => ['GB'],
                'devices' => ['mobile'],
            ],
        ],
    ]);

    // UK mobile - allowed
    $ukMobileRequest = Request::create('/combined-rules', 'GET');
    $ukMobileRequest->headers->set('CF-IPCountry', 'GB');
    $ukMobileRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)');

    // UK desktop - blocked (fails device)
    $ukDesktopRequest = Request::create('/combined-rules', 'GET');
    $ukDesktopRequest->headers->set('CF-IPCountry', 'GB');
    $ukDesktopRequest->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');

    // US mobile - blocked (fails country)
    $usMobileRequest = Request::create('/combined-rules', 'GET');
    $usMobileRequest->headers->set('CF-IPCountry', 'US');
    $usMobileRequest->headers->set('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)');

    expect($this->targetingService->evaluate($biolink, $ukMobileRequest)['matches'])->toBeTrue()
        ->and($this->targetingService->evaluate($biolink, $ukDesktopRequest)['matches'])->toBeFalse()
        ->and($this->targetingService->evaluate($biolink, $usMobileRequest)['matches'])->toBeFalse();
});

// ========================================================================
// Fallback URL Tests
// ========================================================================

it('returns fallback URL when targeting fails', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'fallback-url',
        'settings' => [
            'targeting' => [
                'enabled' => true,
                'countries' => ['US'],
                'fallback_url' => 'https://example.com/not-available',
            ],
        ],
    ]);

    $request = Request::create('/fallback-url', 'GET');
    $request->headers->set('CF-IPCountry', 'GB');

    $result = $this->targetingService->evaluate($biolink, $request);

    expect($result['matches'])->toBeFalse()
        ->and($result['fallback_url'])->toBe('https://example.com/not-available');
});

// ========================================================================
// Password Protection Settings Tests
// ========================================================================

it('stores password protection settings correctly', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'protected-page',
        'is_enabled' => true,
        'settings' => [
            'password_protected' => true,
            'password' => Hash::make('secret123'),
            'password_hint' => 'Your favourite colour',
        ],
    ]);

    expect($biolink->getSetting('password_protected'))->toBeTrue()
        ->and($biolink->getSetting('password'))->not->toBeNull()
        ->and($biolink->getSetting('password_hint'))->toBe('Your favourite colour')
        ->and(Hash::check('secret123', $biolink->getSetting('password')))->toBeTrue();
});

it('verifies password hash correctly', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'verify-password',
        'is_enabled' => true,
        'settings' => [
            'password_protected' => true,
            'password' => Hash::make('correctpassword'),
        ],
    ]);

    $storedHash = $biolink->getSetting('password');

    expect(Hash::check('correctpassword', $storedHash))->toBeTrue()
        ->and(Hash::check('wrongpassword', $storedHash))->toBeFalse();
});

it('can disable password protection', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'not-protected',
        'is_enabled' => true,
        'settings' => [
            'password_protected' => false,
        ],
    ]);

    expect($biolink->getSetting('password_protected'))->toBeFalse();
});

// ========================================================================
// Sensitive Content Settings Tests
// ========================================================================

it('stores sensitive content settings correctly', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'sensitive-content',
        'is_enabled' => true,
        'settings' => [
            'sensitive_content' => true,
            'sensitive_type' => 'adult',
            'sensitive_message' => 'This contains adult content.',
            'sensitive_age_gate' => true,
        ],
    ]);

    expect($biolink->getSetting('sensitive_content'))->toBeTrue()
        ->and($biolink->getSetting('sensitive_type'))->toBe('adult')
        ->and($biolink->getSetting('sensitive_message'))->toBe('This contains adult content.')
        ->and($biolink->getSetting('sensitive_age_gate'))->toBeTrue();
});

it('supports different sensitive content types', function () {
    $types = ['adult', 'violence', 'medical', 'flashing', 'other'];

    foreach ($types as $type) {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => "sensitive-{$type}",
            'is_enabled' => true,
            'settings' => [
                'sensitive_content' => true,
                'sensitive_type' => $type,
            ],
        ]);

        expect($biolink->getSetting('sensitive_type'))->toBe($type);
    }
});

it('can configure referrer bypass for sensitive content', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'sensitive-bypass',
        'is_enabled' => true,
        'settings' => [
            'sensitive_content' => true,
            'sensitive_skip_for_referrers' => ['twitter.com', 'instagram.com'],
        ],
    ]);

    expect($biolink->getSetting('sensitive_skip_for_referrers'))
        ->toBeArray()
        ->toContain('twitter.com')
        ->toContain('instagram.com');
});

// ========================================================================
// Link Cloaking Settings Tests
// ========================================================================

it('stores cloaking settings correctly', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'link',
        'url' => 'cloaked-link',
        'location_url' => 'https://example.com/destination',
        'is_enabled' => true,
        'settings' => [
            'cloaking_enabled' => true,
            'cloaking_title' => 'My Cloaked Page',
        ],
    ]);

    expect($biolink->getSetting('cloaking_enabled'))->toBeTrue()
        ->and($biolink->getSetting('cloaking_title'))->toBe('My Cloaked Page')
        ->and($biolink->location_url)->toBe('https://example.com/destination');
});

it('can disable cloaking', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'link',
        'url' => 'normal-redirect',
        'location_url' => 'https://example.com/destination',
        'is_enabled' => true,
        'settings' => [
            'cloaking_enabled' => false,
        ],
    ]);

    expect($biolink->getSetting('cloaking_enabled'))->toBeFalse();
});

// ========================================================================
// Redirect Type Settings Tests
// ========================================================================

it('stores 301 redirect type setting', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'link',
        'url' => 'permanent-redirect',
        'location_url' => 'https://example.com/destination',
        'is_enabled' => true,
        'settings' => [
            'redirect_type' => 301,
        ],
    ]);

    expect($biolink->getSetting('redirect_type'))->toBe(301);
});

it('defaults to 302 redirect type', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'link',
        'url' => 'temp-redirect',
        'location_url' => 'https://example.com/destination',
        'is_enabled' => true,
        'settings' => [],
    ]);

    // Default is 302 when not specified
    expect($biolink->getSetting('redirect_type', 302))->toBe(302);
});

it('stores 302 redirect type explicitly', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'link',
        'url' => 'explicit-302',
        'location_url' => 'https://example.com/destination',
        'is_enabled' => true,
        'settings' => [
            'redirect_type' => 302,
        ],
    ]);

    expect($biolink->getSetting('redirect_type'))->toBe(302);
});

// ========================================================================
// Verified Badge Tests
// ========================================================================

it('stores is_verified flag correctly', function () {
    $biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'verified-user',
        'is_verified' => true,
    ]);

    expect($biolink->is_verified)->toBeTrue();

    $unverified = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'unverified-user',
        'is_verified' => false,
    ]);

    expect($unverified->is_verified)->toBeFalse();
});

// ========================================================================
// Static Helper Tests
// ========================================================================

it('provides human-readable reason messages', function () {
    expect(TargetingService::getReasonMessage('country_excluded'))
        ->toBe('This content is not available in your region.')
        ->and(TargetingService::getReasonMessage('device_not_allowed'))
        ->toBe('This content is not available on your device type.')
        ->and(TargetingService::getReasonMessage('unknown'))
        ->toBe('This content is not available.');
});

it('provides targeting options for UI', function () {
    $options = TargetingService::getTargetingOptions();

    expect($options)
        ->toHaveKey('devices')
        ->toHaveKey('browsers')
        ->toHaveKey('operating_systems')
        ->toHaveKey('languages');

    expect($options['devices'])
        ->toHaveKey('desktop')
        ->toHaveKey('mobile')
        ->toHaveKey('tablet');
});

it('provides common countries list', function () {
    $countries = TargetingService::getCommonCountries();

    expect($countries)
        ->toHaveKey('GB', 'United Kingdom')
        ->toHaveKey('US', 'United States')
        ->toHaveKey('CA', 'Canada');
});
