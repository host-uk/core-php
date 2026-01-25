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
use Core\Mod\Web\Requests\StoreShortLinkRequest;
use Core\Mod\Web\Requests\UpdateShortLinkRequest;
use Core\Mod\Web\Resources\ShortLinkResource;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Models\Workspace;
use Core\Mod\Tenant\Services\EntitlementService;

/**
 * Short Link API controller.
 *
 * Provides CRUD operations for short links (type=link biolinks) via REST API.
 * Supports both session auth and API key auth.
 */
class ShortLinkController extends Controller
{
    use HasApiResponses;
    use ResolvesWorkspace;

    public function __construct(
        protected EntitlementService $entitlements
    ) {}

    /**
     * List all short links for the current workspace.
     *
     * GET /api/v1/shortlinks
     */
    public function index(Request $request): PaginatedCollection|JsonResponse
    {
        $workspace = $this->resolveWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        $query = Page::where('workspace_id', $workspace->id)
            ->where('type', 'link')
            ->with(['project', 'domain']);

        // Filter by project
        if ($request->has('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        // Filter by enabled status
        if ($request->has('is_enabled')) {
            $query->where('is_enabled', filter_var($request->input('is_enabled'), FILTER_VALIDATE_BOOLEAN));
        }

        // Search by URL or destination
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('url', 'like', '%'.$search.'%')
                    ->orWhere('location_url', 'like', '%'.$search.'%');
            });
        }

        // Sorting
        $allowedSorts = ['created_at', 'updated_at', 'clicks', 'url'];
        $sortBy = in_array($request->input('sort_by'), $allowedSorts, true)
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $perPage = min((int) $request->input('per_page', 25), 100);
        $shortlinks = $query->paginate($perPage);

        return new PaginatedCollection($shortlinks, ShortLinkResource::class);
    }

    /**
     * Create a new short link.
     *
     * POST /api/v1/shortlinks
     */
    public function store(StoreShortLinkRequest $request): ShortLinkResource|JsonResponse
    {
        $workspace = $this->resolveWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Check entitlement limits
        $check = $this->entitlements->can($workspace, 'bio.shortlinks');
        if ($check->isDenied()) {
            return $this->limitReachedResponse(
                'bio.shortlinks',
                'You have reached your short links limit. Upgrade your plan to create more.'
            );
        }

        $validated = $request->validated();

        // Generate URL if not provided
        $url = $validated['url'] ?? $this->generateUniqueSlug();

        // Check URL uniqueness for domain
        $domainId = $validated['domain_id'] ?? null;
        $urlExists = Page::where('url', Str::lower($url))
            ->where('domain_id', $domainId)
            ->exists();

        if ($urlExists) {
            // If auto-generated, try again
            if (! isset($validated['url'])) {
                $url = $this->generateUniqueSlug();
            } else {
                return $this->validationErrorResponse([
                    'url' => ['This URL is already taken.'],
                ]);
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

        $user = $request->user();

        // Build settings from short link specific fields
        $settings = [
            'redirect_type' => $validated['redirect_type'] ?? '302',
            'cloaking' => $validated['cloaking'] ?? false,
        ];

        if (isset($validated['password'])) {
            $settings['password'] = bcrypt($validated['password']);
        }

        $shortlink = Page::create([
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'url' => Str::lower($url),
            'type' => 'link',
            'project_id' => $validated['project_id'] ?? null,
            'domain_id' => $validated['domain_id'] ?? null,
            'location_url' => $validated['destination_url'],
            'settings' => $settings,
            'is_enabled' => $validated['is_enabled'] ?? true,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
        ]);

        // Record usage
        $this->entitlements->recordUsage($workspace, 'bio.shortlinks', 1);

        $shortlink->load(['project', 'domain']);

        return new ShortLinkResource($shortlink);
    }

    /**
     * Get a single short link.
     *
     * GET /api/v1/shortlinks/{shortlink}
     */
    public function show(Request $request, Page $shortlink): ShortLinkResource|JsonResponse
    {
        $workspace = $this->resolveWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify shortlink belongs to workspace and is correct type
        if ($shortlink->workspace_id !== $workspace->id || $shortlink->type !== 'link') {
            return $this->notFoundResponse('Short link');
        }

        $shortlink->load(['project', 'domain']);

        return new ShortLinkResource($shortlink);
    }

    /**
     * Update a short link.
     *
     * PUT /api/v1/shortlinks/{shortlink}
     */
    public function update(UpdateShortLinkRequest $request, Page $shortlink): ShortLinkResource|JsonResponse
    {
        $workspace = $this->resolveWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify shortlink belongs to workspace and is correct type
        if ($shortlink->workspace_id !== $workspace->id || $shortlink->type !== 'link') {
            return $this->notFoundResponse('Short link');
        }

        $validated = $request->validated();

        // Check URL uniqueness if changing URL or domain
        if (isset($validated['url']) || isset($validated['domain_id'])) {
            $url = $validated['url'] ?? $shortlink->url;
            $domainId = $validated['domain_id'] ?? $shortlink->domain_id;

            $urlExists = Page::where('url', Str::lower($url))
                ->where('domain_id', $domainId)
                ->where('id', '!=', $shortlink->id)
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

        // Build update data
        $updateData = [];

        if (isset($validated['url'])) {
            $updateData['url'] = $validated['url'];
        }
        if (isset($validated['destination_url'])) {
            $updateData['location_url'] = $validated['destination_url'];
        }
        if (array_key_exists('project_id', $validated)) {
            $updateData['project_id'] = $validated['project_id'];
        }
        if (array_key_exists('domain_id', $validated)) {
            $updateData['domain_id'] = $validated['domain_id'];
        }
        if (isset($validated['is_enabled'])) {
            $updateData['is_enabled'] = $validated['is_enabled'];
        }
        if (array_key_exists('start_date', $validated)) {
            $updateData['start_date'] = $validated['start_date'];
        }
        if (array_key_exists('end_date', $validated)) {
            $updateData['end_date'] = $validated['end_date'];
        }

        // Update settings
        $settings = $shortlink->settings?->toArray() ?? [];

        if (isset($validated['redirect_type'])) {
            $settings['redirect_type'] = $validated['redirect_type'];
        }
        if (isset($validated['cloaking'])) {
            $settings['cloaking'] = $validated['cloaking'];
        }
        if (isset($validated['password'])) {
            $settings['password'] = bcrypt($validated['password']);
        }

        $updateData['settings'] = $settings;

        $shortlink->update($updateData);

        $shortlink->load(['project', 'domain']);

        return new ShortLinkResource($shortlink);
    }

    /**
     * Delete a short link.
     *
     * DELETE /api/v1/shortlinks/{shortlink}
     */
    public function destroy(Request $request, Page $shortlink): JsonResponse
    {
        $workspace = $this->resolveWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify shortlink belongs to workspace and is correct type
        if ($shortlink->workspace_id !== $workspace->id || $shortlink->type !== 'link') {
            return $this->notFoundResponse('Short link');
        }

        $shortlink->delete();

        return response()->json(null, 204);
    }

    /**
     * Generate a unique random slug for short links.
     */
    protected function generateUniqueSlug(int $length = 6): string
    {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            $slug = Str::lower(Str::random($length));
            $exists = Page::where('url', $slug)->exists();
            $attempts++;

            // Increase length if we're having trouble finding unique slugs
            if ($attempts > $maxAttempts / 2) {
                $length++;
            }
        } while ($exists && $attempts < $maxAttempts);

        return $slug;
    }
}
