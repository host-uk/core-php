<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Page;
use Core\Mod\Tenant\Models\User;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class StaticPageTools extends BaseBioTool
{
    protected string $name = 'static_page_tools';

    protected string $description = 'Manage static HTML bio pages: create, update, delete';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');
        $userId = $request->get('user_id');

        return match ($action) {
            'create' => $this->createStaticPage($userId, $request),
            'update' => $this->updateStaticPage($request),
            'delete' => $this->deleteStaticPage($request->get('biolink_id')),
            default => $this->error('Invalid action', ['available' => ['create', 'update', 'delete']]),
        };
    }

    protected function createStaticPage(?int $userId, Request $request): Response
    {
        if (! $userId) {
            return $this->error('user_id is required');
        }

        $url = $request->get('url');
        $html = $request->get('html');

        if (! $url) {
            return $this->error('url is required');
        }

        if (! $html) {
            return $this->error('html content is required');
        }

        $user = User::find($userId);
        $workspaceId = $user?->defaultHostWorkspace()?->id;

        if (! $workspaceId) {
            return $this->error('User has no workspace');
        }

        $biolink = Page::create([
            'workspace_id' => $workspaceId,
            'user_id' => $userId,
            'type' => 'static',
            'url' => Str::slug($url),
            'settings' => [
                'seo' => [
                    'title' => $request->get('title', $url),
                    'description' => $request->get('description', ''),
                ],
                'static' => [
                    'html' => $html,
                    'css' => $request->get('css', ''),
                    'js' => $request->get('js', ''),
                ],
            ],
            'is_enabled' => true,
        ]);

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolink->id,
            'url' => $biolink->url,
            'full_url' => $biolink->full_url,
            'type' => $biolink->type,
        ]);
    }

    protected function updateStaticPage(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');

        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        if (! $biolink->isStaticPage()) {
            return $this->error('This biolink is not a static page');
        }

        $settings = $biolink->settings?->toArray() ?? [];

        // Update static content
        if ($request->has('html')) {
            $settings['static']['html'] = $request->get('html');
        }
        if ($request->has('css')) {
            $settings['static']['css'] = $request->get('css');
        }
        if ($request->has('js')) {
            $settings['static']['js'] = $request->get('js');
        }

        // Update SEO
        if ($request->has('title')) {
            $settings['seo']['title'] = $request->get('title');
        }
        if ($request->has('description')) {
            $settings['seo']['description'] = $request->get('description');
        }

        $biolink->update(['settings' => $settings]);

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolink->id,
            'url' => $biolink->url,
        ]);
    }

    protected function deleteStaticPage(?int $biolinkId): Response
    {
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        if (! $biolink->isStaticPage()) {
            return $this->error('This biolink is not a static page');
        }

        $url = $biolink->url;
        $biolink->delete();

        return $this->json([
            'ok' => true,
            'deleted_url' => $url,
        ]);
    }
}
