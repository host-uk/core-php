<?php

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Models\Template;
use Core\Mod\Web\Services\TemplateApplicator;
use Core\Mod\Tenant\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Template Gallery component.
 *
 * Browse available templates, preview them, and apply to bio.
 * Supports system templates, premium templates, and custom user templates.
 */
#[Layout('hub::admin.layouts.app')]
class TemplateGallery extends Component
{
    // Filter state
    public string $category = 'all';

    public string $search = '';

    // Preview state
    public bool $showPreview = false;

    public ?int $previewTemplateId = null;

    // Apply to biolink modal
    public bool $showApplyModal = false;

    public ?int $selectedTemplateId = null;

    public ?int $selectedBiolinkId = null;

    public bool $replaceExisting = true;

    public function mount(): void
    {
        if (! auth()->user()?->isHades()) {
            abort(403, 'Hades access required');
        }
    }

    // Placeholder values modal
    public bool $showPlaceholdersModal = false;

    public array $placeholderValues = [];

    /**
     * Get the template applicator service.
     */
    #[Computed]
    protected function applicator(): TemplateApplicator
    {
        return app(TemplateApplicator::class);
    }

    /**
     * Check if user has premium template access.
     */
    #[Computed]
    public function hasPremiumAccess(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        $workspace = $user->defaultHostWorkspace();

        if (! $workspace) {
            return false;
        }

        $entitlements = app(\Core\Mod\Tenant\Services\EntitlementService::class);

        return $entitlements->can($workspace, 'bio.tier.pro')->isAllowed()
            || $entitlements->can($workspace, 'bio.tier.ultimate')->isAllowed();
    }

    /**
     * Get all available templates with filters applied.
     */
    #[Computed]
    public function templates()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return collect();
        }

        $query = Template::active()->orderBy('sort_order');

        // Apply category filter
        if ($this->category !== 'all') {
            $query->where('category', $this->category);
        }

        // Apply search
        if ($this->search) {
            $query->search($this->search);
        }

        $templates = $query->get();

        // Mark premium templates as locked if user lacks access
        return $templates->map(function (Template $template) {
            $template->is_locked = $template->is_premium && ! $this->hasPremiumAccess;

            return $template;
        });
    }

    /**
     * Get user's biolinks for the apply modal.
     */
    #[Computed]
    public function biolinks()
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return collect();
        }

        return Page::where('user_id', $user->id)
            ->where('type', 'biolink')
            ->orderBy('url')
            ->get(['id', 'url']);
    }

    /**
     * Get the template being previewed.
     */
    #[Computed]
    public function previewTemplate(): ?Template
    {
        if (! $this->previewTemplateId) {
            return null;
        }

        return Template::find($this->previewTemplateId);
    }

    /**
     * Get the selected template for applying.
     */
    #[Computed]
    public function selectedTemplate(): ?Template
    {
        if (! $this->selectedTemplateId) {
            return null;
        }

        return Template::find($this->selectedTemplateId);
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
     * Open preview for a template.
     */
    public function preview(int $templateId): void
    {
        $this->previewTemplateId = $templateId;
        $this->showPreview = true;
    }

    /**
     * Close preview.
     */
    public function closePreview(): void
    {
        $this->showPreview = false;
        $this->previewTemplateId = null;
    }

    /**
     * Open apply modal for a template.
     */
    public function openApplyModal(int $templateId): void
    {
        $template = Template::find($templateId);

        if (! $template) {
            return;
        }

        // Check premium access
        if ($template->is_premium && ! $this->hasPremiumAccess) {
            $this->dispatch('notify', message: 'This template requires a Pro or Ultimate plan.', type: 'error');

            return;
        }

        $this->selectedTemplateId = $templateId;
        $this->selectedBiolinkId = null;
        $this->replaceExisting = true;

        // Initialize placeholder values with defaults
        $this->placeholderValues = $template->getDefaultPlaceholders();

        $this->showApplyModal = true;
    }

    /**
     * Close apply modal.
     */
    public function closeApplyModal(): void
    {
        $this->showApplyModal = false;
        $this->selectedTemplateId = null;
        $this->selectedBiolinkId = null;
        $this->placeholderValues = [];
    }

    /**
     * Open placeholders customisation modal.
     */
    public function openPlaceholdersModal(): void
    {
        if (! $this->selectedTemplate) {
            return;
        }

        $this->showPlaceholdersModal = true;
    }

    /**
     * Close placeholders modal.
     */
    public function closePlaceholdersModal(): void
    {
        $this->showPlaceholdersModal = false;
    }

    /**
     * Apply template to selected bio.
     */
    public function applyTemplate(): void
    {
        if (! $this->selectedTemplateId || ! $this->selectedBiolinkId) {
            $this->dispatch('notify', message: 'Please select a bio.', type: 'error');

            return;
        }

        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $biolink = Page::where('user_id', $user->id)->find($this->selectedBiolinkId);
        $template = Template::find($this->selectedTemplateId);

        if (! $biolink || ! $template) {
            $this->dispatch('notify', message: 'Biolink or template not found.', type: 'error');

            return;
        }

        // Check premium access again
        if ($template->is_premium && ! $this->hasPremiumAccess) {
            $this->dispatch('notify', message: 'This template requires a Pro or Ultimate plan.', type: 'error');

            return;
        }

        // Apply template with placeholder values
        $success = $this->applicator->apply(
            $biolink,
            $template,
            $this->placeholderValues,
            $this->replaceExisting
        );

        if (! $success) {
            $this->dispatch('notify', message: 'Failed to apply template. Check your plan limits.', type: 'error');

            return;
        }

        $this->closeApplyModal();
        $this->dispatch('notify', message: 'Template applied to '.$biolink->url, type: 'success');
    }

    /**
     * Set category filter.
     */
    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    /**
     * Clear filters.
     */
    public function clearFilters(): void
    {
        $this->category = 'all';
        $this->search = '';
    }

    public function render()
    {
        return view('webpage::admin.template-gallery')
            ->title('Template Gallery');
    }
}
