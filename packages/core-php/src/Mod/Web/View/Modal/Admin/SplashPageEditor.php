<?php

declare(strict_types=1);

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Splash page editor for configuring overlay pages shown before redirects.
 *
 * Splash pages can be used on short links to display a branded
 * interstitial before redirecting to the destination URL.
 */
class SplashPageEditor extends Component
{
    use WithFileUploads;

    public int $biolinkId;

    public bool $enabled = false;

    public string $title = '';

    public string $description = '';

    public string $buttonText = 'Continue';

    public string $backgroundColor = '#ffffff';

    public string $textColor = '#000000';

    public string $buttonColor = '#3b82f6';

    public string $buttonTextColor = '#ffffff';

    public ?string $logoUrl = null;

    public $logoFile = null;

    public int $autoRedirectDelay = 5;

    public bool $showTimer = true;

    /**
     * Mount the component.
     */
    public function mount(int $biolinkId): void
    {
        $biolink = Page::where('user_id', Auth::id())->findOrFail($biolinkId);

        $this->biolinkId = $biolink->id;

        // Load splash page settings from biolink settings
        $splash = $biolink->getSetting('splash_page', []);

        $this->enabled = (bool) ($splash['enabled'] ?? false);
        $this->title = $splash['title'] ?? '';
        $this->description = $splash['description'] ?? '';
        $this->buttonText = $splash['button_text'] ?? 'Continue';
        $this->backgroundColor = $splash['background_color'] ?? '#ffffff';
        $this->textColor = $splash['text_color'] ?? '#000000';
        $this->buttonColor = $splash['button_color'] ?? '#3b82f6';
        $this->buttonTextColor = $splash['button_text_color'] ?? '#ffffff';
        $this->logoUrl = $splash['logo_url'] ?? null;
        $this->autoRedirectDelay = (int) ($splash['auto_redirect_delay'] ?? 5);
        $this->showTimer = (bool) ($splash['show_timer'] ?? true);
    }

    /**
     * Save splash page settings.
     */
    public function save(): void
    {
        $this->validate([
            'title' => 'required_if:enabled,true|string|max:200',
            'description' => 'nullable|string|max:500',
            'buttonText' => 'required_if:enabled,true|string|max:50',
            'backgroundColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'textColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'buttonColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'buttonTextColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'autoRedirectDelay' => 'required|integer|min:0|max:30',
            'logoFile' => 'nullable|image|max:2048',
        ]);

        $biolink = Page::where('user_id', Auth::id())->findOrFail($this->biolinkId);

        // Handle logo upload
        $logoUrl = $this->logoUrl;
        if ($this->logoFile) {
            $path = $this->logoFile->store('biolink/splash-logos', 'public');
            $logoUrl = asset('storage/'.$path);
        }

        // Build splash page settings
        $splashSettings = [
            'enabled' => $this->enabled,
            'title' => $this->title,
            'description' => $this->description,
            'button_text' => $this->buttonText,
            'background_color' => $this->backgroundColor,
            'text_color' => $this->textColor,
            'button_color' => $this->buttonColor,
            'button_text_color' => $this->buttonTextColor,
            'logo_url' => $logoUrl,
            'auto_redirect_delay' => $this->autoRedirectDelay,
            'show_timer' => $this->showTimer,
        ];

        // Update biolink settings
        $settings = $biolink->settings ?? [];
        $settings['splash_page'] = $splashSettings;

        $biolink->update(['settings' => $settings]);

        // Update local logo URL
        $this->logoUrl = $logoUrl;
        $this->logoFile = null;

        $this->dispatch('notify', message: 'Splash page settings saved.', type: 'success');
    }

    /**
     * Remove the logo.
     */
    public function removeLogo(): void
    {
        $this->logoUrl = null;
        $this->logoFile = null;
    }

    /**
     * Get preview data for the splash page.
     */
    public function getPreviewDataProperty(): array
    {
        return [
            'title' => $this->title ?: 'Your Title Here',
            'description' => $this->description ?: 'Add a description for your splash page.',
            'button_text' => $this->buttonText ?: 'Continue',
            'background_color' => $this->backgroundColor,
            'text_color' => $this->textColor,
            'button_color' => $this->buttonColor,
            'button_text_color' => $this->buttonTextColor,
            'logo_url' => $this->logoUrl,
            'auto_redirect_delay' => $this->autoRedirectDelay,
            'show_timer' => $this->showTimer,
        ];
    }

    public function render()
    {
        return view('webpage::admin.splash-page-editor');
    }
}
