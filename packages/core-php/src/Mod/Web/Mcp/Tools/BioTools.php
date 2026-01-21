<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class BioTools extends BaseBioTool
{
    protected string $name = 'biolink_tools';

    protected string $description = 'Core operations for bio link pages: list, create, update, delete, and manage blocks';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');
        $userId = $request->get('user_id');

        return match ($action) {
            'list' => $this->listBioLinks($userId),
            'get' => $this->getBioLink($request->get('biolink_id')),
            'create' => $this->createBioLink($userId, $request),
            'update' => $this->updateBioLink($request),
            'delete' => $this->deleteBioLink($request->get('biolink_id')),
            'add_block' => $this->addBlock($request),
            'update_block' => $this->updateBlock($request),
            'delete_block' => $this->deleteBlock($request),
            default => $this->error('Invalid action', ['available_actions' => ['list', 'get', 'create', 'update', 'delete', 'add_block', 'update_block', 'delete_block']]),
        };
    }

    protected function listBioLinks(?int $userId): Response
    {
        $query = Page::query()
            ->with(['project', 'domain'])
            ->withCount('blocks');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $biolinks = $query->latest()->limit(50)->get();

        return $this->json($biolinks->map(fn (Page $link) => [
            'id' => $link->id,
            'url' => $link->url,
            'full_url' => $link->full_url,
            'type' => $link->type,
            'clicks' => $link->clicks,
            'blocks_count' => $link->blocks_count,
            'is_enabled' => $link->is_enabled,
            'project' => $link->project?->name,
            'domain' => $link->domain?->host,
            'created_at' => $link->created_at->toIso8601String(),
        ]));
    }

    protected function getBioLink(?int $biolinkId): Response
    {
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::with(['blocks', 'project', 'domain', 'pixels', 'theme'])
            ->find($biolinkId);

        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        return $this->json([
            'id' => $biolink->id,
            'url' => $biolink->url,
            'full_url' => $biolink->full_url,
            'type' => $biolink->type,
            'clicks' => $biolink->clicks,
            'unique_clicks' => $biolink->unique_clicks,
            'is_enabled' => $biolink->is_enabled,
            'settings' => $biolink->settings?->toArray(),
            'blocks' => $biolink->blocks->map(fn (Block $block) => [
                'id' => $block->id,
                'type' => $block->type,
                'order' => $block->order,
                'clicks' => $block->clicks,
                'is_enabled' => $block->is_enabled,
                'settings' => $block->settings,
            ]),
            'project' => $biolink->project ? [
                'id' => $biolink->project->id,
                'name' => $biolink->project->name,
            ] : null,
            'domain' => $biolink->domain ? [
                'id' => $biolink->domain->id,
                'host' => $biolink->domain->host,
            ] : null,
            'theme' => $biolink->theme ? [
                'id' => $biolink->theme->id,
                'name' => $biolink->theme->name,
            ] : null,
            'pixels' => $biolink->pixels->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'type' => $p->type,
            ]),
            'created_at' => $biolink->created_at->toIso8601String(),
        ]);
    }

    protected function createBioLink(?int $userId, Request $request): Response
    {
        if (! $userId) {
            return $this->error('user_id is required');
        }

        $url = $request->get('url');
        if (! $url) {
            return $this->error('url is required');
        }

        $title = $request->get('title', $url);
        $blocks = $request->get('blocks', []);

        $workspace = $this->getWorkspaceForUser($userId);
        if (! $workspace) {
            return $this->error('User has no workspace');
        }

        $biolink = Page::create([
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
            'type' => $request->get('type', 'biolink'),
            'url' => Str::slug($url),
            'location_url' => $request->get('location_url'),
            'project_id' => $request->get('project_id'),
            'domain_id' => $request->get('domain_id'),
            'theme_id' => $request->get('theme_id'),
            'settings' => [
                'seo' => ['title' => $title],
                'background' => ['type' => 'color', 'color' => '#ffffff'],
            ],
            'is_enabled' => true,
        ]);

        foreach ($blocks as $order => $blockData) {
            $biolink->blocks()->create([
                'workspace_id' => $workspace->id,
                'type' => $blockData['type'] ?? 'link',
                'settings' => $blockData['settings'] ?? [],
                'order' => $order,
                'is_enabled' => true,
            ]);
        }

        $biolink->load('blocks');

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolink->id,
            'url' => $biolink->url,
            'full_url' => $biolink->full_url,
            'blocks_created' => $biolink->blocks->count(),
        ]);
    }

    protected function updateBioLink(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $updateData = [];

        if ($request->has('url')) {
            $updateData['url'] = Str::slug($request->get('url'));
        }
        if ($request->has('is_enabled')) {
            $updateData['is_enabled'] = (bool) $request->get('is_enabled');
        }
        if ($request->has('project_id')) {
            $updateData['project_id'] = $request->get('project_id');
        }
        if ($request->has('domain_id')) {
            $updateData['domain_id'] = $request->get('domain_id');
        }
        if ($request->has('location_url')) {
            $updateData['location_url'] = $request->get('location_url');
        }
        if ($request->has('settings')) {
            $updateData['settings'] = array_merge(
                $biolink->settings?->toArray() ?? [],
                $request->get('settings')
            );
        }

        $biolink->update($updateData);

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolink->id,
            'url' => $biolink->url,
        ]);
    }

    protected function deleteBioLink(?int $biolinkId): Response
    {
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $url = $biolink->url;
        $biolink->delete();

        return $this->json([
            'ok' => true,
            'deleted_url' => $url,
        ]);
    }

    protected function addBlock(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $type = $request->get('block_type');
        if (! $type) {
            return $this->error('block_type is required');
        }

        $settings = $request->get('settings', []);
        $order = $biolink->blocks()->max('order') ?? 0;

        $block = $biolink->blocks()->create([
            'workspace_id' => $biolink->workspace_id,
            'type' => $type,
            'settings' => $settings,
            'order' => $order + 1,
            'is_enabled' => true,
        ]);

        return $this->json([
            'ok' => true,
            'block_id' => $block->id,
            'type' => $block->type,
            'order' => $block->order,
        ]);
    }

    protected function updateBlock(Request $request): Response
    {
        $blockId = $request->get('block_id');
        if (! $blockId) {
            return $this->error('block_id is required');
        }

        $block = Block::find($blockId);
        if (! $block) {
            return $this->error('Block not found');
        }

        $updateData = [];

        if ($request->has('settings')) {
            $existingSettings = $block->settings ?? [];
            if ($existingSettings instanceof \ArrayObject) {
                $existingSettings = $existingSettings->getArrayCopy();
            }
            $updateData['settings'] = array_merge(
                $existingSettings,
                $request->get('settings')
            );
        }
        if ($request->has('is_enabled')) {
            $updateData['is_enabled'] = (bool) $request->get('is_enabled');
        }
        if ($request->has('order')) {
            $updateData['order'] = (int) $request->get('order');
        }

        $block->update($updateData);

        return $this->json([
            'ok' => true,
            'block_id' => $block->id,
        ]);
    }

    protected function deleteBlock(Request $request): Response
    {
        $blockId = $request->get('block_id');
        if (! $blockId) {
            return $this->error('block_id is required');
        }

        $block = Block::find($blockId);
        if (! $block) {
            return $this->error('Block not found');
        }

        $block->delete();

        return $this->json([
            'ok' => true,
            'deleted_block_id' => $blockId,
        ]);
    }
}
