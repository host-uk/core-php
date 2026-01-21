<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Theme;
use Core\Mod\Web\Services\ThemeService;
use Core\Mod\Tenant\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class ThemeTools extends BaseBioTool
{
    protected string $name = 'theme_tools';

    protected string $description = 'Manage themes for bio links: list, apply, create custom, delete';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');
        $userId = $request->get('user_id');

        return match ($action) {
            'list' => $this->listThemes($userId),
            'apply' => $this->applyTheme($request),
            'create_custom' => $this->createCustomTheme($userId, $request),
            'delete' => $this->deleteTheme($request->get('theme_id')),
            'search' => $this->searchThemes($request),
            'toggle_favourite' => $this->toggleFavouriteTheme($request),
            default => $this->error('Invalid action', ['available' => ['list', 'apply', 'create_custom', 'delete', 'search', 'toggle_favourite']]),
        };
    }

    protected function listThemes(?int $userId): Response
    {
        $user = $userId ? User::find($userId) : null;
        $workspace = $user?->defaultHostWorkspace();

        $themeService = app(ThemeService::class);

        if ($user) {
            $themes = $themeService->getAvailableThemes($user, $workspace);
        } else {
            $themes = $themeService->getSystemThemes();
        }

        return $this->json([
            'themes' => $themes->map(fn (Theme $theme) => [
                'id' => $theme->id,
                'name' => $theme->name,
                'slug' => $theme->slug,
                'is_system' => $theme->is_system,
                'is_premium' => $theme->is_premium,
                'is_locked' => $theme->is_locked ?? false,
                'settings' => $theme->settings?->toArray(),
            ]),
            'total' => $themes->count(),
        ]);
    }

    protected function applyTheme(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        $themeId = $request->get('theme_id');

        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $themeService = app(ThemeService::class);

        if ($themeId === null) {
            // Remove theme
            $themeService->removeTheme($biolink);

            return $this->json([
                'ok' => true,
                'biolink_id' => $biolinkId,
                'theme_id' => null,
                'message' => 'Theme removed, using default settings.',
            ]);
        }

        $success = $themeService->applyTheme($biolink, (int) $themeId);

        if (! $success) {
            return $this->error('Unable to apply theme. Theme may not exist or requires premium access.');
        }

        $biolink->refresh();

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolinkId,
            'theme_id' => $biolink->theme_id,
            'theme_name' => $biolink->theme?->name,
        ]);
    }

    protected function createCustomTheme(?int $userId, Request $request): Response
    {
        $user = $userId ? User::find($userId) : null;
        if (! $user) {
            return $this->error('user_id is required');
        }

        $name = $request->get('name');
        if (! $name) {
            return $this->error('name is required');
        }

        $settings = $request->get('settings', []);
        $workspace = $user->defaultHostWorkspace();

        $themeService = app(ThemeService::class);
        $theme = $themeService->createCustomTheme($user, $name, $settings, $workspace);

        return $this->json([
            'ok' => true,
            'theme_id' => $theme->id,
            'name' => $theme->name,
            'slug' => $theme->slug,
            'settings' => $theme->settings->toArray(),
        ]);
    }

    protected function deleteTheme(?int $themeId): Response
    {
        if (! $themeId) {
            return $this->error('theme_id is required');
        }

        $theme = Theme::find($themeId);
        if (! $theme) {
            return $this->error('Theme not found');
        }

        if ($theme->is_system) {
            return $this->error('Cannot delete system themes');
        }

        $themeService = app(ThemeService::class);
        $themeService->deleteCustomTheme($theme);

        return $this->json([
            'ok' => true,
            'deleted_theme' => $theme->name,
        ]);
    }

    protected function searchThemes(Request $request): Response
    {
        $userId = $request->get('user_id');
        $query = $request->get('query', '');
        $category = $request->get('category');

        $user = $userId ? User::find($userId) : null;
        $workspace = $user?->defaultHostWorkspace();

        $themeService = app(ThemeService::class);

        // Get available themes
        if ($user) {
            $themes = $themeService->getAvailableThemes($user, $workspace);
        } else {
            $themes = $themeService->getSystemThemes();
        }

        // Filter by search query
        if ($query) {
            $themes = $themes->filter(function (Theme $theme) use ($query) {
                return stripos($theme->name, $query) !== false
                    || stripos($theme->slug, $query) !== false
                    || stripos($theme->category ?? '', $query) !== false;
            });
        }

        // Filter by category
        if ($category) {
            $themes = $themes->filter(fn (Theme $theme) => ($theme->category ?? '') === $category);
        }

        $result = $themes->map(fn (Theme $theme) => [
            'id' => $theme->id,
            'name' => $theme->name,
            'slug' => $theme->slug,
            'category' => $theme->category,
            'is_system' => $theme->is_system,
            'is_premium' => $theme->is_premium,
            'is_locked' => $theme->is_locked ?? false,
            'is_favourite' => $theme->is_favourite ?? false,
            'settings' => $theme->settings?->toArray(),
        ]);

        return $this->json([
            'themes' => $result->values(),
            'total' => $result->count(),
            'query' => $query,
            'category' => $category,
        ]);
    }

    protected function toggleFavouriteTheme(Request $request): Response
    {
        $userId = $request->get('user_id');
        $themeId = $request->get('theme_id');

        if (! $userId || ! $themeId) {
            return $this->error('user_id and theme_id are required');
        }

        $user = User::find($userId);
        if (! $user) {
            return $this->error('User not found');
        }

        $theme = Theme::find($themeId);
        if (! $theme) {
            return $this->error('Theme not found');
        }

        // Toggle favourite in user preferences
        $preferences = $user->preferences ?? [];
        $favouriteThemes = $preferences['favourite_biolink_themes'] ?? [];

        $isFavourite = in_array($themeId, $favouriteThemes);

        if ($isFavourite) {
            $favouriteThemes = array_values(array_diff($favouriteThemes, [$themeId]));
        } else {
            $favouriteThemes[] = $themeId;
        }

        $preferences['favourite_biolink_themes'] = $favouriteThemes;
        $user->update(['preferences' => $preferences]);

        return $this->json([
            'ok' => true,
            'theme_id' => $themeId,
            'is_favourite' => ! $isFavourite,
        ]);
    }
}
