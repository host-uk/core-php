<?php

namespace Core\Mod\Web\Mcp;

use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\Workspace;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

/**
 * MCP Resource for biolink pages.
 *
 * Part of TASK-011 Phase 12: MCP Tools Expansion for BioHost.
 *
 * URI format: biolink://{workspace}/{slug}
 * Returns biolink data as markdown for AI context.
 */
class BioResource extends Resource implements HasUriTemplate
{
    protected string $name = 'biolink';

    protected string $title = 'Bio Link Pages';

    protected string $description = 'Bio link pages with blocks, settings, and analytics summary - returns markdown for AI context';

    protected string $mimeType = 'text/markdown';

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('biolink://{workspace}/{slug}');
    }

    public function handle(Request $request): Response
    {
        $uri = $request->get('uri', '');

        // Parse URI: biolink://{workspace}/{slug}
        if (! str_starts_with($uri, 'biolink://')) {
            return Response::text('Invalid URI format. Expected: biolink://{workspace}/{slug}');
        }

        $path = substr($uri, 10); // Remove 'biolink://'
        $parts = explode('/', $path, 2);

        if (count($parts) < 2) {
            return Response::text('Invalid URI format. Expected: biolink://{workspace}/{slug}');
        }

        [$workspaceSlug, $biolinkSlug] = $parts;

        // Resolve workspace
        $workspace = Workspace::where('slug', $workspaceSlug)
            ->orWhere('id', $workspaceSlug)
            ->first();

        if (! $workspace) {
            return Response::text("Workspace not found: {$workspaceSlug}");
        }

        // Find biolink
        $biolink = Page::where('workspace_id', $workspace->id)
            ->where('url', $biolinkSlug)
            ->first();

        if (! $biolink) {
            // Try by ID
            if (is_numeric($biolinkSlug)) {
                $biolink = Page::where('workspace_id', $workspace->id)
                    ->find($biolinkSlug);
            }
        }

        if (! $biolink) {
            return Response::text("Biolink not found: {$biolinkSlug}");
        }

        // Load relationships
        $biolink->load(['blocks', 'project', 'domain', 'theme', 'pixels']);

        // Return as markdown with frontmatter
        $markdown = $this->biolinkToMarkdown($biolink, $workspace);

        return Response::text($markdown);
    }

    /**
     * Convert biolink to markdown with frontmatter.
     */
    protected function biolinkToMarkdown(Page $biolink, Workspace $workspace): string
    {
        $md = "---\n";
        $md .= "id: {$biolink->id}\n";
        $md .= "url: {$biolink->url}\n";
        $md .= "full_url: {$biolink->full_url}\n";
        $md .= "workspace: {$workspace->slug}\n";
        $md .= "type: {$biolink->type}\n";
        $md .= 'is_enabled: '.($biolink->is_enabled ? 'true' : 'false')."\n";

        if ($biolink->project) {
            $md .= "project: {$biolink->project->name}\n";
        }

        if ($biolink->domain) {
            $md .= "domain: {$biolink->domain->host}\n";
        }

        if ($biolink->theme) {
            $md .= "theme: {$biolink->theme->name}\n";
        }

        // Analytics summary
        $md .= "clicks: {$biolink->clicks}\n";
        $md .= "unique_clicks: {$biolink->unique_clicks}\n";

        if ($biolink->last_click_at) {
            $md .= 'last_click_at: '.$biolink->last_click_at->toIso8601String()."\n";
        }

        // SEO
        $seoTitle = $biolink->getSeoTitle();
        $seoDescription = $biolink->getSeoDescription();

        if ($seoTitle) {
            $md .= "seo_title: \"{$seoTitle}\"\n";
        }
        if ($seoDescription) {
            $md .= "seo_description: \"{$seoDescription}\"\n";
        }

        $md .= 'created_at: '.$biolink->created_at->toIso8601String()."\n";
        $md .= 'updated_at: '.$biolink->updated_at->toIso8601String()."\n";

        // Pixels
        if ($biolink->pixels->isNotEmpty()) {
            $pixelNames = $biolink->pixels->pluck('name')->toArray();
            $md .= 'pixels: ['.implode(', ', $pixelNames)."]\n";
        }

        $md .= "---\n\n";

        // Title
        $title = $seoTitle ?? $biolink->url;
        $md .= "# {$title}\n\n";

        // Description
        if ($seoDescription) {
            $md .= "> {$seoDescription}\n\n";
        }

        // Blocks section
        if ($biolink->blocks->isNotEmpty()) {
            $md .= "## Blocks\n\n";
            $md .= "| Order | Type | Name | Enabled | Clicks |\n";
            $md .= "|-------|------|------|---------|--------|\n";

            foreach ($biolink->blocks as $block) {
                $name = $this->getBlockName($block);
                $enabled = $block->is_enabled ? 'Yes' : 'No';
                $md .= "| {$block->order} | {$block->type} | {$name} | {$enabled} | {$block->clicks} |\n";
            }

            $md .= "\n";
        }

        // Block details
        if ($biolink->blocks->isNotEmpty()) {
            $md .= "### Block Details\n\n";

            foreach ($biolink->blocks as $block) {
                $name = $this->getBlockName($block);
                $md .= "#### {$block->order}. {$block->type}: {$name}\n\n";

                $settings = $block->settings ?? [];
                if (! empty($settings)) {
                    $md .= "```json\n";
                    $md .= json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    $md .= "\n```\n\n";
                }
            }
        }

        // Theme settings (if custom)
        if ($biolink->theme || $biolink->getSetting('theme')) {
            $md .= "## Theme\n\n";

            $themeSettings = $biolink->getThemeSettings();
            $md .= "- **Font**: {$biolink->getFontFamily()}\n";
            $md .= "- **Text colour**: {$biolink->getTextColor()}\n";

            $button = $biolink->getButtonStyle();
            $md .= "- **Button background**: {$button['background_color']}\n";
            $md .= "- **Button text**: {$button['text_color']}\n";

            $background = $biolink->getBackground();
            $md .= "- **Background type**: {$background['type']}\n";

            if ($background['type'] === 'color' && isset($background['color'])) {
                $md .= "- **Background colour**: {$background['color']}\n";
            }

            $md .= "\n";
        }

        return $md;
    }

    /**
     * Get a display name for a block.
     */
    protected function getBlockName(Block $block): string
    {
        $settings = $block->settings ?? [];

        return $settings['name']
            ?? $settings['title']
            ?? $settings['text']
            ?? ucfirst($block->type);
    }

    /**
     * Get list of available biolink resources.
     *
     * This is called when MCP lists available resources.
     */
    public static function list(): array
    {
        $resources = [];

        // Get all workspaces with biolinks
        $workspaces = Workspace::whereHas('bioLinks', function ($q) {
            $q->where('is_enabled', true);
        })->get();

        foreach ($workspaces as $workspace) {
            // Get enabled biolinks for this workspace
            $biolinks = Page::where('workspace_id', $workspace->id)
                ->where('is_enabled', true)
                ->orderByDesc('updated_at')
                ->limit(50)
                ->get(['id', 'url', 'type', 'clicks', 'settings']);

            foreach ($biolinks as $biolink) {
                $title = $biolink->getSeoTitle() ?? $biolink->url;

                $resources[] = [
                    'uri' => "biolink://{$workspace->slug}/{$biolink->url}",
                    'name' => $title,
                    'description' => ucfirst($biolink->type).": {$title} ({$biolink->clicks} clicks)",
                    'mimeType' => 'text/markdown',
                ];
            }
        }

        return $resources;
    }
}
