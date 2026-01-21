<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Project;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Services\EntitlementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Short Link Index component.
 *
 * Displays a filtered list of type=link biolinks (short links only).
 * Provides quick copy, QR code access, and analytics links.
 */
#[Layout('hub::admin.layouts.app')]
class ShortLinkIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    #[Url]
    public ?int $project = null;

    // Create modal
    public bool $showCreateModal = false;

    public string $newUrl = '';

    public string $newLocationUrl = '';

    public ?int $newProjectId = null;

    // Entitlement state
    public bool $canCreate = true;

    public ?string $limitMessage = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'project' => ['except' => null],
    ];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
        $this->checkEntitlements();
    }

    /**
     * Check entitlements for creating short links.
     */
    protected function checkEntitlements(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return;
        }

        $entitlements = app(EntitlementService::class);
        $result = $entitlements->can($workspace, 'bio.shortlinks');
        $this->canCreate = $result->isAllowed();

        if ($result->isDenied()) {
            $this->limitMessage = $result->getMessage();
        }
    }

    /**
     * Get entitlement summary for display.
     */
    #[Computed]
    public function entitlementSummary(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return [];
        }

        $entitlements = app(EntitlementService::class);
        $result = $entitlements->can($workspace, 'bio.shortlinks');

        return [
            'used' => $result->used ?? 0,
            'limit' => $result->limit,
            'unlimited' => $result->isUnlimited(),
        ];
    }

    /**
     * Get projects for the current workspace.
     */
    #[Computed]
    public function projects()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return collect();
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return collect();
        }

        return Project::forWorkspace($workspace)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get paginated short links (type=link only).
     */
    #[Computed]
    public function shortLinks()
    {
        $query = Page::where('user_id', Auth::id())
            ->where('type', 'link')
            ->with('project', 'domain');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('url', 'like', "%{$this->search}%")
                    ->orWhere('location_url', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter === 'enabled') {
            $query->where('is_enabled', true);
        } elseif ($this->statusFilter === 'disabled') {
            $query->where('is_enabled', false);
        }

        // Project filter
        if ($this->project === -1) {
            $query->whereNull('project_id');
        } elseif ($this->project) {
            $query->where('project_id', $this->project);
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Get statistics for the header.
     */
    #[Computed]
    public function stats(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return ['total' => 0, 'enabled' => 0, 'clicks' => 0];
        }

        $query = Page::where('user_id', $user->id)->where('type', 'link');

        return [
            'total' => $query->count(),
            'enabled' => (clone $query)->where('is_enabled', true)->count(),
            'clicks' => (clone $query)->sum('clicks'),
        ];
    }

    /**
     * Open create modal.
     */
    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->newUrl = '';
        $this->newLocationUrl = '';
        $this->newProjectId = $this->project > 0 ? $this->project : null;
    }

    /**
     * Close create modal.
     */
    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->newUrl = '';
        $this->newLocationUrl = '';
        $this->newProjectId = null;
        $this->resetValidation();
    }

    /**
     * Create a new short link.
     */
    public function create(): void
    {
        $this->checkEntitlements();

        if (! $this->canCreate) {
            $this->dispatch('notify', message: $this->limitMessage ?? 'You have reached your short link limit.', type: 'error');

            return;
        }

        $this->validate([
            'newUrl' => ['required', 'string', 'max:256', 'regex:/^[a-z0-9\-_]+$/i'],
            'newLocationUrl' => ['required', 'url', 'max:2048'],
            'newProjectId' => ['nullable', 'integer', 'exists:biolink_projects,id'],
        ], [
            'newUrl.required' => 'Please enter a short URL.',
            'newUrl.regex' => 'URL can only contain letters, numbers, hyphens, and underscores.',
            'newLocationUrl.required' => 'Please enter a destination URL.',
            'newLocationUrl.url' => 'Please enter a valid URL.',
        ]);

        // Check uniqueness
        $exists = Page::where('url', Str::lower($this->newUrl))
            ->whereNull('domain_id')
            ->exists();

        if ($exists) {
            $this->addError('newUrl', 'This URL is already taken.');

            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            $this->dispatch('notify', message: 'Authentication error.', type: 'error');

            return;
        }

        $workspace = $user->defaultHostWorkspace();

        $biolink = Page::create([
            'workspace_id' => $workspace?->id,
            'user_id' => $user->id,
            'project_id' => $this->newProjectId,
            'url' => Str::lower($this->newUrl),
            'location_url' => $this->newLocationUrl,
            'type' => 'link',
            'settings' => [],
        ]);

        // Record usage
        if ($workspace) {
            $entitlements = app(EntitlementService::class);
            $entitlements->recordUsage(
                $workspace,
                'bio.shortlinks',
                1,
                $user,
                ['biolink_id' => $biolink->id, 'type' => 'link']
            );
        }

        $this->closeCreateModal();
        $this->dispatch('notify', message: 'Short link created successfully', type: 'success');
    }

    /**
     * Toggle link enabled status.
     */
    public function toggleEnabled(int $id): void
    {
        $link = Page::where('user_id', Auth::id())
            ->where('type', 'link')
            ->findOrFail($id);

        $link->update(['is_enabled' => ! $link->is_enabled]);
        $this->dispatch('notify', message: 'Link updated', type: 'success');
    }

    /**
     * Delete a short link.
     */
    public function delete(int $id): void
    {
        $link = Page::where('user_id', Auth::id())
            ->where('type', 'link')
            ->findOrFail($id);

        $link->delete();
        $this->dispatch('notify', message: 'Link deleted', type: 'success');
    }

    /**
     * Duplicate a short link.
     */
    public function duplicate(int $id): void
    {
        $link = Page::where('user_id', Auth::id())
            ->where('type', 'link')
            ->findOrFail($id);

        // Generate unique URL
        $baseUrl = $link->url.'-copy';
        $url = $baseUrl;
        $counter = 1;

        while (Page::where('url', $url)->whereNull('domain_id')->exists()) {
            $url = $baseUrl.'-'.$counter++;
        }

        $newLink = $link->replicate();
        $newLink->url = $url;
        $newLink->clicks = 0;
        $newLink->save();

        $this->dispatch('notify', message: 'Link duplicated', type: 'success');
    }

    /**
     * Filter by project.
     */
    public function filterByProject(?int $projectId): void
    {
        $this->project = $projectId;
        $this->resetPage();
    }

    /**
     * Clear project filter.
     */
    public function clearProjectFilter(): void
    {
        $this->project = null;
        $this->resetPage();
    }

    public function render()
    {
        return view('webpage::admin.short-link-index')
            ->title('Short Links');
    }
}
