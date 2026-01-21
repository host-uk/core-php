<?php

declare(strict_types=1);

namespace Core\Mod\Web\View\Modal\Admin;

use Core\Mod\Web\Models\Theme;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin Theme Gallery Manager component.
 *
 * Manages which themes appear in the public gallery.
 * Allows admins to toggle gallery visibility, set categories, and bulk actions.
 */
#[Title('Theme Gallery')]
#[Layout('hub::admin.layouts.app')]
class ThemeGalleryManager extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';

    public string $categoryFilter = 'all';

    public string $galleryFilter = 'all'; // all, gallery, hidden

    // Bulk actions
    public array $selectedThemes = [];

    public bool $selectAll = false;

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
     * Get themes with filters applied.
     */
    #[Computed]
    public function themes()
    {
        $query = Theme::query()
            ->where('is_active', true) // Only active themes
            ->where('is_system', true); // Only system themes can be in gallery

        // Search filter
        if ($this->search) {
            $query->search($this->search);
        }

        // Category filter
        if ($this->categoryFilter !== 'all') {
            $query->where('category', $this->categoryFilter);
        }

        // Gallery visibility filter
        if ($this->galleryFilter === 'gallery') {
            $query->where('is_gallery', true);
        } elseif ($this->galleryFilter === 'hidden') {
            $query->where('is_gallery', false);
        }

        return $query->orderBy('sort_order')->orderBy('name')->paginate(20);
    }

    /**
     * Get available categories.
     */
    #[Computed]
    public function categories(): array
    {
        return Theme::getCategories();
    }

    /**
     * Toggle theme gallery visibility.
     */
    public function toggleGallery(int $themeId): void
    {
        $theme = Theme::find($themeId);

        if (! $theme || ! $theme->is_system) {
            $this->dispatch('notify', message: 'Only system themes can be in the gallery.', type: 'error');

            return;
        }

        $theme->is_gallery = ! $theme->is_gallery;
        $theme->save();

        $status = $theme->is_gallery ? 'added to' : 'removed from';
        $this->dispatch('notify', message: "Theme {$status} gallery.", type: 'success');
    }

    /**
     * Update theme category.
     */
    public function updateCategory(int $themeId, string $category): void
    {
        $theme = Theme::find($themeId);

        if (! $theme) {
            return;
        }

        if (! array_key_exists($category, $this->categories)) {
            $this->dispatch('notify', message: 'Invalid category.', type: 'error');

            return;
        }

        $theme->category = $category;
        $theme->save();

        $this->dispatch('notify', message: 'Category updated.', type: 'success');
    }

    /**
     * Update theme sort order.
     */
    public function updateSortOrder(int $themeId, int $sortOrder): void
    {
        $theme = Theme::find($themeId);

        if (! $theme) {
            return;
        }

        $theme->sort_order = $sortOrder;
        $theme->save();

        $this->dispatch('notify', message: 'Sort order updated.', type: 'success');
    }

    /**
     * Bulk add to gallery.
     */
    public function bulkAddToGallery(): void
    {
        if (empty($this->selectedThemes)) {
            $this->dispatch('notify', message: 'No themes selected.', type: 'warning');

            return;
        }

        $count = Theme::whereIn('id', $this->selectedThemes)
            ->where('is_system', true)
            ->update(['is_gallery' => true]);

        $this->dispatch('notify', message: "{$count} themes added to gallery.", type: 'success');
        $this->selectedThemes = [];
        $this->selectAll = false;
    }

    /**
     * Bulk remove from gallery.
     */
    public function bulkRemoveFromGallery(): void
    {
        if (empty($this->selectedThemes)) {
            $this->dispatch('notify', message: 'No themes selected.', type: 'warning');

            return;
        }

        $count = Theme::whereIn('id', $this->selectedThemes)
            ->update(['is_gallery' => false]);

        $this->dispatch('notify', message: "{$count} themes removed from gallery.", type: 'success');
        $this->selectedThemes = [];
        $this->selectAll = false;
    }

    /**
     * Bulk update category.
     */
    public function bulkUpdateCategory(string $category): void
    {
        if (empty($this->selectedThemes)) {
            $this->dispatch('notify', message: 'No themes selected.', type: 'warning');

            return;
        }

        if (! array_key_exists($category, $this->categories)) {
            $this->dispatch('notify', message: 'Invalid category.', type: 'error');

            return;
        }

        $count = Theme::whereIn('id', $this->selectedThemes)
            ->update(['category' => $category]);

        $this->dispatch('notify', message: "{$count} themes updated.", type: 'success');
        $this->selectedThemes = [];
        $this->selectAll = false;
    }

    /**
     * Toggle select all.
     */
    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedThemes = $this->themes->pluck('id')->toArray();
        } else {
            $this->selectedThemes = [];
        }
    }

    /**
     * Set filter.
     */
    public function setGalleryFilter(string $filter): void
    {
        $this->galleryFilter = $filter;
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
        $this->galleryFilter = 'all';
        $this->resetPage();
    }

    #[Computed]
    public function categoryOptions(): array
    {
        $options = ['all' => 'All categories'];
        foreach ($this->categories as $key => $label) {
            $options[$key] = $label;
        }

        return $options;
    }

    #[Computed]
    public function galleryOptions(): array
    {
        return [
            'all' => 'All visibility',
            'gallery' => 'In gallery',
            'hidden' => 'Hidden',
        ];
    }

    #[Computed]
    public function tableColumns(): array
    {
        return [
            'Theme',
            'Category',
            ['label' => 'Gallery', 'align' => 'center'],
            ['label' => 'Sort Order', 'align' => 'center'],
            ['label' => '', 'align' => 'center'],
        ];
    }

    #[Computed]
    public function tableRows(): array
    {
        return $this->themes->map(function ($theme) {
            return [
                '_id' => $theme->id,
                'cells' => [
                    // Theme name with preview and description
                    [
                        'type' => 'html',
                        'content' => view('webpage::admin.partials.theme-cell', ['theme' => $theme])->render(),
                    ],
                    // Category badge
                    [
                        'type' => 'badge',
                        'label' => $this->categories[$theme->category] ?? ucfirst($theme->category ?? 'general'),
                        'color' => 'violet',
                    ],
                    // Gallery toggle
                    [
                        'type' => 'switch',
                        'click' => "toggleGallery({$theme->id})",
                        'checked' => $theme->is_gallery,
                    ],
                    // Sort order input
                    [
                        'type' => 'input',
                        'inputType' => 'number',
                        'value' => $theme->sort_order ?? 0,
                        'change' => "updateSortOrder({$theme->id}, \$event.target.value)",
                        'class' => 'w-20',
                    ],
                    // Menu actions
                    [
                        'type' => 'menu',
                        'items' => [
                            [
                                'icon' => $theme->is_gallery ? 'eye-slash' : 'eye',
                                'label' => $theme->is_gallery ? 'Hide from gallery' : 'Show in gallery',
                                'click' => "toggleGallery({$theme->id})",
                            ],
                        ],
                    ],
                ],
            ];
        })->all();
    }

    public function render()
    {
        return view('webpage::admin.theme-gallery-manager')
            ->layout('hub::admin.layouts.app', ['title' => 'Theme Gallery']);
    }
}
