<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Template Gallery</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Browse templates to quickly set up your biolinks</p>
        </div>
    </div>

    {{-- Filters --}}
    <flux:accordion class="mb-6">
        <flux:accordion.item heading="Filters">
            <div class="flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-2 flex-wrap">
                    <flux:button
                        wire:click="setCategory('all')"
                        size="sm"
                        :variant="$category === 'all' ? 'primary' : 'filled'"
                    >
                        All
                    </flux:button>
                    @foreach($this->categories as $key => $label)
                        <flux:button
                            wire:click="setCategory('{{ $key }}')"
                            size="sm"
                            :variant="$category === $key ? 'primary' : 'filled'"
                        >
                            {{ $label }}
                        </flux:button>
                    @endforeach
                </div>
                <div class="flex-1 max-w-xs">
                    <flux:input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search templates..."
                        icon="magnifying-glass"
                        clearable
                    />
                </div>
                @if($search || $category !== 'all')
                    <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">
                        Clear filters
                    </flux:button>
                @endif
            </div>
        </flux:accordion.item>
    </flux:accordion>

    {{-- Premium access banner --}}
    @if(!$this->hasPremiumAccess)
        <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-lg p-4 mb-6 text-white">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-white/20 rounded-full">
                    <i class="fa-solid fa-crown text-xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-semibold">Unlock Premium Templates</h3>
                    <p class="text-sm text-white/90">Upgrade to Pro or Ultimate to access all premium templates with advanced blocks and features.</p>
                </div>
                <a href="{{ route('hub.billing.index') }}" class="btn bg-white text-orange-600 hover:bg-white/90">
                    Upgrade Now
                </a>
            </div>
        </div>
    @endif

    {{-- Templates grid --}}
    <div wire:loading.class="opacity-50 pointer-events-none">
    @if($this->templates->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($this->templates as $template)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden group" wire:key="template-{{ $template->id }}">
                    {{-- Template preview --}}
                    <div class="h-40 relative bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                        @if($template->preview_image)
                            <img src="{{ $template->preview_image }}" alt="{{ $template->name }}" class="w-full h-full object-cover">
                        @else
                            <div class="text-white text-center p-4">
                                <i class="fa-solid fa-layer-group text-4xl mb-2 opacity-50"></i>
                                <p class="text-sm opacity-75">{{ $template->blocks_json->count() ?? 0 }} blocks</p>
                            </div>
                        @endif

                        {{-- Badges --}}
                        <div class="absolute top-2 left-2 flex items-center gap-1">
                            @if($template->is_premium && !$this->hasPremiumAccess)
                                <core:badge color="amber" icon="crown">Premium</core:badge>
                            @elseif($template->is_premium)
                                <core:badge color="amber" variant="outline" icon="crown">Premium</core:badge>
                            @endif
                            @if($template->is_system)
                                <flux:badge color="zinc">Official</flux:badge>
                            @endif
                        </div>

                        {{-- Category badge --}}
                        <div class="absolute top-2 right-2">
                            <flux:badge color="zinc" variant="outline">{{ $template->getCategoryName() }}</flux:badge>
                        </div>

                        {{-- Preview button (hover) --}}
                        <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity">
                            <flux:button wire:click="preview({{ $template->id }})" icon="eye">
                                Preview
                            </flux:button>
                        </div>
                    </div>

                    {{-- Template info --}}
                    <div class="p-4">
                        <h3 class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $template->name }}</h3>
                        @if($template->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 mt-1">{{ $template->description }}</p>
                        @endif

                        {{-- Usage stats --}}
                        @if($template->usage_count > 0)
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                                <i class="fa-solid fa-users mr-1"></i> Used {{ number_format($template->usage_count) }} times
                            </p>
                        @endif

                        <div class="flex items-center gap-2 mt-4">
                            @if($template->is_premium && !$this->hasPremiumAccess)
                                <a
                                    href="{{ route('hub.billing.index') }}"
                                    class="flex-1 btn bg-amber-500 hover:bg-amber-600 text-white text-center text-sm"
                                >
                                    <i class="fa-solid fa-crown mr-1"></i> Unlock
                                </a>
                            @else
                                <button
                                    wire:click="openApplyModal({{ $template->id }})"
                                    class="flex-1 btn bg-violet-500 hover:bg-violet-600 text-white text-sm"
                                >
                                    Use Template
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    {{-- Loading indicator --}}
    <div wire:loading class="flex justify-center py-8">
        <flux:icon name="arrow-path" class="size-6 animate-spin text-violet-500" />
    </div>
    @else
    </div>
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-12 px-4">
            <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                <flux:icon name="squares-2x2" class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="text-center">No templates found</flux:heading>
            <flux:subheading class="text-center mt-1 max-w-sm">
                Try adjusting your search or filter.
            </flux:subheading>
            <flux:button wire:click="clearFilters" variant="primary" class="mt-4">
                Clear Filters
            </flux:button>
        </div>
    @endif

    {{-- Preview Modal --}}
    @if($showPreview && $this->previewTemplate)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closePreview"></div>

                <div class="relative z-10 bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                    {{-- Preview header --}}
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $this->previewTemplate->name }}</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->previewTemplate->getCategoryName() }}</p>
                        </div>
                        <button wire:click="closePreview" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fa-solid fa-times text-xl"></i>
                        </button>
                    </div>

                    {{-- Preview content --}}
                    <div class="p-6 overflow-y-auto max-h-[60vh]">
                        @if($this->previewTemplate->description)
                            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $this->previewTemplate->description }}</p>
                        @endif

                        {{-- Blocks preview --}}
                        <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Included Blocks</h4>
                        <div class="space-y-2 mb-6">
                            @foreach($this->previewTemplate->blocks_json as $block)
                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div class="w-8 h-8 flex items-center justify-center bg-violet-100 dark:bg-violet-900/30 rounded">
                                        <i class="fa-solid fa-cube text-violet-500"></i>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize">{{ $block['type'] ?? 'Unknown' }}</span>
                                        @if(isset($block['settings']['text']))
                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $block['settings']['text'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Placeholder variables --}}
                        @if($this->previewTemplate->placeholders && count($this->previewTemplate->placeholders->toArray()) > 0)
                            <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Customisable Variables</h4>
                            <div class="flex flex-wrap gap-2">
                                @foreach($this->previewTemplate->placeholders as $key => $value)
                                    <span class="px-2 py-1 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded">
                                        @{{{{ $key }}}}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Preview footer --}}
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex gap-2">
                        @if($this->previewTemplate->is_premium && !$this->hasPremiumAccess)
                            <a
                                href="{{ route('hub.billing.index') }}"
                                class="flex-1 btn bg-amber-500 hover:bg-amber-600 text-white text-center"
                            >
                                <i class="fa-solid fa-crown mr-2"></i> Unlock Premium
                            </a>
                        @else
                            <button
                                wire:click="openApplyModal({{ $this->previewTemplate->id }})"
                                class="flex-1 btn bg-violet-500 hover:bg-violet-600 text-white"
                            >
                                Use This Template
                            </button>
                        @endif
                        <button
                            wire:click="closePreview"
                            class="btn border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Apply Modal --}}
    @if($showApplyModal && $this->selectedTemplate)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeApplyModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-violet-100 dark:bg-violet-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fa-solid fa-layer-group text-violet-600 dark:text-violet-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    Apply "{{ $this->selectedTemplate->name }}"
                                </h3>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Select a biolink to apply this template
                                    </label>
                                    @if($this->biolinks->count())
                                        <div class="space-y-2 max-h-40 overflow-y-auto mb-4">
                                            @foreach($this->biolinks as $biolink)
                                                <label class="flex items-center p-3 rounded-lg border cursor-pointer transition {{ $selectedBiolinkId === $biolink->id ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-violet-300' }}">
                                                    <input
                                                        type="radio"
                                                        wire:model="selectedBiolinkId"
                                                        value="{{ $biolink->id }}"
                                                        class="text-violet-500 focus:ring-violet-500"
                                                    >
                                                    <span class="ml-3 flex-1">
                                                        <span class="text-gray-900 dark:text-gray-100 font-medium">/{{ $biolink->url }}</span>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>

                                        {{-- Replace existing blocks option --}}
                                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                                            <input type="checkbox" wire:model="replaceExisting" class="rounded text-violet-500 focus:ring-violet-500">
                                            Replace existing blocks (uncheck to add to existing)
                                        </label>

                                        {{-- Customise placeholders button --}}
                                        @if($this->selectedTemplate->placeholders && count($this->selectedTemplate->placeholders->toArray()) > 0)
                                            <button
                                                wire:click="openPlaceholdersModal"
                                                class="text-sm text-violet-600 dark:text-violet-400 hover:underline"
                                            >
                                                <i class="fa-solid fa-edit mr-1"></i> Customise placeholder values
                                            </button>
                                        @endif
                                    @else
                                        <p class="text-gray-500 dark:text-gray-400 text-sm">
                                            No biolinks found. Create a biolink first to apply templates.
                                        </p>
                                        <a href="{{ route('hub.bio.create') }}" class="btn bg-violet-500 hover:bg-violet-600 text-white mt-4">
                                            Create Biolink
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        @if($this->biolinks->count())
                            <button
                                wire:click="applyTemplate"
                                @disabled(!$selectedBiolinkId)
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-violet-600 text-base font-medium text-white hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                Apply Template
                            </button>
                        @endif
                        <button
                            wire:click="closeApplyModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:mt-0 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Placeholders Modal --}}
    @if($showPlaceholdersModal && $this->selectedTemplate)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closePlaceholdersModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">
                            Customise Placeholder Values
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            These values will replace the placeholders in the template.
                        </p>
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            @foreach($placeholderValues as $key => $value)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 capitalize">
                                        {{ str_replace('_', ' ', $key) }}
                                    </label>
                                    <input
                                        type="text"
                                        wire:model="placeholderValues.{{ $key }}"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 text-sm"
                                        placeholder="{{ $value }}"
                                    >
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button
                            wire:click="closePlaceholdersModal"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-violet-600 text-base font-medium text-white hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:w-auto sm:text-sm"
                        >
                            Done
                        </button>
                        <button
                            wire:click="closePlaceholdersModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:mt-0 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
