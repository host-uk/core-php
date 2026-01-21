<?php

declare(strict_types=1);

namespace Core\Mod\Web\Controllers\Api;

use Core\Front\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Core\Mod\Api\Controllers\Concerns\HasApiResponses;
use Core\Mod\Api\Controllers\Concerns\ResolvesWorkspace;
use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Requests\ReorderBlocksRequest;
use Core\Mod\Web\Requests\StoreBlockRequest;
use Core\Mod\Web\Requests\UpdateBlockRequest;
use Core\Mod\Web\Resources\BlockResource;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;

/**
 * BioLink Block API controller.
 *
 * Provides CRUD operations for biolink blocks via REST API.
 * Supports both session auth and API key auth.
 */
class BlockController extends Controller
{
    use HasApiResponses;
    use ResolvesWorkspace;

    /**
     * List all blocks for a bio.
     *
     * GET /api/v1/biolinks/{biolink}/blocks
     */
    public function index(Request $request, Page $biolink): AnonymousResourceCollection|JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->accessDeniedResponse();
        }

        $blocks = $biolink->blocks()->orderBy('order')->get();

        return BlockResource::collection($blocks);
    }

    /**
     * Create a new block.
     *
     * POST /api/v1/biolinks/{biolink}/blocks
     */
    public function store(StoreBlockRequest $request, Page $biolink): BlockResource|JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->accessDeniedResponse();
        }

        $validated = $request->validated();

        // Validate block type exists
        $blockTypes = config('bio.block_types', []);
        if (! array_key_exists($validated['type'], $blockTypes)) {
            return $this->validationErrorResponse([
                'type' => ['Invalid block type.'],
            ]);
        }

        // Default order to end of list
        $order = $validated['order'] ?? ($biolink->blocks()->max('order') + 1);

        $block = $biolink->blocks()->create([
            'workspace_id' => $workspace->id,
            'type' => $validated['type'],
            'location_url' => $validated['location_url'] ?? null,
            'settings' => $validated['settings'] ?? [],
            'order' => $order,
            'is_enabled' => $validated['is_enabled'] ?? true,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        return new BlockResource($block);
    }

    /**
     * Get a single block.
     *
     * GET /api/v1/blocks/{block}
     */
    public function show(Request $request, Block $block): BlockResource|JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify block belongs to workspace
        if ($block->workspace_id !== $workspace->id) {
            return $this->accessDeniedResponse();
        }

        return new BlockResource($block);
    }

    /**
     * Update a block.
     *
     * PUT /api/v1/blocks/{block}
     */
    public function update(UpdateBlockRequest $request, Block $block): BlockResource|JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify block belongs to workspace
        if ($block->workspace_id !== $workspace->id) {
            return $this->accessDeniedResponse();
        }

        $validated = $request->validated();

        $block->update($validated);

        return new BlockResource($block);
    }

    /**
     * Delete a block.
     *
     * DELETE /api/v1/blocks/{block}
     */
    public function destroy(Request $request, Block $block): JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify block belongs to workspace
        if ($block->workspace_id !== $workspace->id) {
            return $this->accessDeniedResponse();
        }

        $biolink = $block->biolink;
        $order = $block->order;

        $block->delete();

        // Reorder remaining blocks
        $biolink->blocks()
            ->where('order', '>', $order)
            ->decrement('order');

        return response()->json(null, 204);
    }

    /**
     * Reorder blocks within a bio.
     *
     * POST /api/v1/biolinks/{biolink}/blocks/reorder
     */
    public function reorder(ReorderBlocksRequest $request, Page $biolink): JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->accessDeniedResponse();
        }

        $validated = $request->validated();
        $blockIds = $validated['order'];

        // Verify all block IDs belong to this biolink
        $validCount = $biolink->blocks()->whereIn('id', $blockIds)->count();

        if ($validCount !== count($blockIds)) {
            return $this->validationErrorResponse([
                'order' => ['One or more blocks do not belong to this bio.'],
            ]);
        }

        foreach ($blockIds as $position => $blockId) {
            $biolink->blocks()
                ->where('id', $blockId)
                ->update(['order' => $position]);
        }

        return $this->successResponse('Blocks reordered successfully.');
    }

    /**
     * Duplicate a block.
     *
     * POST /api/v1/biolinks/{biolink}/blocks/{block}/duplicate
     */
    public function duplicate(Request $request, Page $biolink, Block $block): BlockResource|JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->accessDeniedResponse();
        }

        // Verify block belongs to biolink
        if ($block->biolink_id !== $biolink->id) {
            return $this->notFoundResponse('Block');
        }

        // Shift blocks after this one
        $biolink->blocks()
            ->where('order', '>', $block->order)
            ->increment('order');

        $newBlock = $block->replicate();
        $newBlock->order = $block->order + 1;
        $newBlock->clicks = 0;
        $newBlock->save();

        return new BlockResource($newBlock);
    }

    /**
     * Get the current user's workspace.
     *
     * @deprecated Use resolveWorkspace() from ResolvesWorkspace trait
     */
    protected function getWorkspace(Request $request): ?Workspace
    {
        return $this->resolveWorkspace($request);
    }
}
