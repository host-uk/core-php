<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Project;
use Core\Mod\Tenant\Models\User;
use Core\Mod\Tenant\Services\EntitlementService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public string $typeFilter = '';

    public string $statusFilter = '';

    #[Url]
    public ?int $project = null;

    // Create modal
    public bool $showCreateModal = false;

    public string $newUrl = '';

    public string $newType = 'biolink';

    public ?int $newProjectId = null;

    // Move to project modal
    public bool $showMoveModal = false;

    public ?int $movingBiolinkId = null;

    public ?int $moveToProjectId = null;

    // Entitlement state
    public bool $canCreateBiolink = true;

    public bool $canCreateShortlink = true;

    public ?string $biolinkLimitMessage = null;

    public ?string $shortlinkLimitMessage = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'project' => ['except' => null],
    ];

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->checkEntitlements();
    }

    /**
     * Check entitlements for creating biolinks and shortlinks.
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

        // Check biolink pages limit
        $biolinkResult = $entitlements->can($workspace, 'bio.pages');
        $this->canCreateBiolink = $biolinkResult->isAllowed();
        if ($biolinkResult->isDenied()) {
            $this->biolinkLimitMessage = $biolinkResult->getMessage();
        }

        // Check shortlinks limit
        $shortlinkResult = $entitlements->can($workspace, 'bio.shortlinks');
        $this->canCreateShortlink = $shortlinkResult->isAllowed();
        if ($shortlinkResult->isDenied()) {
            $this->shortlinkLimitMessage = $shortlinkResult->getMessage();
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

        $biolinkResult = $entitlements->can($workspace, 'bio.pages');
        $shortlinkResult = $entitlements->can($workspace, 'bio.shortlinks');

        return [
            'biolinks' => [
                'used' => $biolinkResult->used ?? 0,
                'limit' => $biolinkResult->limit,
                'unlimited' => $biolinkResult->isUnlimited(),
            ],
            'shortlinks' => [
                'used' => $shortlinkResult->used ?? 0,
                'limit' => $shortlinkResult->limit,
                'unlimited' => $shortlinkResult->isUnlimited(),
            ],
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
     * Get the currently selected project.
     */
    #[Computed]
    public function currentProject(): ?Project
    {
        if (! $this->project) {
            return null;
        }

        return $this->projects->firstWhere('id', $this->project);
    }

    /**
     * Get paginated bio.
     */
    #[Computed]
    public function biolinks()
    {
        $query = Page::where('user_id', Auth::id())
            ->with('project', 'domain')
            ->withCount('blocks');

        if ($this->search) {
            $query->where('url', 'like', "%{$this->search}%");
        }

        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->statusFilter === 'enabled') {
            $query->where('is_enabled', true);
        } elseif ($this->statusFilter === 'disabled') {
            $query->where('is_enabled', false);
        }

        // Project filter
        if ($this->project === -1) {
            // Unassigned
            $query->whereNull('project_id');
        } elseif ($this->project) {
            $query->where('project_id', $this->project);
        }

        return $query->latest()->paginate(12);
    }

    /**
     * Get link types for filter.
     */
    #[Computed]
    public function linkTypes(): array
    {
        return [
            'biolink' => 'Bio Page',
            'link' => 'Short Link',
            'file' => 'File Link',
            'vcard' => 'vCard',
            'event' => 'Event',
        ];
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

    /**
     * Open create modal.
     */
    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->newUrl = '';
        $this->newType = 'biolink';
        $this->newProjectId = $this->project > 0 ? $this->project : null;
    }

    /**
     * Close create modal.
     */
    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->newUrl = '';
        $this->newProjectId = null;
    }

    /**
     * Create a new bio.
     */
    public function create(): void
    {
        // Re-check entitlements
        $this->checkEntitlements();

        // Check appropriate limit based on type
        $isBiolink = $this->newType === 'biolink';
        if ($isBiolink && ! $this->canCreateBiolink) {
            $this->dispatch('notify', message: $this->biolinkLimitMessage ?? 'You have reached your bio page limit.', type: 'error');

            return;
        }
        if (! $isBiolink && ! $this->canCreateShortlink) {
            $this->dispatch('notify', message: $this->shortlinkLimitMessage ?? 'You have reached your short link limit.', type: 'error');

            return;
        }

        $this->validate([
            'newUrl' => ['required', 'string', 'max:256', 'regex:/^[a-z0-9\-_]+$/i'],
            'newType' => ['required', 'in:biolink,link,file,vcard,event'],
            'newProjectId' => ['nullable', 'integer', 'exists:biolink_projects,id'],
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
            'type' => $this->newType,
            'settings' => [],
        ]);

        // Record usage
        if ($workspace) {
            $entitlements = app(EntitlementService::class);
            $featureCode = $isBiolink ? 'bio.pages' : 'bio.shortlinks';
            $entitlements->recordUsage(
                $workspace,
                $featureCode,
                1,
                $user,
                ['biolink_id' => $biolink->id, 'type' => $this->newType]
            );
        }

        $this->closeCreateModal();
        $this->dispatch('notify', message: 'Biolink created successfully', type: 'success');

        // Redirect to editor for biolink type
        if ($this->newType === 'biolink') {
            $this->redirect(route('hub.bio.edit', $biolink->id), navigate: true);
        }
    }

    /**
     * Redirect to the short link creation page.
     */
    public function createShortLink(): void
    {
        $this->redirect(route('hub.bio.shortlink.create'), navigate: true);
    }

    /**
     * Redirect to the file link creation page.
     */
    public function createFileLink(): void
    {
        $this->redirect(route('hub.bio.file.create'), navigate: true);
    }

    /**
     * Redirect to the vCard creation page.
     */
    public function createVcard(): void
    {
        $this->redirect(route('hub.bio.vcard.create'), navigate: true);
    }

    /**
     * Redirect to the event creation page.
     */
    public function createEvent(): void
    {
        $this->redirect(route('hub.bio.event.create'), navigate: true);
    }

    /**
     * Toggle biolink enabled status.
     */
    public function toggleEnabled(int $id): void
    {
        $biolink = Page::where('user_id', Auth::id())->findOrFail($id);
        $biolink->update(['is_enabled' => ! $biolink->is_enabled]);
        $this->dispatch('notify', message: 'Biolink updated', type: 'success');
    }

    /**
     * Delete a bio.
     */
    public function delete(int $id): void
    {
        $biolink = Page::where('user_id', Auth::id())->findOrFail($id);
        $biolink->delete();
        $this->dispatch('notify', message: 'Biolink deleted', type: 'success');
    }

    /**
     * Duplicate a bio.
     */
    public function duplicate(int $id): void
    {
        $biolink = Page::where('user_id', Auth::id())->findOrFail($id);

        // Generate unique URL
        $baseUrl = $biolink->url.'-copy';
        $url = $baseUrl;
        $counter = 1;

        while (Page::where('url', $url)->whereNull('domain_id')->exists()) {
            $url = $baseUrl.'-'.$counter++;
        }

        $newBiolink = $biolink->replicate();
        $newBiolink->url = $url;
        $newBiolink->clicks = 0;
        $newBiolink->save();

        // Duplicate blocks
        foreach ($biolink->blocks as $block) {
            $newBlock = $block->replicate();
            $newBlock->biolink_id = $newBiolink->id;
            $newBlock->clicks = 0;
            $newBlock->save();
        }

        $this->dispatch('notify', message: 'Biolink duplicated', type: 'success');
    }

    /**
     * Open move to project modal.
     */
    public function openMoveModal(int $biolinkId): void
    {
        $biolink = Page::where('user_id', Auth::id())->find($biolinkId);

        if (! $biolink) {
            return;
        }

        $this->movingBiolinkId = $biolinkId;
        $this->moveToProjectId = $biolink->project_id;
        $this->showMoveModal = true;
    }

    /**
     * Close move modal.
     */
    public function closeMoveModal(): void
    {
        $this->showMoveModal = false;
        $this->movingBiolinkId = null;
        $this->moveToProjectId = null;
    }

    /**
     * Move biolink to a project.
     */
    public function moveBiolink(): void
    {
        if (! $this->movingBiolinkId) {
            return;
        }

        $biolink = Page::where('user_id', Auth::id())->find($this->movingBiolinkId);

        if (! $biolink) {
            $this->dispatch('notify', message: 'Biolink not found.', type: 'error');
            $this->closeMoveModal();

            return;
        }

        // -1 means unassigned
        $newProjectId = $this->moveToProjectId === -1 ? null : $this->moveToProjectId;

        $biolink->update(['project_id' => $newProjectId]);

        $this->dispatch('notify', message: 'Biolink moved to project.', type: 'success');
        $this->closeMoveModal();
    }

    public function render()
    {
        return view('webpage::admin.index')
            ->layout('hub::admin.layouts.app', ['title' => 'BioLinks']);
    }
}
