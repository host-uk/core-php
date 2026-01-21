<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Theme;
use Core\Mod\Web\Models\ThemeFavourite;
use Core\Mod\Web\Services\ThemeService;
use Core\Mod\Tenant\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Theme Gallery component.
 *
 * Browse available themes, preview them, and apply to bio.
 * Supports system themes, premium themes, and custom user themes.
 */
#[Layout('hub::admin.layouts.app')]
class ThemeGallery extends Component
{
    // Filter state
    public string $filter = 'all'; // all, system, premium, custom, favourites

    public string $search = '';

    public string $category = ''; // Filter by category

    // Preview state
    public bool $showPreview = false;

    public ?int $previewThemeId = null;

    // Apply to biolink modal
    public bool $showApplyModal = false;

    public ?int $selectedThemeId = null;

    public ?int $selectedBiolinkId = null;

    // Create custom theme modal
    public bool $showCreateModal = false;

    public string $newThemeName = '';

    public array $newThemeSettings = [];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
        $this->newThemeSettings = Theme::getDefaultSettings();
    }

    /**
     * Check if user has premium theme access.
     */
    #[Computed]
    public function hasPremiumAccess(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return false;
        }

        $themeService = app(ThemeService::class);

        return $themeService->hasPremiumAccess($workspace);
    }

    /**
     * Get all available themes with filters applied.
     */
    #[Computed]
    public function themes()
    {
        $user = Auth::user();
        $query = Theme::active()->orderBy('sort_order');

        // Apply filter
        if ($this->filter === 'system') {
            $query->system();
        } elseif ($this->filter === 'premium') {
            $query->where('is_premium', true);
        } elseif ($this->filter === 'custom') {
            if ($user instanceof User) {
                $query->where('user_id', $user->id);
            } else {
                return collect();
            }
        } elseif ($this->filter === 'favourites') {
            if ($user instanceof User) {
                $query->whereHas('favouritedBy', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            } else {
                return collect();
            }
        }

        // Apply category filter
        if ($this->category) {
            $query->category($this->category);
        }

        // Apply search
        if ($this->search) {
            $query->search($this->search);
        }

        // Include favourite status for authenticated users
        if ($user instanceof User) {
            $query->withFavouriteStatus($user);
        }

        return $query->get();
    }

    /**
     * Get user's custom themes.
     */
    #[Computed]
    public function customThemes()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return collect();
        }

        return Theme::where('user_id', $user->id)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get user's biolinks for the apply modal.
     */
    #[Computed]
    public function biolinks()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return collect();
        }

        return Page::where('user_id', $user->id)
            ->where('type', 'biolink')
            ->orderBy('url')
            ->get(['id', 'url', 'theme_id']);
    }

    /**
     * Get the theme being previewed.
     */
    #[Computed]
    public function previewTheme(): ?Theme
    {
        if (! $this->previewThemeId) {
            return null;
        }

        return Theme::find($this->previewThemeId);
    }

    /**
     * Get the selected theme for applying.
     */
    #[Computed]
    public function selectedTheme(): ?Theme
    {
        if (! $this->selectedThemeId) {
            return null;
        }

        return Theme::find($this->selectedThemeId);
    }

    /**
     * Open preview for a theme.
     */
    public function preview(int $themeId): void
    {
        $this->previewThemeId = $themeId;
        $this->showPreview = true;
    }

    /**
     * Close preview.
     */
    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewThemeId = null;
    }

    /**
     * Open apply modal for a theme.
     */
    public function openApplyModal(int $themeId): void
    {
        $theme = Theme::find($themeId);

        if (! $theme) {
            return;
        }

        // Check premium access
        if ($theme->is_premium && ! $this->hasPremiumAccess) {
            $this->dispatch('notify', message: 'This theme requires a Pro or Ultimate plan.', type: 'error');

            return;
        }

        $this->selectedThemeId = $themeId;
        $this->selectedBiolinkId = null;
        $this->showApplyModal = true;
    }

    /**
     * Close apply modal.
     */
    public function closeApplyModal(): void
    {
        $this->showApplyModal = false;
        $this->selectedThemeId = null;
        $this->selectedBiolinkId = null;
    }

    /**
     * Apply theme to selected bio.
     */
    public function applyTheme(): void
    {
        if (! $this->selectedThemeId || ! $this->selectedBiolinkId) {
            $this->dispatch('notify', message: 'Please select a bio.', type: 'error');

            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $biolink = Page::where('user_id', $user->id)->find($this->selectedBiolinkId);
        $theme = Theme::find($this->selectedThemeId);

        if (! $biolink || ! $theme) {
            $this->dispatch('notify', message: 'Biolink or theme not found.', type: 'error');

            return;
        }

        // Check premium access again
        if ($theme->is_premium && ! $this->hasPremiumAccess) {
            $this->dispatch('notify', message: 'This theme requires a Pro or Ultimate plan.', type: 'error');

            return;
        }

        $themeService = app(ThemeService::class);
        $themeService->applyTheme($biolink, $theme->id);

        $this->closeApplyModal();
        $this->dispatch('notify', message: 'Theme applied to '.$biolink->url, type: 'success');
    }

    /**
     * Apply theme to all bio.
     */
    public function applyToAll(): void
    {
        if (! $this->selectedThemeId) {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $theme = Theme::find($this->selectedThemeId);

        if (! $theme) {
            return;
        }

        // Check premium access
        if ($theme->is_premium && ! $this->hasPremiumAccess) {
            $this->dispatch('notify', message: 'This theme requires a Pro or Ultimate plan.', type: 'error');

            return;
        }

        $themeService = app(ThemeService::class);

        $biolinks = Page::where('user_id', $user->id)
            ->where('type', 'biolink')
            ->get();

        foreach ($biolinks as $biolink) {
            $themeService->applyTheme($biolink, $theme->id);
        }

        $this->closeApplyModal();
        $this->dispatch('notify', message: 'Theme applied to all biolinks', type: 'success');
    }

    /**
     * Open create custom theme modal.
     */
    public function openCreateModal(): void
    {
        $this->newThemeName = '';
        $this->newThemeSettings = Theme::getDefaultSettings();
        $this->showCreateModal = true;
    }

    /**
     * Close create modal.
     */
    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->newThemeName = '';
        $this->resetValidation();
    }

    /**
     * Create a custom theme.
     */
    public function createTheme(): void
    {
        $this->validate([
            'newThemeName' => ['required', 'string', 'max:64'],
        ], [
            'newThemeName.required' => 'Please enter a name for your theme.',
        ]);

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $themeService = app(ThemeService::class);
        $theme = $themeService->createCustomTheme(
            $user,
            $this->newThemeName,
            $this->newThemeSettings,
            $user->defaultHostWorkspace()
        );

        $this->closeCreateModal();
        $this->dispatch('notify', message: 'Custom theme created', type: 'success');
    }

    /**
     * Delete a custom theme.
     */
    public function deleteTheme(int $themeId): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $theme = Theme::where('id', $themeId)
            ->where('user_id', $user->id)
            ->first();

        if (! $theme) {
            $this->dispatch('notify', message: 'Theme not found or you do not own it.', type: 'error');

            return;
        }

        $themeService = app(ThemeService::class);
        $themeService->deleteCustomTheme($theme);

        $this->dispatch('notify', message: 'Theme deleted', type: 'success');
    }

    /**
     * Generate CSS preview for a theme.
     */
    public function getThemePreviewStyle(Theme $theme): string
    {
        $settings = $theme->settings;
        $bg = $settings['background'] ?? [];

        $background = match ($bg['type'] ?? 'color') {
            'gradient' => "linear-gradient(135deg, {$bg['gradient_start']}, {$bg['gradient_end']})",
            'image' => "url('{$bg['image_url']}') center/cover",
            default => $bg['color'] ?? '#ffffff',
        };

        return "background: {$background};";
    }

    /**
     * Set filter.
     */
    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    /**
     * Set category filter.
     */
    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    /**
     * Clear category filter.
     */
    public function clearCategory(): void
    {
        $this->category = '';
    }

    /**
     * Toggle favourite status for a theme.
     */
    public function toggleFavourite(int $themeId): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            $this->dispatch('notify', message: 'You must be logged in to favourite themes.', type: 'error');

            return;
        }

        $theme = Theme::find($themeId);

        if (! $theme) {
            $this->dispatch('notify', message: 'Theme not found.', type: 'error');

            return;
        }

        $isFavourited = ThemeFavourite::toggle($user, $themeId);

        $message = $isFavourited
            ? "Added {$theme->name} to favourites"
            : "Removed {$theme->name} from favourites";

        $this->dispatch('notify', message: $message, type: 'success');
    }

    /**
     * Get available categories.
     */
    #[Computed]
    public function categories(): array
    {
        return Theme::getCategories();
    }

    public function render()
    {
        return view('webpage::admin.theme-gallery')
            ->title('Theme Gallery');
    }
}
