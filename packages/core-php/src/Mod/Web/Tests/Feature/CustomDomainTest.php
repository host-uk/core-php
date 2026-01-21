<?php

use Core\Mod\Web\Middleware\BioDomainResolver;
use Core\Mod\Web\Models\Domain;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\DomainVerificationService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);
    $this->actingAs($this->user);
});

describe('BioLinkDomain model', function () {
    it('creates domain with default pending status', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'example.com',
        ]);

        expect($domain->verification_status)->toBe('pending')
            ->and($domain->is_enabled)->toBeFalse()
            ->and($domain->scheme)->toBe('https');
    });

    it('generates unique verification token', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'example.com',
        ]);

        $token = $domain->generateVerificationToken();

        expect($token)->toBeString()
            ->and(strlen($token))->toBe(64) // 32 bytes = 64 hex chars
            ->and($domain->verification_token)->toBe($token);
    });

    it('generates correct DNS verification record format', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'example.com',
        ]);

        $domain->generateVerificationToken();
        $record = $domain->getDnsVerificationRecord();

        expect($record)->toStartWith('host-uk-verify=')
            ->and($record)->toContain($domain->verification_token);
    });

    it('marks domain as verified correctly', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'example.com',
        ]);

        $domain->markAsVerified();
        $domain->refresh();

        expect($domain->verification_status)->toBe('verified')
            ->and($domain->is_enabled)->toBeTrue()
            ->and($domain->verified_at)->not->toBeNull();
    });

    it('marks verification as failed', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'example.com',
        ]);

        $domain->markVerificationFailed();
        $domain->refresh();

        expect($domain->verification_status)->toBe('failed')
            ->and($domain->is_enabled)->toBeFalse();
    });

    it('generates correct base URL', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'bio.example.com',
            'scheme' => 'https',
        ]);

        expect($domain->base_url)->toBe('https://bio.example.com');
    });

    it('supports exclusive biolink assignment', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'mypage',
        ]);

        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'example.com',
            'biolink_id' => $biolink->id,
        ]);

        expect($domain->isExclusive())->toBeTrue()
            ->and($domain->exclusiveLink->id)->toBe($biolink->id);
    });

    it('scopes to verified domains', function () {
        Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'pending.example.com',
            'verification_status' => 'pending',
        ]);

        $verified = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'verified.example.com',
            'verification_status' => 'verified',
        ]);

        $verifiedDomains = Domain::verified()->get();

        expect($verifiedDomains)->toHaveCount(1)
            ->and($verifiedDomains->first()->host)->toBe('verified.example.com');
    });

    it('scopes to enabled domains', function () {
        Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'disabled.example.com',
            'is_enabled' => false,
        ]);

        $enabled = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'enabled.example.com',
            'is_enabled' => true,
        ]);

        $enabledDomains = Domain::enabled()->get();

        expect($enabledDomains)->toHaveCount(1)
            ->and($enabledDomains->first()->host)->toBe('enabled.example.com');
    });

    it('supports soft deletes', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'to-delete.example.com',
        ]);

        $domain->delete();

        expect(Domain::find($domain->id))->toBeNull()
            ->and(Domain::withTrashed()->find($domain->id))->not->toBeNull();
    });

    it('belongs to workspace', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'example.com',
        ]);

        expect($domain->workspace->id)->toBe($this->workspace->id)
            ->and($domain->belongsToCurrentWorkspace())->toBeTrue();
    });
});

describe('DomainVerificationService', function () {
    beforeEach(function () {
        $this->service = new DomainVerificationService;
    });

    it('validates correct domain formats', function () {
        expect($this->service->validateDomainFormat('example.com'))->toBeTrue()
            ->and($this->service->validateDomainFormat('sub.example.com'))->toBeTrue()
            ->and($this->service->validateDomainFormat('bio.example.co.uk'))->toBeTrue()
            ->and($this->service->validateDomainFormat('my-domain.example.com'))->toBeTrue();
    });

    it('rejects invalid domain formats', function () {
        // The service strips http:// and trailing / before validation, so test truly invalid formats
        expect($this->service->validateDomainFormat(''))->toBeFalse()
            ->and($this->service->validateDomainFormat('not-a-domain'))->toBeFalse()
            ->and($this->service->validateDomainFormat('.example.com'))->toBeFalse()
            ->and($this->service->validateDomainFormat('example..com'))->toBeFalse()
            ->and($this->service->validateDomainFormat('example'))->toBeFalse()
            ->and($this->service->validateDomainFormat('123.456'))->toBeFalse();
    });

    it('normalises host correctly', function () {
        expect($this->service->normaliseHost('Example.COM'))->toBe('example.com')
            ->and($this->service->normaliseHost('https://example.com'))->toBe('example.com')
            ->and($this->service->normaliseHost('http://Example.com/'))->toBe('example.com');
    });

    it('identifies reserved domains', function () {
        expect($this->service->isDomainReserved('host.uk.com'))->toBeTrue()
            ->and($this->service->isDomainReserved('bio.host.uk.com'))->toBeTrue()
            ->and($this->service->isDomainReserved('lnktr.fyi'))->toBeTrue()
            ->and($this->service->isDomainReserved('sub.host.uk.com'))->toBeTrue();
    });

    it('allows non-reserved domains', function () {
        expect($this->service->isDomainReserved('mysite.com'))->toBeFalse()
            ->and($this->service->isDomainReserved('bio.mysite.com'))->toBeFalse();
    });

    it('provides DNS instructions', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'example.com',
        ]);

        $domain->generateVerificationToken();
        $instructions = $this->service->getDnsInstructions($domain);

        expect($instructions)->toHaveKeys(['cname', 'txt'])
            ->and($instructions['cname']['type'])->toBe('CNAME')
            ->and($instructions['cname']['target'])->toBe('bio.host.uk.com')
            ->and($instructions['txt']['type'])->toBe('TXT')
            ->and($instructions['txt']['host'])->toBe('_biohost-verify.example.com')
            ->and($instructions['txt']['value'])->toContain('host-uk-verify=');
    });
});

describe('BioDomainResolver middleware', function () {
    beforeEach(function () {
        $this->middleware = new BioDomainResolver;
        Cache::flush();
    });

    it('stores null for default domains', function () {
        $request = Request::create('http://bio.host.uk.com/testpage');
        $request->headers->set('HOST', 'bio.host.uk.com');

        $response = $this->middleware->handle($request, function ($req) {
            expect($req->attributes->get('biolink_domain'))->toBeNull()
                ->and($req->attributes->get('biolink_domain_id'))->toBeNull();

            return response('ok');
        });

        expect($response->getContent())->toBe('ok');
    });

    it('resolves verified custom domain', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'custom.example.com',
            'verification_status' => 'verified',
            'is_enabled' => true,
        ]);

        $request = Request::create('http://custom.example.com/testpage');
        $request->headers->set('HOST', 'custom.example.com');

        $response = $this->middleware->handle($request, function ($req) use ($domain) {
            expect($req->attributes->get('biolink_domain'))->not->toBeNull()
                ->and($req->attributes->get('biolink_domain_id'))->toBe($domain->id);

            return response('ok');
        });

        expect($response->getContent())->toBe('ok');
    });

    it('does not resolve unverified domains', function () {
        Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'unverified.example.com',
            'verification_status' => 'pending',
            'is_enabled' => false,
        ]);

        $request = Request::create('http://unverified.example.com/testpage');
        $request->headers->set('HOST', 'unverified.example.com');

        $response = $this->middleware->handle($request, function ($req) {
            expect($req->attributes->get('biolink_domain'))->toBeNull();

            return response('ok');
        });

        expect($response->getContent())->toBe('ok');
    });

    it('redirects root path to custom index URL', function () {
        Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'redirect.example.com',
            'verification_status' => 'verified',
            'is_enabled' => true,
            'custom_index_url' => 'https://external.com/landing',
        ]);

        $request = Request::create('http://redirect.example.com/');
        $request->headers->set('HOST', 'redirect.example.com');

        $response = $this->middleware->handle($request, function ($req) {
            return response('should not reach here');
        });

        expect($response->isRedirect())->toBeTrue()
            ->and($response->headers->get('Location'))->toBe('https://external.com/landing');
    });

    it('redirects root path to exclusive biolink', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'mybiopage',
        ]);

        Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'exclusive.example.com',
            'verification_status' => 'verified',
            'is_enabled' => true,
            'biolink_id' => $biolink->id,
        ]);

        $request = Request::create('http://exclusive.example.com/');
        $request->headers->set('HOST', 'exclusive.example.com');

        $response = $this->middleware->handle($request, function ($req) {
            return response('should not reach here');
        });

        expect($response->isRedirect())->toBeTrue()
            ->and($response->headers->get('Location'))->toContain('/mybiopage');
    });

    it('caches domain lookups', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'cached.example.com',
            'verification_status' => 'verified',
            'is_enabled' => true,
        ]);

        // First request - cache miss
        $request1 = Request::create('http://cached.example.com/page1');
        $request1->headers->set('HOST', 'cached.example.com');

        $this->middleware->handle($request1, function () {
            return response('ok');
        });

        // Verify cache was set
        $cached = Cache::get('biolink_domain:cached.example.com');
        expect($cached)->not->toBeNull()
            ->and($cached->id)->toBe($domain->id);
    });
});

describe('Domain Manager Livewire Component', function () {
    it('renders the domain manager page', function () {
        $response = $this->get(route('hub.bio.domains'));

        $response->assertStatus(200);
        $response->assertSee('Custom Domains');
    });

    it('lists domains for current workspace', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'listed.example.com',
        ]);

        // Domain from another workspace should not appear
        $otherWorkspace = Workspace::factory()->create();
        Domain::create([
            'workspace_id' => $otherWorkspace->id,
            'user_id' => $this->user->id,
            'host' => 'other.example.com',
        ]);

        $response = $this->get(route('hub.bio.domains'));

        $response->assertSee('listed.example.com');
        $response->assertDontSee('other.example.com');
    });

    it('can add a new domain via Livewire', function () {
        Livewire::test(\Core\Mod\Web\View\Livewire\Hub\DomainManager::class)
            ->set('newHost', 'newdomain.example.com')
            ->call('addDomain');

        expect(Domain::where('host', 'newdomain.example.com')->exists())->toBeTrue();
    });

    it('rejects reserved domains', function () {
        Livewire::test(\Core\Mod\Web\View\Livewire\Hub\DomainManager::class)
            ->set('newHost', 'bio.host.uk.com')
            ->call('addDomain')
            ->assertHasErrors('newHost');
    });

    it('rejects duplicate domains', function () {
        Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'existing.example.com',
        ]);

        Livewire::test(\Core\Mod\Web\View\Livewire\Hub\DomainManager::class)
            ->set('newHost', 'existing.example.com')
            ->call('addDomain')
            ->assertHasErrors('newHost');
    });

    it('can delete a domain', function () {
        $domain = Domain::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'host' => 'to-delete.example.com',
        ]);

        Livewire::test(\Core\Mod\Web\View\Livewire\Hub\DomainManager::class)
            ->call('deleteDomain', $domain->id);

        expect(Domain::find($domain->id))->toBeNull();
    });
});
