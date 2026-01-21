<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Theme Gallery</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Browse and apply themes to your biolinks</p>
        </div>
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <button
                wire:click="openCreateModal"
                class="btn bg-violet-500 hover:bg-violet-600 text-white"
            >
                <i class="fa-solid fa-plus mr-2"></i>
                <span>Create Theme</span>
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <flux:accordion class="mb-6">
        <flux:accordion.item heading="Filters">
            <div class="flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-2 flex-wrap">
                    <flux:button
                        wire:click="setFilter('all')"
                        size="sm"
                        :variant="$filter === 'all' ? 'primary' : 'filled'"
                    >
                        All
                    </flux:button>
                    <flux:button
                        wire:click="setFilter('system')"
                        size="sm"
                        icon="star"
                        :variant="$filter === 'system' ? 'primary' : 'filled'"
                    >
                        System
                    </flux:button>
                    <core:button
                        wire:click="setFilter('premium')"
                        size="sm"
                        icon="crown"
                        :variant="$filter === 'premium' ? 'primary' : 'filled'"
                    >
                        Premium
                    </core:button>
                    <flux:button
                        wire:click="setFilter('custom')"
                        size="sm"
                        icon="swatch"
                        :variant="$filter === 'custom' ? 'primary' : 'filled'"
                    >
                        My Themes
                    </flux:button>
                </div>
                <div class="flex-1 max-w-xs">
                    <flux:input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search themes..."
                        icon="magnifying-glass"
                        clearable
                    />
                </div>
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
                    <h3 class="font-semibold">Unlock Premium Themes</h3>
                    <p class="text-sm text-white/90">Upgrade to Pro or Ultimate to access all premium themes and create unlimited custom themes.</p>
                </div>
                <a href="{{ route('hub.billing.index') }}" class="btn bg-white text-orange-600 hover:bg-white/90">
                    Upgrade Now
                </a>
            </div>
        </div>
    @endif

    {{-- Themes grid --}}
    <div wire:loading.class="opacity-50 pointer-events-none">
    @if($this->themes->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($this->themes as $theme)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden group" wire:key="theme-{{ $theme->id }}">
                    {{-- Theme preview --}}
                    <div
                        class="h-40 relative"
                        style="{{ $this->getThemePreviewStyle($theme) }}"
                    >
                        {{-- Badges --}}
                        <div class="absolute top-2 left-2 flex items-center gap-1">
                            @if($theme->is_premium && !$this->hasPremiumAccess)
                                <core:badge color="amber" icon="crown">Premium</core:badge>
                            @elseif($theme->is_premium)
                                <core:badge color="amber" variant="outline" icon="crown">Premium</core:badge>
                            @endif
                            @if($theme->is_system)
                                <flux:badge color="zinc">Official</flux:badge>
                            @endif
                        </div>

                        {{-- Preview button (hover) --}}
                        <div class="absolute inset-0 flex items-center justify-center bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity">
                            <flux:button wire:click="preview({{ $theme->id }})" icon="eye">
                                Preview
                            </flux:button>
                        </div>

                        {{-- Sample buttons to show button style --}}
                        <div class="absolute inset-x-4 bottom-4 space-y-2">
                            @php
                                $btnSettings = $theme->settings['button'] ?? [];
                                $btnBg = $btnSettings['background_color'] ?? '#000000';
                                $btnText = $btnSettings['text_color'] ?? '#ffffff';
                                $btnRadius = $btnSettings['border_radius'] ?? '8px';
                            @endphp
                            <div
                                class="text-center text-sm py-2 px-4 truncate"
                                style="background: {{ $btnBg }}; color: {{ $btnText }}; border-radius: {{ $btnRadius }};"
                            >
                                Sample Button
                            </div>
                        </div>
                    </div>

                    {{-- Theme info --}}
                    <div class="p-4">
                        <h3 class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ $theme->name }}</h3>
                        @if($theme->description)
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate mt-1">{{ $theme->description }}</p>
                        @endif

                        <div class="flex items-center gap-2 mt-4">
                            @if($theme->is_premium && !$this->hasPremiumAccess)
                                <a
                                    href="{{ route('hub.billing.index') }}"
                                    class="flex-1 btn bg-amber-500 hover:bg-amber-600 text-white text-center text-sm"
                                >
                                    <i class="fa-solid fa-crown mr-1"></i> Unlock
                                </a>
                            @else
                                <button
                                    wire:click="openApplyModal({{ $theme->id }})"
                                    class="flex-1 btn bg-violet-500 hover:bg-violet-600 text-white text-sm"
                                >
                                    Apply
                                </button>
                            @endif
                            @if(!$theme->is_system && $theme->user_id === auth()->id())
                                <button
                                    wire:click="deleteTheme({{ $theme->id }})"
                                    wire:confirm="Are you sure you want to delete this theme?"
                                    class="btn border-gray-300 dark:border-gray-600 text-red-500 hover:border-red-500"
                                >
                                    <i class="fa-solid fa-trash"></i>
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
                <flux:icon name="swatch" class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="text-center">
                @if($filter === 'custom')
                    No custom themes yet
                @else
                    No themes found
                @endif
            </flux:heading>
            <flux:subheading class="text-center mt-1 max-w-sm">
                @if($filter === 'custom')
                    Create your first custom theme to personalise your biolinks.
                @else
                    Try adjusting your search or filter.
                @endif
            </flux:subheading>
            @if($filter === 'custom')
                <flux:button wire:click="openCreateModal" icon="plus" variant="primary" class="mt-4">
                    Create your first theme
                </flux:button>
            @endif
        </div>
    @endif

    {{-- Preview Modal --}}
    @if($showPreview && $this->previewTheme)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closePreview"></div>

                <div class="relative z-10 bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full">
                    {{-- Preview area --}}
                    <div
                        class="h-96 rounded-t-lg p-6 flex flex-col items-center justify-center"
                        style="{{ $this->getThemePreviewStyle($this->previewTheme) }}"
                    >
                        @php
                            $settings = $this->previewTheme->settings;
                            $textColor = $settings['text_color'] ?? '#000000';
                            $btnSettings = $settings['button'] ?? [];
                            $btnBg = $btnSettings['background_color'] ?? '#000000';
                            $btnText = $btnSettings['text_color'] ?? '#ffffff';
                            $btnRadius = $btnSettings['border_radius'] ?? '8px';
                            $fontFamily = $settings['font_family'] ?? 'Inter';
                        @endphp
                        <div class="w-20 h-20 rounded-full bg-gray-300 dark:bg-gray-600 mb-4"></div>
                        <h2 class="text-xl font-bold mb-2" style="color: {{ $textColor }}; font-family: '{{ $fontFamily }}', sans-serif;">
                            Your Name
                        </h2>
                        <p class="text-sm mb-6 opacity-80" style="color: {{ $textColor }}; font-family: '{{ $fontFamily }}', sans-serif;">
                            Your bio goes here
                        </p>
                        <div class="w-full max-w-xs space-y-3">
                            <div
                                class="text-center py-3 px-4"
                                style="background: {{ $btnBg }}; color: {{ $btnText }}; border-radius: {{ $btnRadius }}; font-family: '{{ $fontFamily }}', sans-serif;"
                            >
                                Website
                            </div>
                            <div
                                class="text-center py-3 px-4"
                                style="background: {{ $btnBg }}; color: {{ $btnText }}; border-radius: {{ $btnRadius }}; font-family: '{{ $fontFamily }}', sans-serif;"
                            >
                                Instagram
                            </div>
                            <div
                                class="text-center py-3 px-4"
                                style="background: {{ $btnBg }}; color: {{ $btnText }}; border-radius: {{ $btnRadius }}; font-family: '{{ $fontFamily }}', sans-serif;"
                            >
                                Twitter
                            </div>
                        </div>
                    </div>

                    {{-- Modal footer --}}
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="font-medium text-gray-900 dark:text-gray-100">{{ $this->previewTheme->name }}</h3>
                                @if($this->previewTheme->description)
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $this->previewTheme->description }}</p>
                                @endif
                            </div>
                            @if($this->previewTheme->is_premium && !$this->hasPremiumAccess)
                                <core:badge color="amber" icon="crown">Premium</core:badge>
                            @endif
                        </div>
                        <div class="flex gap-2">
                            @if($this->previewTheme->is_premium && !$this->hasPremiumAccess)
                                <a
                                    href="{{ route('hub.billing.index') }}"
                                    class="flex-1 btn bg-amber-500 hover:bg-amber-600 text-white text-center"
                                >
                                    <i class="fa-solid fa-crown mr-2"></i> Unlock Premium
                                </a>
                            @else
                                <button
                                    wire:click="openApplyModal({{ $this->previewTheme->id }})"
                                    class="flex-1 btn bg-violet-500 hover:bg-violet-600 text-white"
                                >
                                    Apply Theme
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
        </div>
    @endif

    {{-- Apply Modal --}}
    @if($showApplyModal && $this->selectedTheme)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeApplyModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-violet-100 dark:bg-violet-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fa-solid fa-palette text-violet-600 dark:text-violet-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    Apply "{{ $this->selectedTheme->name }}"
                                </h3>
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Select a biolink to apply this theme
                                    </label>
                                    @if($this->biolinks->count())
                                        <div class="space-y-2 max-h-60 overflow-y-auto">
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
                                                        @if($biolink->theme_id === $this->selectedTheme->id)
                                                            <span class="ml-2 text-xs text-violet-500">(Current theme)</span>
                                                        @endif
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-gray-500 dark:text-gray-400 text-sm">
                                            No biolinks found. Create a biolink first to apply themes.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button
                            wire:click="applyTheme"
                            @disabled(!$selectedBiolinkId)
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-violet-600 text-base font-medium text-white hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Apply to Selected
                        </button>
                        @if($this->biolinks->count() > 1)
                            <button
                                wire:click="applyToAll"
                                wire:confirm="This will apply this theme to all your bio. Continue?"
                                class="w-full inline-flex justify-center rounded-md border border-violet-300 dark:border-violet-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-violet-700 dark:text-violet-300 hover:bg-violet-50 dark:hover:bg-violet-900/20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:w-auto sm:text-sm"
                            >
                                Apply to All
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

    {{-- Create Theme Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeCreateModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <form wire:submit="createTheme">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-violet-100 dark:bg-violet-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fa-solid fa-plus text-violet-600 dark:text-violet-400"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        Create custom theme
                                    </h3>
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Theme name</label>
                                        <input
                                            type="text"
                                            wire:model="newThemeName"
                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                            placeholder="My Custom Theme"
                                        >
                                        @error('newThemeName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                        This will create a theme with default settings. You can customise it by opening a biolink and editing the theme from there.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-violet-600 text-base font-medium text-white hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Create
                            </button>
                            <button
                                type="button"
                                wire:click="closeCreateModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
