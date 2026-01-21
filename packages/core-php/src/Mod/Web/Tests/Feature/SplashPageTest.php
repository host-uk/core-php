<?php

namespace Core\Mod\Web\Tests\Feature;

use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SplashPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Workspace $workspace;

    protected Page $biolink;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->workspace = Workspace::factory()->create();
        $this->user->hostWorkspaces()->attach($this->workspace->id, ['is_default' => true]);

        $this->biolink = Page::factory()->create([
            'user_id' => $this->user->id,
            'workspace_id' => $this->workspace->id,
            'type' => 'link',
            'url' => 'test-splash',
            'location_url' => 'https://example.com',
        ]);
    }

    public function test_splash_page_editor_mounts_with_default_settings(): void
    {
        Livewire::actingAs($this->user)
            ->test('biolink.splash-page-editor', ['biolinkId' => $this->biolink->id])
            ->assertSet('enabled', false)
            ->assertSet('title', '')
            ->assertSet('autoRedirectDelay', 5)
            ->assertSet('showTimer', true);
    }

    public function test_splash_page_editor_loads_existing_settings(): void
    {
        $this->biolink->update([
            'settings' => [
                'splash_page' => [
                    'enabled' => true,
                    'title' => 'Welcome!',
                    'description' => 'You are being redirected...',
                    'button_text' => 'Continue',
                    'background_color' => '#ffffff',
                    'text_color' => '#000000',
                    'button_color' => '#3b82f6',
                    'button_text_color' => '#ffffff',
                    'auto_redirect_delay' => 10,
                    'show_timer' => false,
                ],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test('biolink.splash-page-editor', ['biolinkId' => $this->biolink->id])
            ->assertSet('enabled', true)
            ->assertSet('title', 'Welcome!')
            ->assertSet('description', 'You are being redirected...')
            ->assertSet('autoRedirectDelay', 10)
            ->assertSet('showTimer', false);
    }

    public function test_can_save_splash_page_settings(): void
    {
        Livewire::actingAs($this->user)
            ->test('biolink.splash-page-editor', ['biolinkId' => $this->biolink->id])
            ->set('enabled', true)
            ->set('title', 'Test Splash')
            ->set('description', 'Test description')
            ->set('buttonText', 'Go Now')
            ->set('autoRedirectDelay', 3)
            ->call('save')
            ->assertDispatched('notify');

        $this->biolink->refresh();
        $splash = $this->biolink->getSetting('splash_page');

        $this->assertTrue($splash['enabled']);
        $this->assertEquals('Test Splash', $splash['title']);
        $this->assertEquals('Test description', $splash['description']);
        $this->assertEquals('Go Now', $splash['button_text']);
        $this->assertEquals(3, $splash['auto_redirect_delay']);
    }

    public function test_requires_title_when_enabled(): void
    {
        Livewire::actingAs($this->user)
            ->test('biolink.splash-page-editor', ['biolinkId' => $this->biolink->id])
            ->set('enabled', true)
            ->set('title', '')
            ->call('save')
            ->assertHasErrors(['title']);
    }

    public function test_splash_page_view_renders_correctly(): void
    {
        $view = view('webpage::splash', [
            'biolink' => $this->biolink,
            'destination_url' => 'https://example.com',
            'title' => 'Welcome Page',
            'description' => 'You are being redirected',
            'button_text' => 'Continue',
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'button_color' => '#3b82f6',
            'button_text_color' => '#ffffff',
            'auto_redirect_delay' => 5,
            'show_timer' => true,
            'logo_url' => null,
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Welcome Page', $html);
        $this->assertStringContainsString('You are being redirected', $html);
        $this->assertStringContainsString('Continue', $html);
        $this->assertStringContainsString('Redirecting in', $html);
        $this->assertStringContainsString('https://example.com', $html);
    }

    public function test_splash_page_view_handles_disabled_timer(): void
    {
        $view = view('webpage::splash', [
            'biolink' => $this->biolink,
            'destination_url' => 'https://example.com',
            'title' => 'Welcome Page',
            'description' => '',
            'button_text' => 'Continue',
            'background_color' => '#ffffff',
            'text_color' => '#000000',
            'button_color' => '#3b82f6',
            'button_text_color' => '#ffffff',
            'auto_redirect_delay' => 0,
            'show_timer' => false,
            'logo_url' => null,
        ]);

        $html = $view->render();

        $this->assertStringContainsString('Welcome Page', $html);
        $this->assertStringNotContainsString('Redirecting in', $html);
    }
}
