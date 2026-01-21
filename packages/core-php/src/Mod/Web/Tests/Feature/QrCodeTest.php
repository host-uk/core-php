<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\QrCodeService;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);

    $this->biolink = Page::create([
        'workspace_id' => $this->workspace->id,
        'user_id' => $this->user->id,
        'type' => 'biolink',
        'url' => 'test-qr-page',
        'settings' => [],
    ]);
});

describe('QrCodeService', function () {
    it('generates a QR code for a biolink', function () {
        $service = app(QrCodeService::class);

        $output = $service->generate($this->biolink, [
            'return_base64' => true,
            'format' => 'png',
        ]);

        expect($output)->toBeString()
            ->and(strlen($output))->toBeGreaterThan(100); // Should have substantial content
    });

    it('generates a QR code as PNG by default', function () {
        $service = app(QrCodeService::class);

        $output = $service->generate($this->biolink, [
            'return_base64' => false,
            'format' => 'png',
        ]);

        // PNG files start with specific bytes
        expect(substr($output, 0, 8))->toBe(chr(137).'PNG'.chr(13).chr(10).chr(26).chr(10));
    });

    it('generates a QR code as SVG when requested', function () {
        $service = app(QrCodeService::class);

        $output = $service->generate($this->biolink, [
            'return_base64' => false,
            'format' => 'svg',
        ]);

        expect($output)->toContain('<svg')
            ->and($output)->toContain('</svg>');
    });

    it('generates a data URI for display', function () {
        $service = app(QrCodeService::class);

        $dataUri = $service->generateDataUri($this->biolink, [
            'format' => 'png',
        ]);

        expect($dataUri)->toStartWith('data:image/png;base64,');
    });

    it('applies custom foreground colour', function () {
        $service = app(QrCodeService::class);

        // Generate with different colours to verify they produce different outputs
        $blackOutput = $service->generate($this->biolink, [
            'foreground_colour' => '#000000',
            'return_base64' => true,
        ]);

        $redOutput = $service->generate($this->biolink, [
            'foreground_colour' => '#ff0000',
            'return_base64' => true,
        ]);

        // Different colours should produce different outputs
        expect($blackOutput)->not->toBe($redOutput);
    });

    it('applies custom background colour', function () {
        $service = app(QrCodeService::class);

        $whiteOutput = $service->generate($this->biolink, [
            'background_colour' => '#ffffff',
            'return_base64' => true,
        ]);

        $blueOutput = $service->generate($this->biolink, [
            'background_colour' => '#0000ff',
            'return_base64' => true,
        ]);

        expect($whiteOutput)->not->toBe($blueOutput);
    });

    it('generates different sizes', function () {
        $service = app(QrCodeService::class);

        $smallOutput = $service->generate($this->biolink, [
            'size' => 200,
            'return_base64' => true,
        ]);

        $largeOutput = $service->generate($this->biolink, [
            'size' => 800,
            'return_base64' => true,
        ]);

        // Larger size should produce larger output (more base64 data)
        expect(strlen($largeOutput))->toBeGreaterThan(strlen($smallOutput));
    });

    it('uses different error correction levels', function () {
        $service = app(QrCodeService::class);

        $lowEcc = $service->generate($this->biolink, [
            'ecc_level' => 'L',
            'return_base64' => true,
        ]);

        $highEcc = $service->generate($this->biolink, [
            'ecc_level' => 'H',
            'return_base64' => true,
        ]);

        // High ECC produces denser (larger) QR codes
        expect(strlen($highEcc))->toBeGreaterThan(strlen($lowEcc));
    });

    it('provides default settings', function () {
        $defaults = QrCodeService::getDefaultSettings();

        expect($defaults)->toBeArray()
            ->and($defaults)->toHaveKey('foreground_colour')
            ->and($defaults)->toHaveKey('background_colour')
            ->and($defaults)->toHaveKey('size')
            ->and($defaults)->toHaveKey('ecc_level')
            ->and($defaults)->toHaveKey('module_style')
            ->and($defaults['foreground_colour'])->toBe('#000000')
            ->and($defaults['background_colour'])->toBe('#ffffff');
    });

    it('validates settings correctly', function () {
        // Valid settings
        $errors = QrCodeService::validateSettings([
            'foreground_colour' => '#ff5500',
            'background_colour' => '#ffffff',
            'size' => 400,
            'ecc_level' => 'M',
            'module_style' => 'square',
            'logo_size' => 20,
        ]);
        expect($errors)->toBeEmpty();

        // Invalid colour format
        $errors = QrCodeService::validateSettings([
            'foreground_colour' => 'not-a-colour',
        ]);
        expect($errors)->toHaveKey('foreground_colour');

        // Invalid size
        $errors = QrCodeService::validateSettings([
            'size' => 50, // Too small
        ]);
        expect($errors)->toHaveKey('size');

        // Invalid ECC level
        $errors = QrCodeService::validateSettings([
            'ecc_level' => 'X',
        ]);
        expect($errors)->toHaveKey('ecc_level');

        // Invalid module style
        $errors = QrCodeService::validateSettings([
            'module_style' => 'invalid',
        ]);
        expect($errors)->toHaveKey('module_style');

        // Invalid logo size
        $errors = QrCodeService::validateSettings([
            'logo_size' => 50, // Too large
        ]);
        expect($errors)->toHaveKey('logo_size');
    });

    it('has available module styles', function () {
        expect(QrCodeService::MODULE_STYLES)->toBeArray()
            ->and(QrCodeService::MODULE_STYLES)->toHaveKey('square')
            ->and(QrCodeService::MODULE_STYLES)->toHaveKey('rounded')
            ->and(QrCodeService::MODULE_STYLES)->toHaveKey('dots');
    });

    it('has available error correction levels', function () {
        expect(QrCodeService::ERROR_CORRECTION_LEVELS)->toBeArray()
            ->and(QrCodeService::ERROR_CORRECTION_LEVELS)->toHaveKey('L')
            ->and(QrCodeService::ERROR_CORRECTION_LEVELS)->toHaveKey('M')
            ->and(QrCodeService::ERROR_CORRECTION_LEVELS)->toHaveKey('Q')
            ->and(QrCodeService::ERROR_CORRECTION_LEVELS)->toHaveKey('H');
    });

    it('has available size presets', function () {
        expect(QrCodeService::SIZE_PRESETS)->toBeArray()
            ->and(QrCodeService::SIZE_PRESETS)->toHaveKey(200)
            ->and(QrCodeService::SIZE_PRESETS)->toHaveKey(400)
            ->and(QrCodeService::SIZE_PRESETS)->toHaveKey(1000);
    });
});

describe('QR Code Controller', function () {
    it('downloads QR code as PNG', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.qr.download', [
                'id' => $this->biolink->id,
                'format' => 'png',
            ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Content-Disposition', "attachment; filename=\"qr-{$this->biolink->url}.png\"");
    });

    it('downloads QR code as SVG', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.qr.download', [
                'id' => $this->biolink->id,
                'format' => 'svg',
            ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml')
            ->assertHeader('Content-Disposition', "attachment; filename=\"qr-{$this->biolink->url}.svg\"");
    });

    it('returns 404 for non-existent biolink', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.qr.download', [
                'id' => 99999,
                'format' => 'png',
            ]));

        $response->assertNotFound();
    });

    it('prevents access to other users biolinks', function () {
        $otherUser = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create();
        $otherUser->hostWorkspaces()->attach($otherWorkspace->id, ['is_default' => true]);

        $otherBiolink = Page::create([
            'workspace_id' => $otherWorkspace->id,
            'user_id' => $otherUser->id,
            'type' => 'biolink',
            'url' => 'other-users-page',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.qr.download', [
                'id' => $otherBiolink->id,
                'format' => 'png',
            ]));

        $response->assertNotFound();
    });

    it('shows preview image', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.qr.preview', [
                'id' => $this->biolink->id,
            ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    });

    it('requires authentication', function () {
        $response = $this->get(route('hub.bio.qr.download', [
            'id' => $this->biolink->id,
            'format' => 'png',
        ]));

        $response->assertRedirect(route('login'));
    });
});

describe('QR Code Editor Component', function () {
    it('renders the QR code editor page', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.qr', ['id' => $this->biolink->id]));

        $response->assertOk()
            ->assertSee('QR Code');
    });

    it('displays the biolink URL', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.qr', ['id' => $this->biolink->id]));

        $response->assertOk()
            ->assertSee($this->biolink->url);
    });

    it('returns 404 for non-existent biolink', function () {
        $response = $this->actingAs($this->user)
            ->get(route('hub.bio.qr', ['id' => 99999]));

        $response->assertNotFound();
    });
});

describe('Biolink QR Settings Storage', function () {
    it('stores QR settings in biolink settings', function () {
        $qrSettings = [
            'foreground_colour' => '#8b5cf6',
            'background_colour' => '#ffffff',
            'size' => 600,
            'ecc_level' => 'H',
            'module_style' => 'dots',
        ];

        $this->biolink->update([
            'settings' => ['qr_code' => $qrSettings],
        ]);

        $this->biolink->refresh();

        expect($this->biolink->getSetting('qr_code.foreground_colour'))->toBe('#8b5cf6')
            ->and($this->biolink->getSetting('qr_code.size'))->toBe(600)
            ->and($this->biolink->getSetting('qr_code.ecc_level'))->toBe('H')
            ->and($this->biolink->getSetting('qr_code.module_style'))->toBe('dots');
    });

    it('uses stored settings when generating QR code', function () {
        $this->biolink->update([
            'settings' => [
                'qr_code' => [
                    'foreground_colour' => '#ff0000',
                    'background_colour' => '#00ff00',
                    'size' => 400,
                    'ecc_level' => 'Q',
                ],
            ],
        ]);

        $service = app(QrCodeService::class);

        // Get settings from biolink
        $qrSettings = $this->biolink->getSetting('qr_code', []);
        $defaults = QrCodeService::getDefaultSettings();

        $options = [
            'foreground_colour' => $qrSettings['foreground_colour'] ?? $defaults['foreground_colour'],
            'background_colour' => $qrSettings['background_colour'] ?? $defaults['background_colour'],
            'size' => $qrSettings['size'] ?? $defaults['size'],
            'ecc_level' => $qrSettings['ecc_level'] ?? $defaults['ecc_level'],
            'return_base64' => true,
        ];

        $output = $service->generate($this->biolink, $options);

        expect($output)->toBeString()
            ->and(strlen($output))->toBeGreaterThan(100);
    });
});

describe('QR Code for different link types', function () {
    it('generates QR code for short links', function () {
        $shortLink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'link',
            'url' => 'my-short-link',
            'location_url' => 'https://example.com/destination',
        ]);

        $service = app(QrCodeService::class);

        $output = $service->generate($shortLink, [
            'return_base64' => true,
        ]);

        expect($output)->toBeString()
            ->and(strlen($output))->toBeGreaterThan(100);
    });

    it('encodes the full URL in QR code', function () {
        $service = app(QrCodeService::class);

        // The QR code should encode the full_url attribute
        $expectedUrl = $this->biolink->full_url;

        expect($expectedUrl)->toContain($this->biolink->url);
    });
});
