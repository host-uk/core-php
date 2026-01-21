<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\QrCodeService;

/**
 * QR code editor component for bio.
 *
 * Provides a visual interface for customising QR codes:
 * - Colour customisation (foreground/background)
 * - Logo embedding
 * - Error correction level
 * - Size selection
 * - Live preview
 * - Download in multiple formats
 */
class QrCodeEditor extends Component
{
    use WithFileUploads;

    public int $biolinkId;

    // QR settings
    public string $foregroundColour = '#000000';

    public string $backgroundColour = '#ffffff';

    public int $size = 400;

    public string $eccLevel = 'M';

    public string $moduleStyle = 'square';

    public ?string $logoPath = null;

    public int $logoSize = 20;

    // File upload for logo
    public $logoUpload = null;

    // UI state
    public bool $showPreview = true;

    public string $downloadFormat = 'png';

    /**
     * Mount the component.
     *
     * Accepts either numeric ID (from hub routes) or URL slug (from lt.hn routes).
     */
    public function mount(int|string $id): void
    {
        $biolink = $this->resolveBiolink($id);

        $this->biolinkId = $biolink->id;

        // Load existing QR settings from biolink
        $qrSettings = $biolink->getSetting('qr_code', []);
        $defaults = QrCodeService::getDefaultSettings();

        $this->foregroundColour = $qrSettings['foreground_colour'] ?? $defaults['foreground_colour'];
        $this->backgroundColour = $qrSettings['background_colour'] ?? $defaults['background_colour'];
        $this->size = $qrSettings['size'] ?? $defaults['size'];
        $this->eccLevel = $qrSettings['ecc_level'] ?? $defaults['ecc_level'];
        $this->moduleStyle = $qrSettings['module_style'] ?? $defaults['module_style'];
        $this->logoPath = $qrSettings['logo_path'] ?? $defaults['logo_path'];
        $this->logoSize = $qrSettings['logo_size'] ?? $defaults['logo_size'];
    }

    /**
     * Resolve biolink from ID or URL slug.
     */
    protected function resolveBiolink(int|string $id): Page
    {
        if (is_numeric($id)) {
            return Page::where('user_id', Auth::id())->findOrFail((int) $id);
        }

        return Page::where('url', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
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
     * Get available module styles.
     */
    #[Computed]
    public function moduleStyles(): array
    {
        return QrCodeService::MODULE_STYLES;
    }

    /**
     * Get available error correction levels.
     */
    #[Computed]
    public function eccLevels(): array
    {
        return QrCodeService::ERROR_CORRECTION_LEVELS;
    }

    /**
     * Get available size presets.
     */
    #[Computed]
    public function sizePresets(): array
    {
        return QrCodeService::SIZE_PRESETS;
    }

    /**
     * Get the current QR code settings.
     */
    #[Computed]
    public function currentSettings(): array
    {
        return [
            'foreground_colour' => $this->foregroundColour,
            'background_colour' => $this->backgroundColour,
            'size' => $this->size,
            'ecc_level' => $this->eccLevel,
            'module_style' => $this->moduleStyle,
            'logo_path' => $this->logoPath,
            'logo_size' => $this->logoSize,
            'return_base64' => true,
        ];
    }

    /**
     * Generate preview QR code.
     */
    #[Computed]
    public function previewQrCode(): ?string
    {
        $biolink = $this->biolink;

        if (! $biolink) {
            return null;
        }

        try {
            $service = app(QrCodeService::class);

            // Use smaller size for preview
            $previewSettings = $this->currentSettings;
            $previewSettings['size'] = min($this->size, 300);

            return $service->generatePreview($biolink, $previewSettings);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Get the public URL that will be encoded.
     */
    #[Computed]
    public function encodedUrl(): string
    {
        return $this->biolink?->full_url ?? '';
    }

    /**
     * Handle logo file upload.
     */
    public function updatedLogoUpload(): void
    {
        $this->validate([
            'logoUpload' => ['nullable', 'image', 'max:1024'], // 1MB max
        ]);

        if ($this->logoUpload) {
            // Store the uploaded logo
            $path = $this->logoUpload->store('qr-logos', 'public');
            $this->logoPath = 'storage://'.$path;
        }
    }

    /**
     * Remove the current logo.
     */
    public function removeLogo(): void
    {
        $this->logoPath = null;
        $this->logoUpload = null;
    }

    /**
     * Save QR settings to bio.
     */
    public function save(): void
    {
        // Validate settings
        $errors = QrCodeService::validateSettings($this->currentSettings);

        if (! empty($errors)) {
            foreach ($errors as $field => $message) {
                $this->addError($field, $message);
            }

            return;
        }

        $biolink = $this->biolink;

        if (! $biolink) {
            $this->dispatch('notify', message: 'Biolink not found', type: 'error');

            return;
        }

        // Update biolink settings
        $settings = $biolink->settings ?? [];
        $settings['qr_code'] = [
            'foreground_colour' => $this->foregroundColour,
            'background_colour' => $this->backgroundColour,
            'size' => $this->size,
            'ecc_level' => $this->eccLevel,
            'module_style' => $this->moduleStyle,
            'logo_path' => $this->logoPath,
            'logo_size' => $this->logoSize,
        ];

        $biolink->update(['settings' => $settings]);

        $this->dispatch('notify', message: 'QR code settings saved', type: 'success');
    }

    /**
     * Reset to default settings.
     */
    public function resetToDefaults(): void
    {
        $defaults = QrCodeService::getDefaultSettings();

        $this->foregroundColour = $defaults['foreground_colour'];
        $this->backgroundColour = $defaults['background_colour'];
        $this->size = $defaults['size'];
        $this->eccLevel = $defaults['ecc_level'];
        $this->moduleStyle = $defaults['module_style'];
        $this->logoPath = $defaults['logo_path'];
        $this->logoSize = $defaults['logo_size'];
        $this->logoUpload = null;

        $this->dispatch('notify', message: 'Settings reset to defaults', type: 'info');
    }

    /**
     * Swap foreground and background colours.
     */
    public function swapColours(): void
    {
        $temp = $this->foregroundColour;
        $this->foregroundColour = $this->backgroundColour;
        $this->backgroundColour = $temp;
    }

    /**
     * Set a preset colour scheme.
     */
    public function applyPreset(string $preset): void
    {
        $presets = [
            'classic' => ['#000000', '#ffffff'],
            'dark' => ['#ffffff', '#1a1a2e'],
            'brand-violet' => ['#8b5cf6', '#ffffff'],
            'brand-violet-dark' => ['#ffffff', '#8b5cf6'],
            'forest' => ['#1b4332', '#d8f3dc'],
            'ocean' => ['#023e8a', '#caf0f8'],
            'sunset' => ['#9d4edd', '#ffbe0b'],
            'monochrome' => ['#374151', '#f3f4f6'],
        ];

        if (isset($presets[$preset])) {
            [$this->foregroundColour, $this->backgroundColour] = $presets[$preset];
        }
    }

    /**
     * Get download URL for the QR code.
     */
    public function getDownloadUrl(string $format = 'png'): string
    {
        return route('hub.bio.qr.download', [
            'id' => $this->biolinkId,
            'format' => $format,
        ]);
    }

    public function render()
    {
        return view('webpage::admin.qr-code-editor')
            ->layout('client::layouts.app', [
                'title' => 'QR Code: /'.($this->biolink?->url ?? ''),
                'bioUrl' => $this->biolink?->url ?? '',
            ]);
    }
}
