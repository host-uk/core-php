<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('web::web.projects.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('web::web.projects.subtitle') }}</p>
        </div>
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <flux:button :href="route('hub.bio.index')" wire:navigate icon="arrow-left">
                {{ __('web::web.actions.back_to_pages') }}
            </flux:button>
            <flux:button wire:click="openCreateModal" variant="primary" icon="plus">
                {{ __('web::web.projects.new') }}
            </flux:button>
        </div>
    </div>

    {{-- Projects grid --}}
    <div wire:loading.class="opacity-50 pointer-events-none">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        {{-- Unassigned "project" card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden border-2 border-dashed border-gray-300 dark:border-gray-600">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-10 h-10 rounded-lg flex items-center justify-center"
                        style="background-color: #9ca3af"
                    >
                        <i class="fa-solid fa-folder-open text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ __('web::web.projects.unassigned') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('web::web.projects.unassigned_description') }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
                    <span>
                        <i class="fa-solid fa-link mr-1"></i>
                        {{ __('web::web.projects.pages_count', ['count' => $this->unassignedCount]) }}
                    </span>
                </div>

                @if($this->unassignedCount > 0)
                    <core:button wire:click="openMoveModal(-1)" icon="arrows-alt" class="w-full">
                        {{ __('web::web.projects.move_to_project') }}
                    </core:button>
                @endif
            </div>
        </div>

        {{-- Project cards --}}
        @foreach($this->projects as $project)
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden group"
                wire:key="project-{{ $project->id }}"
                x-data="{ showActions: false }"
                @mouseenter="showActions = true"
                @mouseleave="showActions = false"
            >
                {{-- Colour bar --}}
                <div class="h-2" style="background-color: {{ $project->color }}"></div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center"
                                style="background-color: {{ $project->color }}20"
                            >
                                <i class="fa-solid fa-folder" style="color: {{ $project->color }}"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ $project->name }}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('web::web.projects.created', ['time' => $project->created_at->diffForHumans()]) }}
                                </p>
                            </div>
                        </div>

                        {{-- Actions dropdown --}}
                        <div x-show="showActions" x-cloak>
                            <flux:dropdown align="end">
                                <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" :tooltip="__('web::web.tooltips.actions')" square />

                                <flux:menu>
                                    <flux:menu.item wire:click="openEditModal({{ $project->id }})" icon="pencil">
                                        {{ __('web::web.tooltips.edit') }}
                                    </flux:menu.item>
                                    @if($project->biolinks_count > 0)
                                        <flux:menu.item wire:click="openMoveModal({{ $project->id }})" icon="arrows-right-left">
                                            {{ __('web::web.projects.move.title') }}
                                        </flux:menu.item>
                                    @endif
                                    <flux:menu.separator />
                                    <flux:menu.item wire:click="confirmDelete({{ $project->id }})" icon="trash" variant="danger">
                                        {{ __('web::web.actions.delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    </div>

                    {{-- Stats --}}
                    <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-4">
                        <span>
                            <i class="fa-solid fa-link mr-1"></i>
                            {{ __('web::web.projects.pages_count', ['count' => $project->biolinks_count]) }}
                        </span>
                        <span>
                            <i class="fa-solid fa-eye mr-1"></i>
                            {{ __('web::web.projects.clicks_count', ['count' => number_format($project->total_clicks)]) }}
                        </span>
                    </div>

                    {{-- Quick actions --}}
                    <div class="flex items-center gap-2">
                        <flux:button :href="route('hub.bio.index', ['project' => $project->id])" wire:navigate variant="primary" icon="eye" class="flex-1">
                            {{ __('web::web.projects.view_pages') }}
                        </flux:button>
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

    {{-- Empty state --}}
    @if($this->projects->isEmpty() && $this->unassignedCount === 0)
        <div class="flex flex-col items-center justify-center py-12 px-4 mt-6">
            <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                <flux:icon name="folder-plus" class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="text-center">{{ __('web::web.projects.empty.title') }}</flux:heading>
            <flux:subheading class="text-center mt-1 max-w-sm">
                {{ __('web::web.projects.empty.message') }}
            </flux:subheading>
            <flux:button wire:click="openCreateModal" variant="primary" icon="plus" class="mt-4">
                {{ __('web::web.projects.empty.action') }}
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
                                    style="background-color: {{ $color }}20"
                                >
                                    <i class="fa-solid fa-folder" style="color: {{ $color }}"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        {{ $editingProjectId ? __('web::web.projects.edit_title') : __('web::web.projects.create_title') }}
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        {{-- Name field --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                {{ __('web::web.projects.modal.name_label') }}
                                            </label>
                                            <input
                                                type="text"
                                                wire:model="name"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                placeholder="{{ __('web::web.projects.modal.name_placeholder') }}"
                                            >
                                            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>

                                        {{-- Colour picker --}}
                                        <flux:field>
                                            <flux:label>{{ __('web::web.projects.modal.colour_label') }}</flux:label>
                                            <div class="grid grid-cols-8 gap-2" x-data="{ selected: @entangle('color') }">
                                                @foreach($this->colours as $hex => $label)
                                                    <button
                                                        type="button"
                                                        wire:click="$set('color', '{{ $hex }}')"
                                                        class="w-8 h-8 rounded-lg transition-all hover:scale-110 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 dark:focus:ring-offset-gray-800"
                                                        :class="selected === '{{ $hex }}' ? 'ring-2 ring-offset-2 ring-violet-500 dark:ring-offset-gray-800 scale-110' : ''"
                                                        style="background-color: {{ $hex }}"
                                                        title="{{ $label }}"
                                                        x-tooltip="'{{ $label }}'"
                                                    >
                                                        <span x-show="selected === '{{ $hex }}'" x-cloak class="flex items-center justify-center h-full">
                                                            <flux:icon name="check" class="size-4 text-white drop-shadow-md" />
                                                        </span>
                                                    </button>
                                                @endforeach
                                            </div>
                                            <flux:error name="color" />
                                        </flux:field>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                            <flux:button type="submit" variant="primary">
                                {{ $editingProjectId ? __('web::web.projects.modal.save_button') : __('web::web.projects.modal.create_button') }}
                            </flux:button>
                            <flux:button wire:click="closeModal">
                                {{ __('web::web.actions.cancel') }}
                            </flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteModal)
        @php
            $deletingProject = $this->projects->firstWhere('id', $deletingProjectId);
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
                                    {{ __('web::web.projects.delete.title') }}
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('web::web.projects.delete.confirm', ['name' => $deletingProject?->name ?? 'this project']) }}
                                        @if($deletingProject && $deletingProject->biolinks_count > 0)
                                            {{ __('web::web.projects.delete.has_pages', ['count' => $deletingProject->biolinks_count]) }}
                                        @endif
                                    </p>
                                </div>

                                @if($deletingProject && $deletingProject->biolinks_count > 0)
                                    <div class="mt-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            {{ __('web::web.projects.delete.action_label') }}
                                        </label>
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input
                                                    type="radio"
                                                    wire:model="deleteAction"
                                                    value="unassign"
                                                    class="text-violet-600 focus:ring-violet-500"
                                                >
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                    {{ __('web::web.projects.delete.keep') }}
                                                </span>
                                            </label>
                                            <label class="flex items-center">
                                                <input
                                                    type="radio"
                                                    wire:model="deleteAction"
                                                    value="delete"
                                                    class="text-red-600 focus:ring-red-500"
                                                >
                                                <span class="ml-2 text-sm text-red-600 dark:text-red-400">
                                                    {{ __('web::web.projects.delete.delete_all') }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <flux:button wire:click="deleteProject" variant="danger">
                            {{ __('web::web.projects.delete.button') }}
                        </flux:button>
                        <flux:button wire:click="closeDeleteModal">
                            {{ __('web::web.actions.cancel') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Move Pages Modal --}}
    @if($showMoveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeMoveModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-violet-100 dark:bg-violet-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fa-solid fa-arrows-alt text-violet-600 dark:text-violet-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    {{ __('web::web.projects.move.title') }}
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('web::web.projects.move.description', ['source' => $sourceProjectId === -1 ? __('web::web.projects.unassigned') : ($this->projects->firstWhere('id', $sourceProjectId)?->name ?? 'Unknown')]) }}
                                    </p>
                                </div>

                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ __('web::web.projects.move.destination') }}
                                    </label>
                                    <select
                                        wire:model="targetProjectId"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                    >
                                        <option value="">{{ __('web::web.projects.move.select_placeholder') }}</option>
                                        @if($sourceProjectId !== -1)
                                            <option value="-1">{{ __('web::web.projects.unassigned') }}</option>
                                        @endif
                                        @foreach($this->projects as $project)
                                            @if($project->id !== $sourceProjectId)
                                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <flux:button wire:click="moveBiolinks" variant="primary" :disabled="$targetProjectId === null">
                            {{ __('web::web.projects.move.button') }}
                        </flux:button>
                        <flux:button wire:click="closeMoveModal">
                            {{ __('web::web.actions.cancel') }}
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
