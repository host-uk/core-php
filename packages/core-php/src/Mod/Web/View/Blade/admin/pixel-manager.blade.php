<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('web::web.pixels.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('web::web.pixels.subtitle') }}</p>
        </div>
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <a
                href="{{ route('hub.bio.index') }}"
                wire:navigate
                class="btn border-gray-300 dark:border-gray-600 hover:border-violet-500 text-gray-700 dark:text-gray-300"
            >
                <i class="fa-solid fa-arrow-left mr-2"></i>
                {{ __('web::web.actions.back_to_pages') }}
            </a>
            <button
                wire:click="openCreateModal"
                class="btn bg-violet-500 hover:bg-violet-600 text-white"
            >
                <i class="fa-solid fa-plus mr-2"></i>
                {{ __('web::web.pixels.add_pixel') }}
            </button>
        </div>
    </div>

    {{-- Info box --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fa-solid fa-info-circle text-blue-500"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    {{ __('web::web.pixels.info') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Pixels grid --}}
    <div wire:loading.class="opacity-50 pointer-events-none">
    @if($this->pixels->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($this->pixels as $pixel)
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden group"
                    wire:key="pixel-{{ $pixel->id }}"
                >
                    {{-- Colour bar --}}
                    <div class="h-2" style="background-color: {{ $this->getPixelColour($pixel->type) }}"></div>

                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-lg flex items-center justify-center"
                                    style="background-color: {{ $this->getPixelColour($pixel->type) }}20"
                                >
                                    <i class="{{ $this->getPixelIcon($pixel->type) }}" style="color: {{ $this->getPixelColour($pixel->type) }}"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $pixel->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $pixel->type_label }}</p>
                                </div>
                            </div>

                            {{-- Actions --}}
                            <flux:button.group>
                                <flux:button
                                    wire:click="openEditModal({{ $pixel->id }})"
                                    variant="ghost"
                                    size="sm"
                                    icon="pencil"
                                    :tooltip="__('web::web.tooltips.edit')"
                                    square
                                />
                                <flux:button
                                    wire:click="confirmDelete({{ $pixel->id }})"
                                    variant="danger"
                                    size="sm"
                                    icon="trash"
                                    :tooltip="__('web::web.tooltips.delete')"
                                    square
                                />
                            </flux:button.group>
                        </div>

                        {{-- Pixel ID (truncated) --}}
                        <div class="mb-4">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">{{ __('web::web.pixels.pixel_id') }}</p>
                            <p class="text-sm text-gray-700 dark:text-gray-300 font-mono truncate" title="{{ $pixel->pixel_id }}">
                                {{ $pixel->pixel_id }}
                            </p>
                        </div>

                        {{-- Stats --}}
                        <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                            <i class="fa-solid fa-link mr-1"></i>
                            <span>{{ __('web::web.pixels.pages_attached', ['count' => $pixel->biolinks_count, 'label' => Str::plural('page', $pixel->biolinks_count)]) }}</span>
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
                <flux:icon name="chart-bar" class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="text-center">{{ __('web::web.pixels.empty.title') }}</flux:heading>
            <flux:subheading class="text-center mt-1 max-w-sm">
                {{ __('web::web.pixels.empty.message') }}
            </flux:subheading>
            <flux:button wire:click="openCreateModal" icon="plus" variant="primary" class="mt-4">
                {{ __('web::web.pixels.add_first') }}
            </flux:button>
        </div>
    @endif

    {{-- Create/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <form wire:submit="save">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div
                                    class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full sm:mx-0 sm:h-10 sm:w-10"
                                    style="background-color: {{ $this->getPixelColour($type) }}20"
                                >
                                    <i class="{{ $this->getPixelIcon($type) }}" style="color: {{ $this->getPixelColour($type) }}"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        {{ $editingPixelId ? __('web::web.pixels.modal.edit_title') : __('web::web.pixels.modal.add_title') }}
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        {{-- Type selector --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {{ __('web::web.pixels.modal.type_label') }}
                                            </label>
                                            <select
                                                wire:model.live="type"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            >
                                                @foreach($this->pixelTypes as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        {{-- Name field --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {{ __('web::web.pixels.modal.name_label') }}
                                            </label>
                                            <input
                                                type="text"
                                                wire:model="name"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                placeholder="{{ __('web::web.pixels.modal.name_placeholder') }}"
                                            >
                                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        {{-- Pixel ID field --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {{ __('web::web.pixels.modal.pixel_id_label') }}
                                            </label>
                                            <input
                                                type="text"
                                                wire:model="pixelId"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 font-mono"
                                                placeholder="{{ $this->getPixelIdPlaceholder() }}"
                                            >
                                            @error('pixelId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('web::web.pixels.modal.pixel_id_hint', ['type' => $this->pixelTypes[$type] ?? $type]) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-violet-600 text-base font-medium text-white hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ $editingPixelId ? __('web::web.pixels.modal.save_button') : __('web::web.pixels.modal.add_button') }}
                            </button>
                            <button
                                type="button"
                                wire:click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ __('web::web.actions.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        @php
            $deletingPixel = $this->pixels->firstWhere('id', $deletingPixelId);
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeDeleteModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fa-solid fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    {{ __('web::web.pixels.delete.title') }}
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('web::web.pixels.delete.confirm', ['name' => $deletingPixel?->name ?? 'this pixel']) }}
                                        @if($deletingPixel && $deletingPixel->biolinks_count > 0)
                                            {{ __('web::web.pixels.delete.has_pages', ['count' => $deletingPixel->biolinks_count, 'label' => Str::plural('page', $deletingPixel->biolinks_count)]) }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            wire:click="deletePixel"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            {{ __('web::web.pixels.delete.button') }}
                        </button>
                        <button
                            type="button"
                            wire:click="closeDeleteModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            {{ __('web::web.actions.cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
