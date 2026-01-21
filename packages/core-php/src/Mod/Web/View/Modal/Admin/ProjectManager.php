<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Project;
use Core\Mod\Tenant\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectManager extends Component
{
    // Create/Edit modal state
    public bool $showModal = false;

    public ?int $editingProjectId = null;

    public string $name = '';

    public string $color = '#6366f1';

    // Delete confirmation
    public bool $showDeleteModal = false;

    public ?int $deletingProjectId = null;

    public string $deleteAction = 'unassign'; // 'unassign' or 'delete'

    // Move biolinks modal
    public bool $showMoveModal = false;

    public ?int $sourceProjectId = null;

    public ?int $targetProjectId = null;

    /**
     * Get all projects for the current workspace.
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
            ->withCount('biolinks')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get count of unassigned biolinks (not in any project).
     */
    #[Computed]
    public function unassignedCount(): int
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return 0;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return 0;
        }

        return Page::where('workspace_id', $workspace->id)
            ->whereNull('project_id')
            ->count();
    }

    /**
     * Get available colours for the picker.
     */
    #[Computed]
    public function colours(): array
    {
        return Project::COLOURS;
    }

    /**
     * Open modal to create a new project.
     */
    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    /**
     * Open modal to edit an existing project.
     */
    public function openEditModal(int $projectId): void
    {
        $project = $this->findProject($projectId);

        if (! $project) {
            return;
        }

        $this->editingProjectId = $project->id;
        $this->name = $project->name;
        $this->color = $project->color;
        $this->showModal = true;
    }

    /**
     * Close the create/edit modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    /**
     * Reset form fields.
     */
    private function resetForm(): void
    {
        $this->editingProjectId = null;
        $this->name = '';
        $this->color = '#6366f1';
        $this->resetErrorBag();
    }

    /**
     * Save a project (create or update).
     */
    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:128'],
            'color' => ['required', 'string', 'max:16', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $user = Auth::user();

        if (! $user instanceof User) {
            $this->dispatch('notify', message: 'Authentication error.', type: 'error');

            return;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            $this->dispatch('notify', message: 'No workspace found.', type: 'error');

            return;
        }

        if ($this->editingProjectId) {
            // Update existing project
            $project = Project::where('workspace_id', $workspace->id)
                ->find($this->editingProjectId);

            if (! $project) {
                $this->dispatch('notify', message: 'Project not found.', type: 'error');

                return;
            }

            $project->update([
                'name' => $this->name,
                'color' => $this->color,
            ]);

            $this->dispatch('notify', message: 'Project updated.', type: 'success');
        } else {
            // Create new project
            Project::create([
                'workspace_id' => $workspace->id,
                'user_id' => $user->id,
                'name' => $this->name,
                'color' => $this->color,
            ]);

            $this->dispatch('notify', message: 'Project created.', type: 'success');
        }

        $this->closeModal();
    }

    /**
     * Open delete confirmation modal.
     */
    public function confirmDelete(int $projectId): void
    {
        $project = $this->findProject($projectId);

        if (! $project) {
            return;
        }

        $this->deletingProjectId = $projectId;
        $this->deleteAction = 'unassign';
        $this->showDeleteModal = true;
    }

    /**
     * Close delete confirmation modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingProjectId = null;
        $this->deleteAction = 'unassign';
    }

    /**
     * Delete a project with the selected action for bio.
     */
    public function deleteProject(): void
    {
        if (! $this->deletingProjectId) {
            return;
        }

        $project = $this->findProject($this->deletingProjectId);

        if (! $project) {
            $this->dispatch('notify', message: 'Project not found.', type: 'error');
            $this->closeDeleteModal();

            return;
        }

        if ($this->deleteAction === 'delete') {
            // Delete all biolinks in the project
            $project->biolinks()->delete();
        } else {
            // Unassign biolinks (set project_id to null)
            $project->biolinks()->update(['project_id' => null]);
        }

        $project->delete();

        $this->dispatch('notify', message: 'Project deleted.', type: 'success');
        $this->closeDeleteModal();
    }

    /**
     * Open move biolinks modal.
     */
    public function openMoveModal(int $sourceProjectId): void
    {
        $this->sourceProjectId = $sourceProjectId;
        $this->targetProjectId = null;
        $this->showMoveModal = true;
    }

    /**
     * Close move biolinks modal.
     */
    public function closeMoveModal(): void
    {
        $this->showMoveModal = false;
        $this->sourceProjectId = null;
        $this->targetProjectId = null;
    }

    /**
     * Move all biolinks from source to target project.
     */
    public function moveBiolinks(): void
    {
        if (! $this->sourceProjectId) {
            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return;
        }

        // Build query for source biolinks
        if ($this->sourceProjectId === -1) {
            // Moving from unassigned
            $query = Page::where('workspace_id', $workspace->id)
                ->whereNull('project_id');
        } else {
            $query = Page::where('workspace_id', $workspace->id)
                ->where('project_id', $this->sourceProjectId);
        }

        // Update to target project
        $query->update([
            'project_id' => $this->targetProjectId === -1 ? null : $this->targetProjectId,
        ]);

        $this->dispatch('notify', message: 'Biolinks moved.', type: 'success');
        $this->closeMoveModal();
    }

    /**
     * Handle biolink drop on a project (drag and drop).
     */
    #[On('biolink-dropped')]
    public function handleBiolinkDrop(int $biolinkId, ?int $projectId): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return;
        }

        $biolink = Page::where('workspace_id', $workspace->id)
            ->find($biolinkId);

        if (! $biolink) {
            return;
        }

        // Verify target project belongs to workspace (if not null)
        if ($projectId !== null) {
            $project = Project::where('workspace_id', $workspace->id)
                ->find($projectId);

            if (! $project) {
                return;
            }
        }

        $biolink->update(['project_id' => $projectId]);
        $this->dispatch('notify', message: 'Biolink moved to project.', type: 'success');
    }

    /**
     * Find a project belonging to the current workspace.
     */
    private function findProject(int $projectId): ?Project
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return null;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return null;
        }

        return Project::where('workspace_id', $workspace->id)
            ->find($projectId);
    }

    public function render()
    {
        return view('webpage::admin.project-manager')
            ->layout('hub::admin.layouts.app', ['title' => 'Projects']);
    }
}
