<?php

namespace Core\Mod\Web\Services;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Theme;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;
use Illuminate\Database\Eloquent\Collection;

class ThemeService
{
    public function __construct(
        protected EntitlementService $entitlements
    ) {}

    /**
     * Get all available themes for a user/workspace.
     *
     * Returns system themes plus user's custom themes.
     * Premium themes are included but marked as locked if user lacks entitlement.
     */
    public function getAvailableThemes(User $user, ?Workspace $workspace = null): Collection
    {
        $workspace ??= $user->defaultHostWorkspace();
        $hasPremiumAccess = $this->hasPremiumAccess($workspace);

        // Get system themes
        $systemThemes = Theme::system()
            ->active()
            ->orderBy('sort_order')
            ->get();

        // Get user's custom themes
        $customThemes = Theme::custom()
            ->active()
            ->where(function ($query) use ($user, $workspace) {
                $query->where('user_id', $user->id);
                if ($workspace) {
                    $query->orWhere('workspace_id', $workspace->id);
                }
            })
            ->orderBy('name')
            ->get();

        // Mark premium themes as locked if user lacks access
        $allThemes = $systemThemes->concat($customThemes);

        return $allThemes->map(function (Theme $theme) use ($hasPremiumAccess) {
            $theme->is_locked = $theme->is_premium && ! $hasPremiumAccess;

            return $theme;
        });
    }

    /**
     * Get system themes only.
     */
    public function getSystemThemes(): Collection
    {
        return Theme::system()
            ->active()
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get a theme by ID.
     */
    public function getTheme(int $themeId): ?Theme
    {
        return Theme::find($themeId);
    }

    /**
     * Get a theme by slug.
     */
    public function getThemeBySlug(string $slug): ?Theme
    {
        return Theme::where('slug', $slug)->first();
    }

    /**
     * Get the effective theme for a bio.
     *
     * Returns theme settings, falling back to default if no theme set.
     */
    public function getEffectiveTheme(Page $biolink): array
    {
        // If biolink has a theme assigned, use it
        if ($biolink->theme_id && $biolink->theme) {
            return $biolink->theme->settings->toArray();
        }

        // Check if biolink has inline theme settings
        $inlineTheme = $biolink->getSetting('theme');
        if ($inlineTheme && is_array($inlineTheme)) {
            return array_merge(Theme::getDefaultSettings(), $inlineTheme);
        }

        // Return default theme
        return Theme::getDefaultSettings();
    }

    /**
     * Apply a theme to a bio.
     */
    public function applyTheme(Page $biolink, int $themeId): bool
    {
        $theme = $this->getTheme($themeId);

        if (! $theme) {
            return false;
        }

        // Check if user has access to premium theme
        if ($theme->is_premium) {
            $workspace = $biolink->workspace ?? $biolink->user?->defaultHostWorkspace();
            if ($workspace && ! $this->hasPremiumAccess($workspace)) {
                return false;
            }
        }

        $biolink->theme_id = $themeId;
        $biolink->save();

        return true;
    }

    /**
     * Remove theme from a biolink (revert to default).
     */
    public function removeTheme(Page $biolink): void
    {
        $biolink->theme_id = null;
        $biolink->save();
    }

    /**
     * Create a custom theme for a user.
     */
    public function createCustomTheme(
        User $user,
        string $name,
        array $settings,
        ?Workspace $workspace = null
    ): Theme {
        return Theme::create([
            'user_id' => $user->id,
            'workspace_id' => $workspace?->id,
            'name' => $name,
            'settings' => array_merge(Theme::getDefaultSettings(), $settings),
            'is_system' => false,
            'is_premium' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Update a custom theme.
     */
    public function updateCustomTheme(Theme $theme, array $settings, ?string $name = null): bool
    {
        // Cannot update system themes
        if ($theme->is_system) {
            return false;
        }

        $theme->settings = array_merge($theme->settings->toArray(), $settings);

        if ($name) {
            $theme->name = $name;
        }

        return $theme->save();
    }

    /**
     * Delete a custom theme.
     */
    public function deleteCustomTheme(Theme $theme): bool
    {
        // Cannot delete system themes
        if ($theme->is_system) {
            return false;
        }

        // Remove theme from any biolinks using it
        Page::where('theme_id', $theme->id)->update(['theme_id' => null]);

        return $theme->delete();
    }

    /**
     * Duplicate a theme as a custom theme.
     */
    public function duplicateTheme(Theme $source, User $user, ?Workspace $workspace = null): Theme
    {
        return $this->createCustomTheme(
            $user,
            $source->name.' (Copy)',
            $source->settings->toArray(),
            $workspace
        );
    }

    /**
     * Generate CSS variables for a bio.
     */
    public function generateCssVariables(Page $biolink): array
    {
        $settings = $this->getEffectiveTheme($biolink);

        $background = $settings['background'] ?? [];
        $button = $settings['button'] ?? [];

        return [
            '--biolink-bg' => $background['color'] ?? '#ffffff',
            '--biolink-bg-type' => $background['type'] ?? 'color',
            '--biolink-bg-gradient-start' => $background['gradient_start'] ?? $background['color'] ?? '#ffffff',
            '--biolink-bg-gradient-end' => $background['gradient_end'] ?? $background['color'] ?? '#ffffff',
            '--biolink-text' => $settings['text_color'] ?? '#000000',
            '--biolink-btn-bg' => $button['background_color'] ?? '#000000',
            '--biolink-btn-text' => $button['text_color'] ?? '#ffffff',
            '--biolink-btn-radius' => $button['border_radius'] ?? '8px',
            '--biolink-btn-border-width' => $button['border_width'] ?? '0',
            '--biolink-btn-border-color' => $button['border_color'] ?? 'transparent',
            '--biolink-font' => "'".($settings['font_family'] ?? 'Inter')."', sans-serif",
        ];
    }

    /**
     * Generate inline CSS string for a bio.
     */
    public function generateCssString(Page $biolink): string
    {
        $variables = $this->generateCssVariables($biolink);

        return collect($variables)
            ->map(fn ($value, $key) => "{$key}: {$value}")
            ->implode('; ');
    }

    /**
     * Generate background CSS for a bio.
     */
    public function generateBackgroundCss(Page $biolink): string
    {
        $settings = $this->getEffectiveTheme($biolink);
        $background = $settings['background'] ?? [];
        $type = $background['type'] ?? 'color';

        return match ($type) {
            'gradient' => sprintf(
                'background: linear-gradient(135deg, %s, %s)',
                $background['gradient_start'] ?? '#ffffff',
                $background['gradient_end'] ?? '#ffffff'
            ),
            'image' => sprintf(
                "background: url('%s') center/cover fixed",
                $background['image_url'] ?? ''
            ),
            default => sprintf('background: %s', $background['color'] ?? '#ffffff'),
        };
    }

    /**
     * Get the Google Fonts import URL for a biolink's theme.
     */
    public function getFontImportUrl(Page $biolink): ?string
    {
        $settings = $this->getEffectiveTheme($biolink);
        $fontFamily = $settings['font_family'] ?? 'Inter';

        // System fonts don't need imports
        $systemFonts = ['system-ui', 'sans-serif', 'serif', 'monospace', 'Arial', 'Helvetica', 'Georgia', 'Times New Roman'];
        if (in_array($fontFamily, $systemFonts)) {
            return null;
        }

        // URL-encode font name for Google Fonts
        $encodedFont = urlencode($fontFamily);

        return "https://fonts.googleapis.com/css2?family={$encodedFont}:wght@400;500;600;700&display=swap";
    }

    /**
     * Check if workspace has premium theme access.
     */
    public function hasPremiumAccess(?Workspace $workspace): bool
    {
        if (! $workspace) {
            return false;
        }

        // Check the new specific premium themes entitlement first
        if ($this->entitlements->can($workspace, 'bio.themes.premium')->isAllowed()) {
            return true;
        }

        // Fallback to checking tier-based access (legacy)
        return $this->entitlements->can($workspace, 'bio.tier.pro')->isAllowed()
            || $this->entitlements->can($workspace, 'bio.tier.ultimate')->isAllowed();
    }

    /**
     * Get a subset of available Google Fonts for the theme editor.
     */
    public static function getAvailableFonts(): array
    {
        return [
            'Inter' => 'Inter',
            'Poppins' => 'Poppins',
            'Montserrat' => 'Montserrat',
            'Open Sans' => 'Open Sans',
            'Roboto' => 'Roboto',
            'Lato' => 'Lato',
            'Nunito' => 'Nunito',
            'Playfair Display' => 'Playfair Display',
            'Merriweather' => 'Merriweather',
            'Source Sans 3' => 'Source Sans 3',
            'Oswald' => 'Oswald',
            'Raleway' => 'Raleway',
            'DM Sans' => 'DM Sans',
            'Space Grotesk' => 'Space Grotesk',
            'Rubik' => 'Rubik',
            'Mukta' => 'Mukta',
            'Libre Baskerville' => 'Libre Baskerville',
            'Cormorant Garamond' => 'Cormorant Garamond',
            'Amiri' => 'Amiri',
            'system-ui' => 'System Default',
        ];
    }
}
