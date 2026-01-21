<?php

namespace Core\Mod\Web\Controllers\Web;

use Core\Front\Controller;
use Core\Mod\Web\Models\Block;
use Core\Mod\Web\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    /**
     * Display blocks for a bio.
     */
    public function index(Request $request, Page $biolink): JsonResponse
    {
        $this->authorizeUser($request, $biolink);

        $blocks = $biolink->blocks()->orderBy('order')->get();

        return response()->json($blocks);
    }

    /**
     * Store a new block.
     */
    public function store(Request $request, Page $biolink): JsonResponse
    {
        $this->authorizeUser($request, $biolink);

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:32'],
            'location_url' => ['nullable', 'url', 'max:512'],
            'settings' => ['nullable', 'array'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['sometimes', 'boolean'],
        ]);

        // Validate block type exists
        if (! array_key_exists($validated['type'], config('bio.block_types', []))) {
            return response()->json([
                'message' => 'Invalid block type.',
                'errors' => ['type' => ['Invalid block type.']],
            ], 422);
        }

        // Default order to end of list
        if (! isset($validated['order'])) {
            $validated['order'] = $biolink->blocks()->max('order') + 1;
        }

        $block = $biolink->blocks()->create([
            'workspace_id' => $biolink->workspace_id,
            'type' => $validated['type'],
            'location_url' => $validated['location_url'] ?? null,
            'settings' => $validated['settings'] ?? [],
            'order' => $validated['order'],
            'is_enabled' => $validated['is_enabled'] ?? true,
        ]);

        return response()->json($block, 201);
    }

    /**
     * Update a block.
     */
    public function update(Request $request, Page $biolink, Block $block): JsonResponse
    {
        $this->authorizeUser($request, $biolink);
        $this->authorizeBlock($biolink, $block);

        $validated = $request->validate([
            'location_url' => ['nullable', 'url', 'max:512'],
            'settings' => ['nullable', 'array'],
            'order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $block->update($validated);

        return response()->json($block);
    }

    /**
     * Delete a block.
     */
    public function destroy(Request $request, Page $biolink, Block $block): JsonResponse
    {
        $this->authorizeUser($request, $biolink);
        $this->authorizeBlock($biolink, $block);

        $block->delete();

        // Reorder remaining blocks
        $biolink->blocks()
            ->where('order', '>', $block->order)
            ->decrement('order');

        return response()->json(['message' => 'Block deleted']);
    }

    /**
     * Reorder blocks.
     */
    public function reorder(Request $request, Page $biolink): JsonResponse
    {
        $this->authorizeUser($request, $biolink);

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:biolink_blocks,id'],
        ]);

        $blockIds = $validated['order'];

        // Verify all block IDs belong to this biolink
        $validCount = $biolink->blocks()->whereIn('id', $blockIds)->count();

        if ($validCount !== count($blockIds)) {
            return response()->json([
                'message' => 'One or more blocks do not belong to this bio.',
                'errors' => ['order' => ['Invalid block IDs provided.']],
            ], 422);
        }

        foreach ($blockIds as $position => $blockId) {
            $biolink->blocks()
                ->where('id', $blockId)
                ->update(['order' => $position]);
        }

        return response()->json(['message' => 'Blocks reordered']);
    }

    /**
     * Duplicate a block.
     */
    public function duplicate(Request $request, Page $biolink, Block $block): JsonResponse
    {
        $this->authorizeUser($request, $biolink);
        $this->authorizeBlock($biolink, $block);

        // Shift blocks after this one
        $biolink->blocks()
            ->where('order', '>', $block->order)
            ->increment('order');

        $newBlock = $block->replicate();
        $newBlock->order = $block->order + 1;
        $newBlock->clicks = 0;
        $newBlock->save();

        return response()->json($newBlock, 201);
    }

    /**
     * Ensure user owns the bio.
     */
    protected function authorizeUser(Request $request, Page $biolink): void
    {
        if ($biolink->user_id !== $request->user()->id) {
            abort(403);
        }
    }

    /**
     * Ensure block belongs to bio.
     */
    protected function authorizeBlock(Page $biolink, Block $block): void
    {
        if ($block->biolink_id !== $biolink->id) {
            abort(404);
        }
    }
}
