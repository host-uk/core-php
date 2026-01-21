<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Theme;
use Core\Mod\Web\Services\ThemeService;
use Core\Mod\Tenant\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ThemeEditor extends Component
{
    // Theme data
    public ?int $themeId = null;

    public string $themeName = '';

    public array $settings = [];

    // Target biolink (if editing theme for specific biolink)
    public ?int $biolinkId = null;

    // UI state
    public bool $isEditing = false;

    public bool $showGallery = true;

    public string $activeTab = 'background';

    // Selected values for form
    public string $backgroundType = 'color';

    public string $backgroundColor = '#ffffff';

    public string $gradientStart = '#ffffff';

    public string $gradientEnd = '#000000';

    public string $textColor = '#000000';

    public string $buttonBgColor = '#000000';

    public string $buttonTextColor = '#ffffff';

    public string $buttonBorderRadius = '8px';

    public string $buttonBorderWidth = '0';

    public string $buttonBorderColor = '#000000';

    public string $fontFamily = 'Inter';

    /**
     * Mount the component.
     */
    public function mount(?int $biolinkId = null, ?int $themeId = null): void
    {
        $this->biolinkId = $biolinkId;

        if ($themeId) {
            $this->loadTheme($themeId);
        } elseif ($biolinkId) {
            $this->loadBiolinkTheme($biolinkId);
        } else {
            $this->resetToDefaults();
        }
    }

    /**
     * Load theme by ID.
     */
    public function loadTheme(int $themeId): void
    {
        $theme = Theme::find($themeId);

        if ($theme) {
            $this->themeId = $theme->id;
            $this->themeName = $theme->name;
            $this->settings = $theme->settings->toArray();
            $this->syncFormFromSettings();
            $this->isEditing = ! $theme->is_system;
        }
    }

    /**
     * Load theme from bio.
     */
    public function loadBiolinkTheme(int $biolinkId): void
    {
        $biolink = Page::find($biolinkId);

        if (! $biolink) {
            $this->resetToDefaults();

            return;
        }

        if ($biolink->theme_id && $biolink->theme) {
            $this->loadTheme($biolink->theme_id);
        } else {
            // Load inline settings or defaults
            $themeService = app(ThemeService::class);
            $this->settings = $themeService->getEffectiveTheme($biolink);
            $this->syncFormFromSettings();
            $this->themeId = null;
            $this->themeName = '';
        }
    }

    /**
     * Reset to default theme settings.
     */
    public function resetToDefaults(): void
    {
        $this->themeId = null;
        $this->themeName = '';
        $this->settings = Theme::getDefaultSettings();
        $this->syncFormFromSettings();
        $this->isEditing = true;
    }

    /**
     * Sync form fields from settings array.
     */
    protected function syncFormFromSettings(): void
    {
        $bg = $this->settings['background'] ?? [];
        $btn = $this->settings['button'] ?? [];

        $this->backgroundType = $bg['type'] ?? 'color';
        $this->backgroundColor = $bg['color'] ?? '#ffffff';
        $this->gradientStart = $bg['gradient_start'] ?? $bg['color'] ?? '#ffffff';
        $this->gradientEnd = $bg['gradient_end'] ?? '#000000';
        $this->textColor = $this->settings['text_color'] ?? '#000000';
        $this->buttonBgColor = $btn['background_color'] ?? '#000000';
        $this->buttonTextColor = $btn['text_color'] ?? '#ffffff';
        $this->buttonBorderRadius = $btn['border_radius'] ?? '8px';
        $this->buttonBorderWidth = $btn['border_width'] ?? '0';
        $this->buttonBorderColor = $btn['border_color'] ?? '#000000';
        $this->fontFamily = $this->settings['font_family'] ?? 'Inter';
    }

    /**
     * Sync settings array from form fields.
     */
    protected function syncSettingsFromForm(): void
    {
        $this->settings = [
            'background' => [
                'type' => $this->backgroundType,
                'color' => $this->backgroundColor,
                'gradient_start' => $this->gradientStart,
                'gradient_end' => $this->gradientEnd,
            ],
            'text_color' => $this->textColor,
            'button' => [
                'background_color' => $this->buttonBgColor,
                'text_color' => $this->buttonTextColor,
                'border_radius' => $this->buttonBorderRadius,
                'border_width' => $this->buttonBorderWidth,
                'border_color' => $this->buttonBorderColor,
            ],
            'font_family' => $this->fontFamily,
        ];
    }

    /**
     * Get available themes.
     */
    #[Computed]
    public function availableThemes()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return Theme::system()->active()->orderBy('sort_order')->get();
        }

        $themeService = app(ThemeService::class);

        return $themeService->getAvailableThemes($user);
    }

    /**
     * Get available font families.
     */
    #[Computed]
    public function fontFamilies(): array
    {
        return ThemeService::getAvailableFonts();
    }

    /**
     * Get border radius options.
     */
    #[Computed]
    public function borderRadiusOptions(): array
    {
        return [
            '0' => 'Square',
            '4px' => 'Slightly Rounded',
            '8px' => 'Rounded',
            '12px' => 'More Rounded',
            '24px' => 'Pill',
            '50px' => 'Full Pill',
        ];
    }

    /**
     * Select a preset theme.
     */
    public function selectTheme(int $themeId): void
    {
        $theme = Theme::find($themeId);

        if (! $theme) {
            return;
        }

        // Check if theme is locked (premium without access)
        if ($theme->is_premium) {
            $user = Auth::user();
            if ($user instanceof User) {
                $workspace = $user->defaultHostWorkspace();
                $themeService = app(ThemeService::class);
                if (! $themeService->hasPremiumAccess($workspace)) {
                    $this->dispatch('notify', message: 'This theme requires a Pro or Ultimate plan. Please upgrade to access.', type: 'error');

                    return;
                }
            }
        }

        $this->loadTheme($themeId);
        $this->showGallery = false;

        // Apply to biolink if one is selected
        if ($this->biolinkId) {
            $this->applyToBiolink();
        }

        $this->dispatch('theme-selected', themeId: $themeId);
    }

    /**
     * Start customising (opens editor).
     */
    public function startCustomising(): void
    {
        $this->showGallery = false;
        $this->isEditing = true;
    }

    /**
     * Back to theme gallery.
     */
    public function backToGallery(): void
    {
        $this->showGallery = true;
    }

    /**
     * Update settings when form values change.
     */
    public function updated($property): void
    {
        // Sync settings when any form field changes
        if (in_array($property, [
            'backgroundType', 'backgroundColor', 'gradientStart', 'gradientEnd',
            'textColor', 'buttonBgColor', 'buttonTextColor', 'buttonBorderRadius',
            'buttonBorderWidth', 'buttonBorderColor', 'fontFamily',
        ])) {
            $this->syncSettingsFromForm();
            $this->dispatch('theme-preview-updated', settings: $this->settings);
        }
    }

    /**
     * Apply current settings to bio.
     */
    public function applyToBiolink(): void
    {
        if (! $this->biolinkId) {
            return;
        }

        $biolink = Page::where('user_id', Auth::id())->find($this->biolinkId);

        if (! $biolink) {
            return;
        }

        $themeService = app(ThemeService::class);

        if ($this->themeId) {
            // Apply preset theme
            $themeService->applyTheme($biolink, $this->themeId);
        } else {
            // Apply custom inline settings
            $biolink->theme_id = null;
            $currentSettings = $biolink->settings ? $biolink->settings->toArray() : [];
            $currentSettings['theme'] = $this->settings;
            $biolink->settings = $currentSettings;
            $biolink->save();
        }

        $this->dispatch('notify', message: 'Theme applied', type: 'success');
        $this->dispatch('theme-applied', settings: $this->settings);
    }

    /**
     * Save as a custom theme.
     */
    public function saveAsCustomTheme(): void
    {
        $this->validate([
            'themeName' => ['required', 'string', 'max:64'],
        ]);

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $this->syncSettingsFromForm();

        $themeService = app(ThemeService::class);
        $theme = $themeService->createCustomTheme(
            $user,
            $this->themeName,
            $this->settings,
            $user->defaultHostWorkspace()
        );

        $this->themeId = $theme->id;
        $this->dispatch('notify', message: 'Custom theme saved', type: 'success');
        $this->dispatch('custom-theme-created', themeId: $theme->id);
    }

    /**
     * Update existing custom theme.
     */
    public function updateCustomTheme(): void
    {
        if (! $this->themeId) {
            return;
        }

        $theme = Theme::find($this->themeId);

        if (! $theme || $theme->is_system) {
            $this->dispatch('notify', message: 'Cannot update system themes', type: 'error');

            return;
        }

        // Verify ownership
        $user = Auth::user();
        if ($theme->user_id !== $user?->id) {
            $this->dispatch('notify', message: 'You do not own this theme', type: 'error');

            return;
        }

        $this->syncSettingsFromForm();

        $themeService = app(ThemeService::class);
        $themeService->updateCustomTheme($theme, $this->settings, $this->themeName ?: null);

        $this->dispatch('notify', message: 'Theme updated', type: 'success');
    }

    /**
     * Delete a custom theme.
     */
    public function deleteCustomTheme(int $themeId): void
    {
        $theme = Theme::find($themeId);

        if (! $theme || $theme->is_system) {
            return;
        }

        // Verify ownership
        $user = Auth::user();
        if ($theme->user_id !== $user?->id) {
            return;
        }

        $themeService = app(ThemeService::class);
        $themeService->deleteCustomTheme($theme);

        if ($this->themeId === $themeId) {
            $this->resetToDefaults();
        }

        $this->dispatch('notify', message: 'Theme deleted', type: 'success');
    }

    /**
     * Generate preview CSS for the current settings.
     */
    #[Computed]
    public function previewCss(): string
    {
        $bg = match ($this->backgroundType) {
            'gradient' => "linear-gradient(135deg, {$this->gradientStart}, {$this->gradientEnd})",
            default => $this->backgroundColor,
        };

        return "background: {$bg}; color: {$this->textColor};";
    }

    /**
     * Generate button preview CSS.
     */
    #[Computed]
    public function buttonPreviewCss(): string
    {
        $borderWidth = $this->buttonBorderWidth === '0' ? '0' : $this->buttonBorderWidth;

        return implode('; ', [
            "background: {$this->buttonBgColor}",
            "color: {$this->buttonTextColor}",
            "border-radius: {$this->buttonBorderRadius}",
            "border: {$borderWidth} solid {$this->buttonBorderColor}",
            "font-family: '{$this->fontFamily}', sans-serif",
        ]);
    }

    /**
     * Handle external theme selection (from parent component).
     */
    #[On('select-theme')]
    public function handleExternalThemeSelect(int $themeId): void
    {
        $this->selectTheme($themeId);
    }

    public function render()
    {
        return view('webpage::admin.theme-editor');
    }
}
