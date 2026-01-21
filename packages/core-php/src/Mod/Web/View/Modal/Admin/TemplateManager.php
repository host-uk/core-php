<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Template;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin Template Manager component.
 *
 * CRUD operations for biolink templates.
 * Allows admins to create, edit, and manage system templates.
 */
#[Layout('hub::admin.layouts.app')]
class TemplateManager extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';

    public string $categoryFilter = 'all';

    public string $typeFilter = 'all'; // all, system, custom

    // Edit modal state
    public bool $showEditModal = false;

    public ?int $editingTemplateId = null;

    public string $name = '';

    public string $category = 'business';

    public string $description = '';

    public array $blocksJson = [];

    public array $settingsJson = [];

    public array $placeholders = [];

    public bool $isPremium = false;

    public bool $isActive = true;

    public int $sortOrder = 0;

    // Delete confirmation
    public bool $showDeleteModal = false;

    public ?int $deletingTemplateId = null;

    // Bulk actions
    public array $selectedTemplates = [];

    public bool $selectAll = false;

    // Preview modal
    public bool $showPreviewModal = false;

    public ?int $previewTemplateId = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
        // Admin-only check would go here
        // if (!Auth::user()->isAdmin()) abort(403);
    }

    /**
     * Get templates with filters applied.
     */
    #[Computed]
    public function templates()
    {
        $query = Template::query();

        // Search filter
        if ($this->search) {
            $query->search($this->search);
        }

        // Category filter
        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        // Type filter
        if ($this->typeFilter === 'system') {
            $query->where('is_system', true);
        } elseif ($this->typeFilter === 'custom') {
            $query->where('is_system', false);
        }

        return $query->orderBy('sort_order')->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Get available categories.
     */
    #[Computed]
    public function categories(): array
    {
        return Template::getCategories();
    }

    /**
     * Open create modal.
     */
    public function create(): void
    {
        $this->resetForm();
        $this->showEditModal = true;
    }

    /**
     * Open edit modal for a template.
     */
    public function edit(int $templateId): void
    {
        $template = Template::find($templateId);

        if (! $template) {
            return;
        }

        $this->editingTemplateId = $templateId;
        $this->name = $template->name;
        $this->category = $template->category;
        $this->description = $template->description ?? '';
        $this->blocksJson = $template->blocks_json->toArray();
        $this->settingsJson = $template->settings_json->toArray();
        $this->placeholders = $template->placeholders?->toArray() ?? [];
        $this->isPremium = $template->is_premium;
        $this->isActive = $template->is_active;
        $this->sortOrder = $template->sort_order;

        $this->showEditModal = true;
    }

    /**
     * Close edit modal.
     */
    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetForm();
        $this->resetValidation();
    }

    /**
     * Save template (create or update).
     */
    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:128'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys($this->categories))],
            'description' => ['nullable', 'string', 'max:500'],
            'sortOrder' => ['integer', 'min:0'],
        ]);

        if ($this->editingTemplateId) {
            // Update existing
            $template = Template::find($this->editingTemplateId);

            if (! $template) {
                $this->dispatch('notify', message: 'Template not found.', type: 'error');

                return;
            }

            $template->update([
                'name' => $this->name,
                'category' => $this->category,
                'description' => $this->description,
                'blocks_json' => $this->blocksJson,
                'settings_json' => $this->settingsJson,
                'placeholders' => $this->placeholders,
                'is_premium' => $this->isPremium,
                'is_active' => $this->isActive,
                'sort_order' => $this->sortOrder,
            ]);

            $this->dispatch('notify', message: 'Template updated successfully.', type: 'success');
        } else {
            // Create new system template
            Template::create([
                'name' => $this->name,
                'category' => $this->category,
                'description' => $this->description,
                'blocks_json' => $this->blocksJson ?: [],
                'settings_json' => $this->settingsJson ?: [],
                'placeholders' => $this->placeholders ?: [],
                'is_system' => true, // Admin-created templates are system templates
                'is_premium' => $this->isPremium,
                'is_active' => $this->isActive,
                'sort_order' => $this->sortOrder,
            ]);

            $this->dispatch('notify', message: 'Template created successfully.', type: 'success');
        }

        $this->closeEditModal();
    }

    /**
     * Open delete confirmation modal.
     */
    public function confirmDelete(int $templateId): void
    {
        $this->deletingTemplateId = $templateId;
        $this->showDeleteModal = true;
    }

    /**
     * Close delete modal.
     */
    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingTemplateId = null;
    }

    /**
     * Delete a template.
     */
    public function delete(): void
    {
        if (! $this->deletingTemplateId) {
            return;
        }

        $template = Template::find($this->deletingTemplateId);

        if (! $template) {
            $this->dispatch('notify', message: 'Template not found.', type: 'error');
            $this->closeDeleteModal();

            return;
        }

        // Check if template is in use
        $usageCount = $template->usage_count;
        if ($usageCount > 0) {
            $this->dispatch('notify', message: "This template has been used {$usageCount} times. Consider deactivating instead.", type: 'warning');
            $this->closeDeleteModal();

            return;
        }

        $template->delete();

        $this->dispatch('notify', message: 'Template deleted successfully.', type: 'success');
        $this->closeDeleteModal();
    }

    /**
     * Toggle template active status.
     */
    public function toggleActive(int $templateId): void
    {
        $template = Template::find($templateId);

        if (! $template) {
            return;
        }

        $template->is_active = ! $template->is_active;
        $template->save();

        $status = $template->is_active ? 'activated' : 'deactivated';
        $this->dispatch('notify', message: "Template {$status}.", type: 'success');
    }

    /**
     * Toggle template premium status.
     */
    public function togglePremium(int $templateId): void
    {
        $template = Template::find($templateId);

        if (! $template) {
            return;
        }

        $template->is_premium = ! $template->is_premium;
        $template->save();

        $status = $template->is_premium ? 'premium' : 'free';
        $this->dispatch('notify', message: "Template marked as {$status}.", type: 'success');
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(int $templateId): void
    {
        $template = Template::find($templateId);

        if (! $template) {
            return;
        }

        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name.' (Copy)';
        $newTemplate->slug = null; // Will be auto-generated
        $newTemplate->usage_count = 0;
        $newTemplate->save();

        $this->dispatch('notify', message: 'Template duplicated successfully.', type: 'success');
    }

    /**
     * Reset form fields.
     */
    protected function resetForm(): void
    {
        $this->editingTemplateId = null;
        $this->name = '';
        $this->category = 'business';
        $this->description = '';
        $this->blocksJson = [];
        $this->settingsJson = [];
        $this->placeholders = [];
        $this->isPremium = false;
        $this->isActive = true;
        $this->sortOrder = 0;
    }

    /**
     * Set filter.
     */
    public function setTypeFilter(string $type): void
    {
        $this->typeFilter = $type;
        $this->resetPage();
    }

    /**
     * Set category filter.
     */
    public function setCategoryFilter(string $category): void
    {
        $this->categoryFilter = $category;
        $this->resetPage();
    }

    /**
     * Clear filters.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = 'all';
        $this->typeFilter = 'all';
        $this->resetPage();
    }

    /**
     * Open preview modal.
     */
    public function preview(int $templateId): void
    {
        $this->previewTemplateId = $templateId;
        $this->showPreviewModal = true;
    }

    /**
     * Close preview modal.
     */
    public function closePreviewModal(): void
    {
        $this->showPreviewModal = false;
        $this->previewTemplateId = null;
    }

    /**
     * Bulk activate templates.
     */
    public function bulkActivate(): void
    {
        if (empty($this->selectedTemplates)) {
            $this->dispatch('notify', message: 'No templates selected.', type: 'warning');

            return;
        }

        $count = Template::whereIn('id', $this->selectedTemplates)
            ->update(['is_active' => true]);

        $this->dispatch('notify', message: "{$count} templates activated.", type: 'success');
        $this->selectedTemplates = [];
        $this->selectAll = false;
    }

    /**
     * Bulk deactivate templates.
     */
    public function bulkDeactivate(): void
    {
        if (empty($this->selectedTemplates)) {
            $this->dispatch('notify', message: 'No templates selected.', type: 'warning');

            return;
        }

        $count = Template::whereIn('id', $this->selectedTemplates)
            ->update(['is_active' => false]);

        $this->dispatch('notify', message: "{$count} templates deactivated.", type: 'success');
        $this->selectedTemplates = [];
        $this->selectAll = false;
    }

    /**
     * Bulk delete templates.
     */
    public function bulkDelete(): void
    {
        if (empty($this->selectedTemplates)) {
            $this->dispatch('notify', message: 'No templates selected.', type: 'warning');

            return;
        }

        // Check if any templates are in use
        $templates = Template::whereIn('id', $this->selectedTemplates)->get();
        $inUse = $templates->filter(fn ($t) => $t->usage_count > 0);

        if ($inUse->isNotEmpty()) {
            $this->dispatch('notify', message: 'Some templates are in use and cannot be deleted.', type: 'warning');

            return;
        }

        $count = Template::whereIn('id', $this->selectedTemplates)->delete();

        $this->dispatch('notify', message: "{$count} templates deleted.", type: 'success');
        $this->selectedTemplates = [];
        $this->selectAll = false;
    }

    /**
     * Toggle select all.
     */
    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedTemplates = $this->templates->pluck('id')->toArray();
        } else {
            $this->selectedTemplates = [];
        }
    }

    public function render()
    {
        return view('webpage::admin.template-manager')
            ->title('Template Manager');
    }
}
