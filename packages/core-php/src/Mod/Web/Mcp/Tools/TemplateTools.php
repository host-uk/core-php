<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Template;
use Core\Mod\Web\Services\TemplateApplicator;
use Core\Mod\Tenant\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class TemplateTools extends BaseBioTool
{
    protected string $name = 'template_tools';

    protected string $description = 'Manage templates for bio links: list, apply, preview';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');
        $userId = $request->get('user_id');

        return match ($action) {
            'list' => $this->listTemplates($userId),
            'apply' => $this->applyTemplate($request),
            'preview' => $this->previewTemplate($request),
            default => $this->error('Invalid action', ['available' => ['list', 'apply', 'preview']]),
        };
    }

    protected function listTemplates(?int $userId): Response
    {
        $user = $userId ? User::find($userId) : null;
        if (! $user) {
            return $this->error('user_id is required');
        }

        $workspace = $user->defaultHostWorkspace();
        $applicator = app(TemplateApplicator::class);

        $templates = $applicator->getAvailableTemplates($user, $workspace);

        return $this->json([
            'templates' => $templates->map(fn (Template $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'category' => $template->category,
                'description' => $template->description,
                'is_system' => $template->is_system,
                'is_premium' => $template->is_premium,
                'is_locked' => $template->is_locked ?? false,
                'usage_count' => $template->usage_count,
                'preview_image' => $template->preview_image,
                'tags' => $template->tags,
            ]),
            'total' => $templates->count(),
            'categories' => Template::getCategories(),
        ]);
    }

    protected function applyTemplate(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        $templateId = $request->get('template_id');

        if (! $biolinkId || ! $templateId) {
            return $this->error('biolink_id and template_id are required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $template = Template::find($templateId);
        if (! $template) {
            return $this->error('Template not found');
        }

        $applicator = app(TemplateApplicator::class);
        $placeholders = $request->get('placeholders', []);
        $replaceExisting = $request->get('replace_existing', true);

        $success = $applicator->apply($biolink, $template, $placeholders, $replaceExisting);

        if (! $success) {
            return $this->error('Unable to apply template. You may not have access to premium templates or templates feature.');
        }

        $biolink->refresh()->load('blocks');

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolink->id,
            'template_id' => $template->id,
            'template_name' => $template->name,
            'blocks_created' => $biolink->blocks->count(),
        ]);
    }

    protected function previewTemplate(Request $request): Response
    {
        $templateId = $request->get('template_id');

        if (! $templateId) {
            return $this->error('template_id is required');
        }

        $template = Template::find($templateId);
        if (! $template) {
            return $this->error('Template not found');
        }

        $applicator = app(TemplateApplicator::class);
        $placeholders = $request->get('placeholders', []);

        $preview = $applicator->preview($template, $placeholders);

        return $this->json([
            'template_id' => $template->id,
            'template_name' => $template->name,
            'preview' => $preview,
            'placeholders_available' => $template->getPlaceholderVariables(),
        ]);
    }
}
