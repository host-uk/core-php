<?php

namespace Core\Mod\Web\Services;

use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Template;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;
use Illuminate\Support\Facades\DB;

class TemplateApplicator
{
    public function __construct(
        protected EntitlementService $entitlements
    ) {}

    /**
     * Apply a template to a bio.
     *
     * @param  BioLink  $biolink  The biolink to apply the template to
     * @param  Template  $template  The template to apply
     * @param  array  $placeholderValues  Values to replace placeholders (e.g., ['name' => 'John', 'city' => 'London'])
     * @param  bool  $replaceExisting  Whether to delete existing blocks (default: true)
     * @return bool Success status
     */
    public function apply(
        Page $biolink,
        Template $template,
        array $placeholderValues = [],
        bool $replaceExisting = true
    ): bool {
        // Check if user has access to premium templates
        if ($template->is_premium) {
            $workspace = $biolink->workspace ?? $biolink->user?->defaultHostWorkspace();
            if ($workspace && ! $this->hasPremiumAccess($workspace)) {
                return false;
            }
        }

        // Check templates entitlement
        $workspace = $biolink->workspace ?? $biolink->user?->defaultHostWorkspace();
        if ($workspace) {
            $check = $this->entitlements->can($workspace, 'bio.templates');
            if ($check->isDenied()) {
                return false;
            }
        }

        return DB::transaction(function () use ($biolink, $template, $placeholderValues, $replaceExisting) {
            // Merge default placeholders with provided values
            $replacements = array_merge($template->getDefaultPlaceholders(), $placeholderValues);

            // Delete existing blocks if requested
            if ($replaceExisting) {
                $biolink->blocks()->delete();
            }

            // Apply settings to biolink
            $settings = $template->getSettingsWithReplacements($replacements);
            $this->applySettings($biolink, $settings);

            // Create blocks from template
            $blocks = $template->getBlocksWithReplacements($replacements);
            $this->createBlocks($biolink, $blocks);

            // Increment template usage counter
            $template->incrementUsage();

            return true;
        });
    }

    /**
     * Apply template settings to a bio.
     *
     * Merges template settings into biolink settings, preserving any custom settings.
     */
    protected function applySettings(Page $biolink, array $templateSettings): void
    {
        // Get current settings
        $settings = $biolink->settings;
        $currentSettings = $settings ? $settings->toArray() : [];

        // Apply theme settings if present
        if (isset($templateSettings['theme'])) {
            $currentSettings['theme'] = $templateSettings['theme'];
        }

        // Apply SEO settings if present
        if (isset($templateSettings['seo'])) {
            $currentSettings['seo'] = array_merge(
                $currentSettings['seo'] ?? [],
                $templateSettings['seo']
            );
        }

        // Apply other template settings
        foreach ($templateSettings as $key => $value) {
            if (! in_array($key, ['theme', 'seo'])) {
                $currentSettings[$key] = $value;
            }
        }

        $biolink->settings = $currentSettings;
        $biolink->save();
    }

    /**
     * Create blocks from template block definitions.
     */
    protected function createBlocks(Page $biolink, array $blockDefinitions): void
    {
        $order = 1;

        foreach ($blockDefinitions as $blockDef) {
            // Ensure block has required fields
            if (! isset($blockDef['type'])) {
                continue;
            }

            // Create block with workspace scoping
            Block::create([
                'biolink_id' => $biolink->id,
                'workspace_id' => $biolink->workspace_id,
                'type' => $blockDef['type'],
                'order' => $blockDef['order'] ?? $order,
                'settings' => $blockDef['settings'] ?? [],
                'is_enabled' => $blockDef['is_enabled'] ?? true,
                'start_date' => $blockDef['start_date'] ?? null,
                'end_date' => $blockDef['end_date'] ?? null,
            ]);

            $order++;
        }
    }

    /**
     * Create a new biolink from a template.
     *
     * @param  User  $user  The user creating the biolink
     * @param  Template  $template  The template to use
     * @param  string  $url  The URL slug for the new biolink
     * @param  array  $placeholderValues  Values to replace placeholders
     */
    public function createFromTemplate(
        User $user,
        Template $template,
        string $url,
        array $placeholderValues = []
    ): ?Page {
        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return null;
        }

        // Check if user has access to premium templates
        if ($template->is_premium && ! $this->hasPremiumAccess($workspace)) {
            return null;
        }

        // Check templates entitlement
        $check = $this->entitlements->can($workspace, 'bio.templates');
        if ($check->isDenied()) {
            return null;
        }

        return DB::transaction(function () use ($user, $workspace, $template, $url, $placeholderValues) {
            // Create biolink
            $biolink = Page::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'type' => 'biolink',
                'url' => $url,
                'is_enabled' => true,
            ]);

            // Apply template
            $this->apply($biolink, $template, $placeholderValues, false);

            return $biolink;
        });
    }

    /**
     * Preview template by generating a temporary settings array.
     *
     * Returns processed blocks and settings without creating any database records.
     */
    public function preview(Template $template, array $placeholderValues = []): array
    {
        $replacements = array_merge($template->getDefaultPlaceholders(), $placeholderValues);

        return [
            'blocks' => $template->getBlocksWithReplacements($replacements),
            'settings' => $template->getSettingsWithReplacements($replacements),
        ];
    }

    /**
     * Get all available templates for a user.
     *
     * Returns system templates plus user's custom templates.
     * Premium templates are included but marked as locked if user lacks entitlement.
     */
    public function getAvailableTemplates(User $user, ?Workspace $workspace = null): \Illuminate\Database\Eloquent\Collection
    {
        $workspace ??= $user->defaultHostWorkspace();
        $hasPremiumAccess = $this->hasPremiumAccess($workspace);

        // Get system templates
        $systemTemplates = Template::system()
            ->active()
            ->orderBy('sort_order')
            ->get();

        // Get user's custom templates
        $customTemplates = Template::custom()
            ->active()
            ->where(function ($query) use ($user, $workspace) {
                $query->where('user_id', $user->id);
                if ($workspace) {
                    $query->orWhere('workspace_id', $workspace->id);
                }
            })
            ->orderBy('name')
            ->get();

        // Mark premium templates as locked if user lacks access
        $allTemplates = $systemTemplates->concat($customTemplates);

        return $allTemplates->map(function (Template $template) use ($hasPremiumAccess) {
            $template->is_locked = $template->is_premium && ! $hasPremiumAccess;

            return $template;
        });
    }

    /**
     * Get templates filtered by category.
     */
    public function getTemplatesByCategory(string $category, User $user, ?Workspace $workspace = null): \Illuminate\Database\Eloquent\Collection
    {
        $templates = $this->getAvailableTemplates($user, $workspace);

        return $templates->filter(fn (Template $template) => $template->category === $category);
    }

    /**
     * Search templates by name or description.
     */
    public function searchTemplates(string $query, User $user, ?Workspace $workspace = null): \Illuminate\Database\Eloquent\Collection
    {
        $workspace ??= $user->defaultHostWorkspace();

        $templates = Template::active()
            ->where(function ($q) use ($user, $workspace) {
                $q->where('is_system', true)
                    ->orWhere('user_id', $user->id)
                    ->orWhere('workspace_id', $workspace?->id);
            })
            ->search($query)
            ->orderBy('sort_order')
            ->get();

        $hasPremiumAccess = $this->hasPremiumAccess($workspace);

        return $templates->map(function (Template $template) use ($hasPremiumAccess) {
            $template->is_locked = $template->is_premium && ! $hasPremiumAccess;

            return $template;
        });
    }

    /**
     * Check if workspace has premium template access.
     */
    protected function hasPremiumAccess(?Workspace $workspace): bool
    {
        if (! $workspace) {
            return false;
        }

        return $this->entitlements->can($workspace, 'bio.tier.pro')->isAllowed()
            || $this->entitlements->can($workspace, 'bio.tier.ultimate')->isAllowed();
    }
}
