<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Short Links</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create and manage redirect links</p>
        </div>
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            {{-- Usage indicator --}}
            @if($this->entitlementSummary)
                <div class="flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        @if($this->entitlementSummary['unlimited'])
                            <i class="fa-solid fa-infinity mr-1"></i> Unlimited
                        @else
                            {{ number_format($this->entitlementSummary['used']) }} / {{ number_format($this->entitlementSummary['limit']) }}
                        @endif
                    </span>
                </div>
            @endif
            {{-- Create button --}}
            <button
                wire:click="openCreateModal"
                @disabled(!$canCreate)
                class="btn bg-violet-500 hover:bg-violet-600 text-white disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <i class="fa-solid fa-plus mr-2"></i>
                <span>New Short Link</span>
            </button>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-violet-100 dark:bg-violet-900/30">
                    <i class="fa-solid fa-link text-violet-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Links</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($this->stats['total']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30">
                    <i class="fa-solid fa-toggle-on text-green-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($this->stats['enabled']) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <i class="fa-solid fa-eye text-blue-500"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Clicks</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($this->stats['clicks']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Project filter bar --}}
    @if($this->projects->count() > 0 || $project !== null)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 mb-4">
            <div class="flex items-center gap-2 overflow-x-auto">
                <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">Project:</span>
                <button
                    wire:click="clearProjectFilter"
                    class="px-3 py-1.5 text-sm rounded-md whitespace-nowrap {{ $project === null ? 'bg-violet-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                >
                    All
                </button>
                <button
                    wire:click="filterByProject(-1)"
                    class="px-3 py-1.5 text-sm rounded-md whitespace-nowrap {{ $project === -1 ? 'bg-violet-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                >
                    <i class="fa-solid fa-folder-open mr-1 text-gray-400"></i>
                    Unassigned
                </button>
                @foreach($this->projects as $proj)
                    <button
                        wire:click="filterByProject({{ $proj->id }})"
                        class="px-3 py-1.5 text-sm rounded-md whitespace-nowrap flex items-center gap-2 {{ $project === $proj->id ? 'bg-violet-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                    >
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $proj->color }}"></span>
                        {{ $proj->name }}
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <flux:accordion class="mb-6">
        <flux:accordion.item heading="Filters">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Search</flux:label>
                    <flux:input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by URL or destination..."
                        icon="magnifying-glass"
                        clearable
                    />
                </flux:field>
                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model.live="statusFilter">
                        <option value="">All</option>
                        <option value="enabled">Enabled</option>
                        <option value="disabled">Disabled</option>
                    </flux:select>
                </flux:field>
            </div>
        </flux:accordion.item>
    </flux:accordion>

    {{-- Links table --}}
    <div wire:loading.class="opacity-50 pointer-events-none">
    @if($this->shortLinks->count())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Short URL</th>
                            <th class="px-4 py-3 text-left text-gray-500 dark:text-gray-400 font-medium">Destination</th>
                            <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-medium">Clicks</th>
                            <th class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 font-medium">Status</th>
                            <th class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->shortLinks as $link)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30" wire:key="link-{{ $link->id }}">
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-3">
                                        @if($link->project)
                                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $link->project->color }}" title="{{ $link->project->name }}"></span>
                                        @endif
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">/{{ $link->url }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $link->full_url }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <a href="{{ $link->location_url }}" target="_blank" class="text-gray-600 dark:text-gray-300 hover:text-violet-500 truncate block max-w-xs" title="{{ $link->location_url }}">
                                        {{ Str::limit($link->location_url, 50) }}
                                        <i class="fa-solid fa-arrow-up-right-from-square ml-1 text-xs"></i>
                                    </a>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="text-gray-900 dark:text-gray-100 font-medium">{{ number_format($link->clicks) }}</span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <button wire:click="toggleEnabled({{ $link->id }})">
                                        @if($link->is_enabled)
                                            <flux:badge color="green" icon="check">Active</flux:badge>
                                        @else
                                            <flux:badge color="zinc" icon="pause">Paused</flux:badge>
                                        @endif
                                    </button>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button.group>
                                            {{-- Copy URL --}}
                                            <flux:button
                                                x-data="{ copied: false }"
                                                x-on:click="
                                                    navigator.clipboard.writeText('{{ $link->full_url }}');
                                                    copied = true;
                                                    $dispatch('notify', { message: 'Link copied to clipboard', type: 'success' });
                                                    setTimeout(() => copied = false, 2000);
                                                "
                                                variant="ghost"
                                                size="sm"
                                                icon="clipboard"
                                                tooltip="Copy URL"
                                                square
                                            />
                                            {{-- QR Code --}}
                                            <flux:button
                                                :href="route('hub.bio.qr', $link->id)"
                                                wire:navigate
                                                variant="ghost"
                                                size="sm"
                                                icon="qr-code"
                                                tooltip="QR Code"
                                                square
                                            />
                                            {{-- Analytics --}}
                                            <flux:button
                                                :href="route('hub.bio.analytics', $link->id)"
                                                wire:navigate
                                                variant="ghost"
                                                size="sm"
                                                icon="chart-bar"
                                                tooltip="Analytics"
                                                square
                                            />
                                            {{-- Duplicate --}}
                                            <flux:button
                                                wire:click="duplicate({{ $link->id }})"
                                                variant="ghost"
                                                size="sm"
                                                icon="document-duplicate"
                                                tooltip="Duplicate"
                                                square
                                            />
                                            {{-- Delete --}}
                                            <flux:button
                                                wire:click="delete({{ $link->id }})"
                                                wire:confirm="Are you sure you want to delete this short link?"
                                                variant="danger"
                                                size="sm"
                                                icon="trash"
                                                tooltip="Delete"
                                                square
                                            />
                                        </flux:button.group>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $this->shortLinks->links() }}
            </div>
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
                <flux:icon name="link" class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="text-center">No short links yet</flux:heading>
            <flux:subheading class="text-center mt-1 max-w-sm">
                Create your first short link to start tracking clicks.
            </flux:subheading>
            <flux:button wire:click="openCreateModal" :disabled="!$canCreate" icon="plus" variant="primary" class="mt-4">
                Create your first short link
            </flux:button>
        </div>
    @endif

    {{-- Create Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                {{-- Background overlay --}}
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeCreateModal"></div>

                {{-- Modal panel --}}
                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <form wire:submit="create">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fa-solid fa-link text-blue-600 dark:text-blue-400"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        Create short link
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Short URL</label>
                                            <div class="flex rounded-md shadow-sm">
                                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm">
                                                    bio.host.uk.com/
                                                </span>
                                                <input
                                                    type="text"
                                                    wire:model="newUrl"
                                                    class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                                    placeholder="my-link"
                                                >
                                            </div>
                                            @error('newUrl') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Destination URL</label>
                                            <input
                                                type="url"
                                                wire:model="newLocationUrl"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                                placeholder="https://example.com/your-page"
                                            >
                                            @error('newLocationUrl') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                        </div>
                                        @if($this->projects->count() > 0)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project (optional)</label>
                                                <select
                                                    wire:model="newProjectId"
                                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                >
                                                    <option value="">No project</option>
                                                    @foreach($this->projects as $proj)
                                                        <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    </div>
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
