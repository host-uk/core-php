<?php

declare(strict_types=1);

namespace Core\Mod\Web\Controllers\Api;

use Core\Front\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Core\Mod\Api\Controllers\Concerns\HasApiResponses;
use Core\Mod\Api\Controllers\Concerns\ResolvesWorkspace;
use Core\Mod\Api\Resources\PaginatedCollection;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Requests\StorePageRequest;
use Core\Mod\Web\Requests\UpdatePageRequest;
use Core\Mod\Web\Resources\BioResource;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;

/**
 * BioLink API controller.
 *
 * Provides CRUD operations for biolinks via REST API.
 * Supports both session auth and API key auth.
 */
class PageController extends Controller
{
    use HasApiResponses;
    use ResolvesWorkspace;

    public function __construct(
        protected EntitlementService $entitlements
    ) {}

    /**
     * List all biolinks for the current workspace.
     *
     * GET /api/v1/biolinks
     */
    public function index(Request $request): PaginatedCollection|JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        $query = Page::where('workspace_id', $workspace->id)
            ->with(['project', 'domain'])
            ->withCount('blocks');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        // Filter by enabled status
        if ($request->has('is_enabled')) {
            $query->where('is_enabled', filter_var($request->input('is_enabled'), FILTER_VALIDATE_BOOLEAN));
        }

        // Search by URL
        if ($request->has('search')) {
            $query->where('url', 'like', '%'.$request->input('search').'%');
        }

        // Sorting
        $allowedSorts = ['created_at', 'updated_at', 'clicks', 'url'];
        $sortBy = in_array($request->input('sort_by'), $allowedSorts, true)
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) $request->input('per_page', 25), 100);
        $biolinks = $query->paginate($perPage);

        return new PaginatedCollection($biolinks, BioResource::class);
    }

    /**
     * Create a new bio.
     *
     * POST /api/v1/biolinks
     */
    public function store(StorePageRequest $request): BioResource|JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Check entitlement limits
        $check = $this->entitlements->can($workspace, 'bio.pages');
        if ($check->isDenied()) {
            return $this->limitReachedResponse(
                'bio.pages',
                'You have reached your biolink pages limit. Upgrade your plan to create more.'
            );
        }

        $validated = $request->validated();

        // Check URL uniqueness for domain
        $domainId = $validated['domain_id'] ?? null;
        $urlExists = Page::where('url', Str::lower($validated['url']))
            ->where('domain_id', $domainId)
            ->exists();

        if ($urlExists) {
            return $this->validationErrorResponse([
                'url' => ['This URL is already taken.'],
            ]);
        }

        // Validate project belongs to workspace
        if (isset($validated['project_id'])) {
            $projectExists = $workspace->bioProjects()
                ->where('id', $validated['project_id'])
                ->exists();
            if (! $projectExists) {
                return $this->validationErrorResponse([
                    'project_id' => ['Project not found in your workspace.'],
                ]);
            }
        }

        // Validate domain belongs to workspace
        if (isset($validated['domain_id'])) {
            $domainExists = $workspace->bioDomains()
                ->where('id', $validated['domain_id'])
                ->exists();
            if (! $domainExists) {
                return $this->validationErrorResponse([
                    'domain_id' => ['Domain not found in your workspace.'],
                ]);
            }
        }

        $user = $request->user();

        $biolink = Page::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'url' => Str::lower($validated['url']),
            'type' => $validated['type'] ?? 'biolink',
            'project_id' => $validated['project_id'] ?? null,
            'domain_id' => $validated['domain_id'] ?? null,
            'location_url' => $validated['location_url'] ?? null,
            'settings' => $validated['settings'] ?? [],
            'is_enabled' => $validated['is_enabled'] ?? true,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        // Record usage
        $this->entitlements->recordUsage($workspace, 'bio.pages', 1);

        $biolink->load(['project', 'domain']);

        return new BioResource($biolink);
    }

    /**
     * Get a single bio.
     *
     * GET /api/v1/biolinks/{biolink}
     */
    public function show(Request $request, Page $biolink): BioResource|JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        $biolink->load(['blocks', 'project', 'domain']);
        $biolink->loadCount('blocks');

        return new BioResource($biolink);
    }

    /**
     * Update a bio.
     *
     * PUT /api/v1/biolinks/{biolink}
     */
    public function update(UpdatePageRequest $request, Page $biolink): BioResource|JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        $validated = $request->validated();

        // Check URL uniqueness if changing URL or domain
        if (isset($validated['url']) || isset($validated['domain_id'])) {
            $url = $validated['url'] ?? $biolink->url;
            $domainId = $validated['domain_id'] ?? $biolink->domain_id;

            $urlExists = Page::where('url', Str::lower($url))
                ->where('domain_id', $domainId)
                ->where('id', '!=', $biolink->id)
                ->exists();

            if ($urlExists) {
                return $this->validationErrorResponse([
                    'url' => ['This URL is already taken.'],
                ]);
            }

            if (isset($validated['url'])) {
                $validated['url'] = Str::lower($validated['url']);
            }
        }

        // Validate project belongs to workspace
        if (isset($validated['project_id'])) {
            $projectExists = $workspace->bioProjects()
                ->where('id', $validated['project_id'])
                ->exists();
            if (! $projectExists) {
                return $this->validationErrorResponse([
                    'project_id' => ['Project not found in your workspace.'],
                ]);
            }
        }

        // Validate domain belongs to workspace
        if (isset($validated['domain_id'])) {
            $domainExists = $workspace->bioDomains()
                ->where('id', $validated['domain_id'])
                ->exists();
            if (! $domainExists) {
                return $this->validationErrorResponse([
                    'domain_id' => ['Domain not found in your workspace.'],
                ]);
            }
        }

        $biolink->update($validated);

        $biolink->load(['blocks', 'project', 'domain']);
        $biolink->loadCount('blocks');

        return new BioResource($biolink);
    }

    /**
     * Delete a bio.
     *
     * DELETE /api/v1/biolinks/{biolink}
     */
    public function destroy(Request $request, Page $biolink): JsonResponse
    {
        $workspace = $this->getWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        $biolink->delete();

        return response()->json(null, 204);
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
