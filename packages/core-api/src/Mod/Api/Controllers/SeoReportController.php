<?php

declare(strict_types=1);

namespace Core\Mod\Api\Controllers;

use Core\Front\Controller;
use Core\Mod\Agentic\Jobs\ProcessContentTask;
use Core\Mod\Content\Models\ContentItem;
use Core\Mod\Content\Models\ContentTask;
use Core\Mod\Agentic\Models\Prompt;
use Core\Mod\Tenant\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SeoReportController extends Controller
{
    /**
     * Receive SEO report data from SEO PowerSuite or similar tools.
     */
    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url',
            'issues' => 'required|array',
            'suggestions' => 'nullable|array',
            'score' => 'nullable|integer|min:0|max:100',
            'workspace' => 'nullable|string',
        ]);

        // Find content item by URL
        $item = $this->findContentByUrl($validated['url'], $validated['workspace'] ?? null);

        if (! $item) {
            Log::info('SEO report received for unknown URL', [
                'url' => $validated['url'],
            ]);

            return response()->json([
                'status' => 'ignored',
                'reason' => 'Content not found for URL',
            ]);
        }

        // Update SEO metadata
        $item->updateSeo([
            'seo_score' => $validated['score'] ?? null,
            'seo_issues' => $validated['issues'],
            'seo_suggestions' => $validated['suggestions'] ?? [],
        ]);

        Log::info('SEO report processed', [
            'content_id' => $item->id,
            'score' => $validated['score'],
            'issue_count' => count($validated['issues']),
        ]);

        return response()->json([
            'status' => 'received',
            'content_id' => $item->id,
        ]);
    }

    /**
     * Get SEO issues for a workspace.
     */
    public function issues(Request $request, string $workspaceSlug): JsonResponse
    {
        $workspace = Workspace::where('slug', $workspaceSlug)->first();

        if (! $workspace) {
            return response()->json(['error' => 'Unknown workspace'], 404);
        }

        $minScore = $request->input('min_score');
        $maxScore = $request->input('max_score');
        $hasIssues = $request->boolean('has_issues', true);

        $items = ContentItem::query()
            ->where('workspace_id', $workspace->id)
            ->whereHas('seoMetadata', function ($query) use ($minScore, $maxScore, $hasIssues) {
                if ($hasIssues) {
                    $query->whereNotNull('seo_issues')
                        ->whereJsonLength('seo_issues', '>', 0);
                }

                if ($minScore !== null) {
                    $query->where('seo_score', '>=', (int) $minScore);
                }

                if ($maxScore !== null) {
                    $query->where('seo_score', '<=', (int) $maxScore);
                }
            })
            ->with('seoMetadata')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $items->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'slug' => $item->slug,
                'type' => $item->type,
                'status' => $item->status,
                'seo' => [
                    'score' => $item->seoMetadata?->seo_score,
                    'issue_count' => count($item->seoMetadata?->seo_issues ?? []),
                    'issues' => $item->seoMetadata?->seo_issues,
                    'suggestions' => $item->seoMetadata?->seo_suggestions,
                ],
                'updated_at' => $item->updated_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Generate an AI task to fix SEO issues.
     */
    public function generateTask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_item_id' => 'required|exists:content_items,id',
            'improvement_type' => 'required|in:title,description,content,schema,all',
        ]);

        $item = ContentItem::with('seoMetadata')->findOrFail($validated['content_item_id']);

        // Find the appropriate SEO prompt
        $promptName = match ($validated['improvement_type']) {
            'title' => 'seo-title-optimizer',
            'description' => 'seo-description-optimizer',
            'content' => 'seo-content-optimizer',
            'schema' => 'seo-schema-generator',
            'all' => 'seo-full-optimization',
        };

        $prompt = Prompt::where('name', $promptName)->active()->first();

        if (! $prompt) {
            return response()->json([
                'error' => "Prompt '{$promptName}' not found or inactive",
            ], 404);
        }

        // Create the task
        $task = ContentTask::create([
            'workspace_id' => $item->workspace_id,
            'prompt_id' => $prompt->id,
            'status' => ContentTask::STATUS_PENDING,
            'priority' => ContentTask::PRIORITY_NORMAL,
            'input_data' => [
                'title' => $item->title,
                'slug' => $item->slug,
                'excerpt' => $item->excerpt,
                'content' => $item->content_html_clean,
                'current_seo_title' => $item->seoMetadata?->title,
                'current_seo_description' => $item->seoMetadata?->description,
                'seo_issues' => $item->seoMetadata?->seo_issues ?? [],
                'seo_suggestions' => $item->seoMetadata?->seo_suggestions ?? [],
                'focus_keyword' => $item->seoMetadata?->focus_keyword,
            ],
            'target_type' => ContentItem::class,
            'target_id' => $item->id,
        ]);

        // Dispatch for processing
        ProcessContentTask::dispatch($task);

        Log::info('SEO improvement task created', [
            'task_id' => $task->id,
            'content_id' => $item->id,
            'type' => $validated['improvement_type'],
        ]);

        return response()->json([
            'success' => true,
            'task_id' => $task->id,
            'status' => 'queued',
        ]);
    }

    /**
     * Find content item by URL.
     */
    protected function findContentByUrl(string $url, ?string $workspaceSlug = null): ?ContentItem
    {
        // Extract slug from URL
        $path = parse_url($url, PHP_URL_PATH);
        $slug = basename($path);

        // Remove common URL patterns
        $slug = preg_replace('/\.(html?|php)$/', '', $slug);

        $query = ContentItem::query()->where('slug', $slug);

        if ($workspaceSlug) {
            $workspace = Workspace::where('slug', $workspaceSlug)->first();
            if ($workspace) {
                $query->where('workspace_id', $workspace->id);
            }
        }

        return $query->first();
    }
}
