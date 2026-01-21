<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Pixel;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class PixelTools extends BaseBioTool
{
    protected string $name = 'pixel_tools';

    protected string $description = 'Manage tracking pixels for analytics';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');
        $userId = $request->get('user_id');

        return match ($action) {
            'list' => $this->listPixels($userId),
            'create' => $this->createPixel($userId, $request),
            'update' => $this->updatePixel($request),
            'delete' => $this->deletePixel($request->get('pixel_id')),
            'attach' => $this->attachPixel($request),
            'detach' => $this->detachPixel($request),
            default => $this->error('Invalid action', ['available' => ['list', 'create', 'update', 'delete', 'attach', 'detach']]),
        };
    }

    protected function listPixels(?int $userId): Response
    {
        $workspace = $this->getWorkspaceForUser($userId);
        if (! $workspace) {
            return $this->error('User or workspace not found');
        }

        $pixels = Pixel::where('workspace_id', $workspace->id)
            ->withCount('biolinks')
            ->get();

        return $this->json([
            'pixels' => $pixels->map(fn (Pixel $pixel) => [
                'id' => $pixel->id,
                'name' => $pixel->name,
                'type' => $pixel->type,
                'type_label' => $pixel->type_label,
                'pixel_id' => $pixel->pixel_id,
                'biolinks_count' => $pixel->biolinks_count,
                'created_at' => $pixel->created_at->toIso8601String(),
            ]),
            'total' => $pixels->count(),
            'available_types' => Pixel::TYPES,
        ]);
    }

    protected function createPixel(?int $userId, Request $request): Response
    {
        $workspace = $this->getWorkspaceForUser($userId);
        if (! $workspace) {
            return $this->error('User or workspace not found');
        }

        $type = $request->get('type');
        $pixelId = $request->get('pixel_id');
        $name = $request->get('name');

        if (! $type || ! $pixelId) {
            return $this->error('type and pixel_id are required');
        }

        if (! array_key_exists($type, Pixel::TYPES)) {
            return $this->error('Invalid pixel type', ['available_types' => array_keys(Pixel::TYPES)]);
        }

        $pixel = Pixel::create([
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
            'type' => $type,
            'pixel_id' => $pixelId,
            'name' => $name ?? Pixel::TYPES[$type],
        ]);

        return $this->json([
            'ok' => true,
            'pixel_id' => $pixel->id,
            'name' => $pixel->name,
            'type' => $pixel->type,
        ]);
    }

    protected function updatePixel(Request $request): Response
    {
        $pixelId = $request->get('pixel_id');
        if (! $pixelId) {
            return $this->error('pixel_id is required');
        }

        $pixel = Pixel::find($pixelId);
        if (! $pixel) {
            return $this->error('Pixel not found');
        }

        $updateData = [];
        if ($request->has('name')) {
            $updateData['name'] = $request->get('name');
        }
        if ($request->has('tracking_id')) {
            $updateData['pixel_id'] = $request->get('tracking_id');
        }

        $pixel->update($updateData);

        return $this->json([
            'ok' => true,
            'pixel_id' => $pixel->id,
        ]);
    }

    protected function deletePixel(?int $pixelId): Response
    {
        if (! $pixelId) {
            return $this->error('pixel_id is required');
        }

        $pixel = Pixel::find($pixelId);
        if (! $pixel) {
            return $this->error('Pixel not found');
        }

        $name = $pixel->name;
        $pixel->biolinks()->detach();
        $pixel->delete();

        return $this->json([
            'ok' => true,
            'deleted_pixel' => $name,
        ]);
    }

    protected function attachPixel(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        $pixelId = $request->get('pixel_id');

        if (! $biolinkId || ! $pixelId) {
            return $this->error('biolink_id and pixel_id are required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $pixel = Pixel::find($pixelId);
        if (! $pixel) {
            return $this->error('Pixel not found');
        }

        $biolink->pixels()->syncWithoutDetaching([$pixelId]);

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolinkId,
            'pixel_id' => $pixelId,
            'pixel_name' => $pixel->name,
        ]);
    }

    protected function detachPixel(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        $pixelId = $request->get('pixel_id');

        if (! $biolinkId || ! $pixelId) {
            return $this->error('biolink_id and pixel_id are required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $biolink->pixels()->detach($pixelId);

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolinkId,
            'detached_pixel_id' => $pixelId,
        ]);
    }
}
