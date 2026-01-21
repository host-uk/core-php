<admin:module title="Theme Gallery" subtitle="Manage which themes appear in the public gallery">
    <admin:flash />

    <admin:filter-bar cols="4">
        <admin:search model="search" placeholder="Search themes..." />
        <admin:filter model="categoryFilter" :options="$this->categoryOptions" />
        <admin:filter model="galleryFilter" :options="$this->galleryOptions" />
        <admin:clear-filters :show="$search || $categoryFilter !== 'all' || $galleryFilter !== 'all'" />
    </admin:filter-bar>

    {{-- Bulk Actions --}}
    @if(count($selectedThemes) > 0)
        <div class="mb-4 flex items-center gap-4 rounded-lg bg-violet-50 p-4 dark:bg-violet-900/20">
            <span class="font-medium text-gray-900 dark:text-gray-100">{{ count($selectedThemes) }} selected</span>
            <core:button wire:click="bulkAddToGallery" size="sm" variant="primary">
                Add to gallery
            </core:button>
            <core:button wire:click="bulkRemoveFromGallery" size="sm" variant="ghost">
                Remove from gallery
            </core:button>
            <flux:dropdown>
                <flux:button size="sm" variant="ghost">Set category</flux:button>
                <flux:menu>
                    @foreach($this->categories as $key => $label)
                        <flux:menu.item wire:click="bulkUpdateCategory('{{ $key }}')">
                            {{ $label }}
                        </flux:menu.item>
                    @endforeach
                </flux:menu>
            </flux:dropdown>
        </div>
    @endif

    <admin:editable-table
        :columns="$this->tableColumns"
        :rows="$this->tableRows"
        :pagination="$this->themes"
        :selectable="true"
        selectModel="selectedThemes"
        empty="No themes found."
        emptyIcon="palette"
    />
</admin:module>
