<?php

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Template;
use Core\Mod\Web\Services\TemplateApplicator;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;
use Core\Mod\Tenant\Models\Feature;
use Core\Mod\Tenant\Models\Package;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create();
    $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);

    // Provision entitlements
    Feature::create([
        'code' => 'bio.templates',
        'name' => 'Templates',
        'type' => 'boolean',
    ]);

    $package = Package::create(['code' => 'default', 'name' => 'Default Package', 'is_base_package' => true]);
    $package->features()->attach(Feature::where('code', 'bio.templates')->first(), ['limit_value' => 1]);

    app(EntitlementService::class)->provisionPackage($this->workspace, 'default');

    $this->applicator = app(TemplateApplicator::class);
});

// Model Tests
describe('BioLinkTemplate Model', function () {
    it('can create a system template', function () {
        $template = Template::create([
            'name' => 'Test Template',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'heading', 'settings' => ['text' => 'Hello']],
            ],
            'settings_json' => [
                'theme' => ['background' => ['type' => 'color', 'color' => '#ffffff']],
            ],
            'is_system' => true,
            'is_premium' => false,
        ]);

        expect($template->name)->toBe('Test Template')
            ->and($template->isSystem())->toBeTrue()
            ->and($template->slug)->toBe('test-template');
    });

    it('auto-generates slug from name', function () {
        $template = Template::create([
            'name' => 'Creative Portfolio Template',
            'category' => 'creative',
            'blocks_json' => [],
            'settings_json' => [],
        ]);

        expect($template->slug)->toBe('creative-portfolio-template');
    });

    it('scopes to active templates', function () {
        Template::create([
            'name' => 'Active',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
            'is_active' => true,
        ]);

        Template::create([
            'name' => 'Inactive',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
            'is_active' => false,
        ]);

        $active = Template::active()->get();

        expect($active)->toHaveCount(1)
            ->and($active->first()->name)->toBe('Active');
    });

    it('scopes to specific category', function () {
        Template::create([
            'name' => 'Business Template',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
        ]);

        Template::create([
            'name' => 'Creative Template',
            'category' => 'creative',
            'blocks_json' => [],
            'settings_json' => [],
        ]);

        $business = Template::category('business')->get();

        expect($business)->toHaveCount(1)
            ->and($business->first()->name)->toBe('Business Template');
    });

    it('can search by name or description', function () {
        Template::create([
            'name' => 'Photography Portfolio',
            'description' => 'For photographers',
            'category' => 'creative',
            'blocks_json' => [],
            'settings_json' => [],
        ]);

        Template::create([
            'name' => 'Business Card',
            'description' => 'Professional template',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
        ]);

        $results = Template::search('photo')->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Photography Portfolio');
    });

    it('increments usage count', function () {
        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
            'usage_count' => 5,
        ]);

        $template->incrementUsage();

        expect($template->fresh()->usage_count)->toBe(6);
    });
});

// Placeholder Tests
describe('Template Placeholders', function () {
    it('extracts placeholder variables from blocks', function () {
        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'heading', 'settings' => ['text' => 'Hi, I\'m {{name}}']],
                ['type' => 'paragraph', 'settings' => ['text' => 'Based in {{city}}']],
                ['type' => 'link', 'settings' => ['url' => '{{website}}', 'text' => 'Visit Site']],
            ],
            'settings_json' => [
                'seo' => ['title' => '{{name}} - Portfolio'],
            ],
        ]);

        $variables = $template->getPlaceholderVariables();

        expect($variables)->toContain('name', 'city', 'website');
    });

    it('replaces placeholders in blocks', function () {
        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'heading', 'settings' => ['text' => 'Hi, I\'m {{name}}']],
                ['type' => 'paragraph', 'settings' => ['text' => 'Based in {{city}}']],
            ],
            'settings_json' => [],
        ]);

        $blocks = $template->getBlocksWithReplacements([
            'name' => 'John',
            'city' => 'London',
        ]);

        expect($blocks[0]['settings']['text'])->toBe('Hi, I\'m John')
            ->and($blocks[1]['settings']['text'])->toBe('Based in London');
    });

    it('replaces placeholders in settings', function () {
        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [
                'seo' => [
                    'title' => '{{name}} - {{title}}',
                    'description' => 'Connect with {{name}}',
                ],
            ],
        ]);

        $settings = $template->getSettingsWithReplacements([
            'name' => 'Jane',
            'title' => 'Designer',
        ]);

        expect($settings['seo']['title'])->toBe('Jane - Designer')
            ->and($settings['seo']['description'])->toBe('Connect with Jane');
    });

    it('uses default placeholder values when provided', function () {
        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
            'placeholders' => [
                'name' => 'Your Name',
                'city' => 'London',
            ],
        ]);

        $defaults = $template->getDefaultPlaceholders();

        expect($defaults)->toBe([
            'name' => 'Your Name',
            'city' => 'London',
        ]);
    });
});

// Template Applicator Service Tests
describe('TemplateApplicator Service', function () {
    it('applies template to biolink', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test',
        ]);

        $template = Template::create([
            'name' => 'Test Template',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'heading', 'order' => 1, 'settings' => ['text' => 'Welcome']],
                ['type' => 'link', 'order' => 2, 'settings' => ['url' => 'https://example.com', 'text' => 'Click']],
            ],
            'settings_json' => [
                'theme' => ['background' => ['type' => 'color', 'color' => '#ffffff']],
            ],
        ]);

        $success = $this->applicator->apply($biolink, $template);

        expect($success)->toBeTrue();

        $biolink->refresh();
        $blocks = $biolink->blocks;

        expect($blocks)->toHaveCount(2)
            ->and($blocks[0]->type)->toBe('heading')
            ->and($blocks[0]->settings['text'])->toBe('Welcome')
            ->and($blocks[1]->type)->toBe('link');
    });

    it('applies template with placeholder replacements', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test',
        ]);

        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'heading', 'order' => 1, 'settings' => ['text' => 'Hi, I\'m {{name}}']],
                ['type' => 'paragraph', 'order' => 2, 'settings' => ['text' => 'From {{city}}']],
            ],
            'settings_json' => [],
        ]);

        $success = $this->applicator->apply($biolink, $template, [
            'name' => 'Alice',
            'city' => 'Manchester',
        ]);

        expect($success)->toBeTrue();

        $blocks = $biolink->fresh()->blocks;

        expect($blocks[0]->settings['text'])->toBe('Hi, I\'m Alice')
            ->and($blocks[1]->settings['text'])->toBe('From Manchester');
    });

    it('replaces existing blocks when specified', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test',
        ]);

        // Create existing blocks
        $biolink->blocks()->create([
            'workspace_id' => $this->workspace->id,
            'type' => 'heading',
            'order' => 1,
            'settings' => ['text' => 'Old Heading'],
        ]);

        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'heading', 'order' => 1, 'settings' => ['text' => 'New Heading']],
            ],
            'settings_json' => [],
        ]);

        $this->applicator->apply($biolink, $template, [], true);

        $blocks = $biolink->fresh()->blocks;

        expect($blocks)->toHaveCount(1)
            ->and($blocks[0]->settings['text'])->toBe('New Heading');
    });

    it('keeps existing blocks when not replacing', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test',
        ]);

        // Create existing blocks
        $biolink->blocks()->create([
            'workspace_id' => $this->workspace->id,
            'type' => 'heading',
            'order' => 1,
            'settings' => ['text' => 'Existing'],
        ]);

        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'link', 'order' => 2, 'settings' => ['url' => 'https://example.com', 'text' => 'New']],
            ],
            'settings_json' => [],
        ]);

        $this->applicator->apply($biolink, $template, [], false);

        $blocks = $biolink->fresh()->blocks;

        expect($blocks)->toHaveCount(2);
    });

    it('increments template usage counter on apply', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test',
        ]);

        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
            'usage_count' => 0,
        ]);

        $this->applicator->apply($biolink, $template);

        expect($template->fresh()->usage_count)->toBe(1);
    });

    it('creates new biolink from template', function () {
        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'heading', 'order' => 1, 'settings' => ['text' => 'Welcome']],
            ],
            'settings_json' => [],
        ]);

        $biolink = $this->applicator->createFromTemplate(
            $this->user,
            $template,
            'new-biolink'
        );

        expect($biolink)->not->toBeNull()
            ->and($biolink->url)->toBe('new-biolink')
            ->and($biolink->blocks)->toHaveCount(1);
    });

    it('creates biolink with placeholder values', function () {
        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'heading', 'order' => 1, 'settings' => ['text' => 'Hi {{name}}']],
            ],
            'settings_json' => [],
        ]);

        $biolink = $this->applicator->createFromTemplate(
            $this->user,
            $template,
            'test-biolink',
            ['name' => 'Bob']
        );

        expect($biolink->blocks[0]->settings['text'])->toBe('Hi Bob');
    });

    it('previews template without creating records', function () {
        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'heading', 'settings' => ['text' => 'Hi {{name}}']],
            ],
            'settings_json' => [
                'seo' => ['title' => '{{name}} - Portfolio'],
            ],
        ]);

        $preview = $this->applicator->preview($template, ['name' => 'Charlie']);

        expect($preview['blocks'][0]['settings']['text'])->toBe('Hi Charlie')
            ->and($preview['settings']['seo']['title'])->toBe('Charlie - Portfolio');

        // Verify no blocks were created
        expect(\Core\Mod\Web\Models\Block::count())->toBe(0);
    });
});

// Template Availability Tests
describe('Template Availability', function () {
    it('returns available templates for user', function () {
        // System templates
        Template::create([
            'name' => 'System Template',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
            'is_system' => true,
        ]);

        // User custom template
        Template::create([
            'name' => 'My Template',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
            'is_system' => false,
            'user_id' => $this->user->id,
        ]);

        // Other user's template (should not appear)
        $otherUser = User::factory()->create();
        Template::create([
            'name' => 'Other Template',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
            'is_system' => false,
            'user_id' => $otherUser->id,
        ]);

        $templates = $this->applicator->getAvailableTemplates($this->user, $this->workspace);

        expect($templates)->toHaveCount(2);
    });

    it('filters templates by category', function () {
        Template::create([
            'name' => 'Business',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
            'is_system' => true,
        ]);

        Template::create([
            'name' => 'Creative',
            'category' => 'creative',
            'blocks_json' => [],
            'settings_json' => [],
            'is_system' => true,
        ]);

        $business = $this->applicator->getTemplatesByCategory('business', $this->user, $this->workspace);

        expect($business)->toHaveCount(1)
            ->and($business->first()->category)->toBe('business');
    });

    it('searches templates by query', function () {
        Template::create([
            'name' => 'Photography Portfolio',
            'description' => 'For photographers',
            'category' => 'creative',
            'blocks_json' => [],
            'settings_json' => [],
            'is_system' => true,
        ]);

        Template::create([
            'name' => 'Business Card',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
            'is_system' => true,
        ]);

        $results = $this->applicator->searchTemplates('photo', $this->user, $this->workspace);

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('Photography Portfolio');
    });
});

// Template Settings Tests
describe('Template Settings', function () {
    it('applies theme settings from template', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test',
        ]);

        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [
                'theme' => [
                    'background' => ['type' => 'gradient', 'gradient_start' => '#ff0000', 'gradient_end' => '#0000ff'],
                    'text_color' => '#ffffff',
                ],
            ],
        ]);

        $this->applicator->apply($biolink, $template);

        $biolink->refresh();
        $theme = $biolink->getSetting('theme');

        expect($theme['background']['type'])->toBe('gradient')
            ->and($theme['background']['gradient_start'])->toBe('#ff0000')
            ->and($theme['text_color'])->toBe('#ffffff');
    });

    it('applies SEO settings from template', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test',
        ]);

        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [
                'seo' => [
                    'title' => 'Test Title',
                    'description' => 'Test Description',
                ],
            ],
        ]);

        $this->applicator->apply($biolink, $template);

        $biolink->refresh();

        expect($biolink->getSeoTitle())->toBe('Test Title')
            ->and($biolink->getSeoDescription())->toBe('Test Description');
    });
});

// Edge Cases and Validation
describe('Template Edge Cases', function () {
    it('handles empty blocks gracefully', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test',
        ]);

        $template = Template::create([
            'name' => 'Empty',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
        ]);

        $success = $this->applicator->apply($biolink, $template);

        expect($success)->toBeTrue()
            ->and($biolink->fresh()->blocks)->toHaveCount(0);
    });

    it('handles invalid block type gracefully', function () {
        $biolink = Page::create([
            'workspace_id' => $this->workspace->id,
            'user_id' => $this->user->id,
            'type' => 'biolink',
            'url' => 'test',
        ]);

        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [
                ['settings' => ['text' => 'No type field']],
                ['type' => 'heading', 'order' => 1, 'settings' => ['text' => 'Valid']],
            ],
            'settings_json' => [],
        ]);

        $this->applicator->apply($biolink, $template);

        // Should only create valid block
        expect($biolink->fresh()->blocks)->toHaveCount(1)
            ->and($biolink->blocks[0]->type)->toBe('heading');
    });

    it('handles missing placeholder gracefully', function () {
        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [
                ['type' => 'heading', 'settings' => ['text' => 'Hi {{name}}, from {{city}}']],
            ],
            'settings_json' => [],
        ]);

        // Only provide one placeholder
        $blocks = $template->getBlocksWithReplacements(['name' => 'David']);

        // Unreplaced placeholder remains
        expect($blocks[0]['settings']['text'])->toBe('Hi David, from {{city}}');
    });

    it('returns null when creating biolink without workspace', function () {
        $userWithoutWorkspace = User::factory()->create();

        $template = Template::create([
            'name' => 'Test',
            'category' => 'business',
            'blocks_json' => [],
            'settings_json' => [],
        ]);

        $biolink = $this->applicator->createFromTemplate(
            $userWithoutWorkspace,
            $template,
            'test-url'
        );

        expect($biolink)->toBeNull();
    });
});

// Category Tests
describe('Template Categories', function () {
    it('returns all available categories', function () {
        $categories = Template::getCategories();

        expect($categories)->toBeArray()
            ->and($categories)->toHaveKey('business')
            ->and($categories)->toHaveKey('creative')
            ->and($categories)->toHaveKey('portfolio');
    });

    it('gets category display name', function () {
        $template = Template::create([
            'name' => 'Test',
            'category' => 'ecommerce',
            'blocks_json' => [],
            'settings_json' => [],
        ]);

        expect($template->getCategoryName())->toBe('E-commerce');
    });
});
