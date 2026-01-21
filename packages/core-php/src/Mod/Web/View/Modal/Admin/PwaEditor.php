<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Pwa;
use Core\Mod\Tenant\Services\EntitlementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * PWA editor component for bio.
 *
 * Allows creators to configure their biolink as an installable Progressive Web App.
 * Fans can install the biolink as an app on their device for a dedicated experience.
 */
class PwaEditor extends Component
{
    use WithFileUploads;

    public int $biolinkId;

    // PWA settings
    public ?int $pwaId = null;

    public string $name = '';

    public string $shortName = '';

    public string $description = '';

    public string $themeColor = '#6366f1';

    public string $backgroundColor = '#ffffff';

    public string $display = 'standalone';

    public string $orientation = 'any';

    public string $lang = 'en-GB';

    public string $dir = 'auto';

    public bool $isEnabled = true;

    // Icon uploads
    public $iconUpload = null;

    public $iconMaskableUpload = null;

    // Screenshots (up to 6)
    public array $screenshots = [];

    public $newScreenshot = null;

    // Shortcuts (up to 4)
    public array $shortcuts = [];

    public bool $addingShortcut = false;

    public string $newShortcutName = '';

    public string $newShortcutUrl = '';

    public string $newShortcutDescription = '';

    // Install prompt settings
    public int $installPromptDelay = 30;

    // UI state
    public bool $showPreview = false;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    /**
     * Validation rules.
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:128',
            'shortName' => 'nullable|string|max:32',
            'description' => 'nullable|string|max:256',
            'themeColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'backgroundColor' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'display' => 'required|in:standalone,fullscreen,minimal-ui,browser',
            'orientation' => 'required|in:any,natural,portrait,landscape',
            'lang' => 'required|string|max:8',
            'dir' => 'required|in:ltr,rtl,auto',
            'installPromptDelay' => 'required|integer|min:0|max:300',
            'iconUpload' => 'nullable|image|max:2048',
            'iconMaskableUpload' => 'nullable|image|max:2048',
            'newScreenshot' => 'nullable|image|max:5120',
        ];
    }

    /**
     * Mount the component.
     */
    public function mount(int $id): void
    {
        $biolink = Page::where('workspace_id', Auth::user()->defaultHostWorkspace()->id)
            ->findOrFail($id);

        $this->biolinkId = $biolink->id;

        // Load existing PWA config if it exists
        if ($biolink->pwa) {
            $pwa = $biolink->pwa;
            $this->pwaId = $pwa->id;
            $this->name = $pwa->name;
            $this->shortName = $pwa->short_name ?? '';
            $this->description = $pwa->description ?? '';
            $this->themeColor = $pwa->theme_color;
            $this->backgroundColor = $pwa->background_color;
            $this->display = $pwa->display;
            $this->orientation = $pwa->orientation;
            $this->lang = $pwa->lang;
            $this->dir = $pwa->dir;
            $this->isEnabled = $pwa->is_enabled;
            $this->screenshots = $pwa->screenshots ?? [];
            $this->shortcuts = $pwa->shortcuts ?? [];
        } else {
            // Initialize with sensible defaults from biolink
            $this->name = $biolink->getSetting('seo.title') ?? $biolink->url;
            $this->shortName = substr($this->name, 0, 12);
            $this->description = $biolink->getSetting('seo.description') ?? '';
        }

        // Load install prompt delay from biolink settings
        $this->installPromptDelay = $biolink->getSetting('pwa.install_prompt_delay', 30);
    }

    /**
     * Get the biolink model.
     */
    #[Computed]
    public function biolink(): ?Page
    {
        return Page::find($this->biolinkId);
    }

    /**
     * Get available display modes.
     */
    #[Computed]
    public function displayModes(): array
    {
        return [
            'standalone' => 'Standalone (recommended)',
            'fullscreen' => 'Full screen',
            'minimal-ui' => 'Minimal UI',
            'browser' => 'Browser',
        ];
    }

    /**
     * Get available orientations.
     */
    #[Computed]
    public function orientations(): array
    {
        return [
            'any' => 'Any',
            'natural' => 'Natural',
            'portrait' => 'Portrait',
            'landscape' => 'Landscape',
        ];
    }

    /**
     * Save PWA configuration.
     */
    public function save(): void
    {
        $this->validate();

        $workspace = Auth::user()->defaultHostWorkspace();

        // Check entitlement
        $entitlements = app(EntitlementService::class);
        $canUsePwa = $entitlements->can($workspace, 'bio.pwa');

        if ($canUsePwa->isDenied()) {
            $this->errorMessage = 'PWA feature not available on your plan. Please upgrade.';

            return;
        }

        $biolink = $this->biolink();

        // Handle icon uploads
        $iconUrl = null;
        $iconMaskableUrl = null;

        if ($this->iconUpload) {
            $path = $this->iconUpload->store('biolink-pwa-icons', 'public');
            $iconUrl = Storage::disk('public')->url($path);
        }

        if ($this->iconMaskableUpload) {
            $path = $this->iconMaskableUpload->store('biolink-pwa-icons', 'public');
            $iconMaskableUrl = Storage::disk('public')->url($path);
        }

        // Create or update PWA config
        $data = [
            'biolink_id' => $this->biolinkId,
            'name' => $this->name,
            'short_name' => $this->shortName ?: null,
            'description' => $this->description ?: null,
            'theme_color' => $this->themeColor,
            'background_color' => $this->backgroundColor,
            'display' => $this->display,
            'orientation' => $this->orientation,
            'lang' => $this->lang,
            'dir' => $this->dir,
            'screenshots' => $this->screenshots,
            'shortcuts' => $this->shortcuts,
            'is_enabled' => $this->isEnabled,
        ];

        // Add icon URLs if uploaded
        if ($iconUrl) {
            $data['icon_url'] = $iconUrl;
        }
        if ($iconMaskableUrl) {
            $data['icon_maskable_url'] = $iconMaskableUrl;
        }

        if ($this->pwaId) {
            $pwa = Pwa::find($this->pwaId);
            $pwa->update($data);
        } else {
            $pwa = Pwa::create($data);
            $this->pwaId = $pwa->id;
        }

        // Save install prompt delay to biolink settings
        $settings = $biolink->settings ?? [];
        $settings['pwa'] = [
            'install_prompt_delay' => $this->installPromptDelay,
        ];
        $biolink->update(['settings' => $settings]);

        $this->successMessage = 'PWA configuration saved successfully.';

        // Reset file uploads
        $this->iconUpload = null;
        $this->iconMaskableUpload = null;
    }

    /**
     * Add a screenshot.
     */
    public function addScreenshot(): void
    {
        $this->validate([
            'newScreenshot' => 'required|image|max:5120',
        ]);

        if (count($this->screenshots) >= 6) {
            $this->errorMessage = 'Maximum 6 screenshots allowed.';

            return;
        }

        // Upload screenshot
        $path = $this->newScreenshot->store('biolink-pwa-screenshots', 'public');
        $url = Storage::disk('public')->url($path);

        $this->screenshots[] = [
            'url' => $url,
            'platform' => 'narrow',
        ];

        $this->newScreenshot = null;
        $this->successMessage = 'Screenshot added.';
    }

    /**
     * Remove a screenshot.
     */
    public function removeScreenshot(int $index): void
    {
        if (isset($this->screenshots[$index])) {
            unset($this->screenshots[$index]);
            $this->screenshots = array_values($this->screenshots);
            $this->successMessage = 'Screenshot removed.';
        }
    }

    /**
     * Show add shortcut form.
     */
    public function showAddShortcut(): void
    {
        $this->addingShortcut = true;
        $this->newShortcutName = '';
        $this->newShortcutUrl = '';
        $this->newShortcutDescription = '';
    }

    /**
     * Cancel adding shortcut.
     */
    public function cancelAddShortcut(): void
    {
        $this->addingShortcut = false;
    }

    /**
     * Add a shortcut.
     */
    public function addShortcut(): void
    {
        $this->validate([
            'newShortcutName' => 'required|string|max:64',
            'newShortcutUrl' => 'required|url|max:512',
            'newShortcutDescription' => 'nullable|string|max:128',
        ]);

        if (count($this->shortcuts) >= 4) {
            $this->errorMessage = 'Maximum 4 shortcuts allowed.';

            return;
        }

        $this->shortcuts[] = [
            'name' => $this->newShortcutName,
            'url' => $this->newShortcutUrl,
            'description' => $this->newShortcutDescription,
        ];

        $this->addingShortcut = false;
        $this->successMessage = 'Shortcut added.';
    }

    /**
     * Remove a shortcut.
     */
    public function removeShortcut(int $index): void
    {
        if (isset($this->shortcuts[$index])) {
            unset($this->shortcuts[$index]);
            $this->shortcuts = array_values($this->shortcuts);
            $this->successMessage = 'Shortcut removed.';
        }
    }

    /**
     * Toggle preview.
     */
    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    /**
     * Disable PWA.
     */
    public function disable(): void
    {
        if ($this->pwaId) {
            $pwa = Pwa::find($this->pwaId);
            $pwa->update(['is_enabled' => false]);
            $this->isEnabled = false;
            $this->successMessage = 'PWA disabled.';
        }
    }

    /**
     * Enable PWA.
     */
    public function enable(): void
    {
        if ($this->pwaId) {
            $pwa = Pwa::find($this->pwaId);
            $pwa->update(['is_enabled' => true]);
            $this->isEnabled = true;
            $this->successMessage = 'PWA enabled.';
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('webpage::admin.pwa-editor');
    }
}
