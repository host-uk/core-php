<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('webpage::bio.index.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('webpage::bio.index.subtitle') }}</p>
        </div>
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            {{-- Manage Projects link --}}
            <flux:button :href="route('hub.bio.projects')" wire:navigate icon="folder">
                {{ __('webpage::bio.actions.projects') }}
            </flux:button>
            {{-- Dropdown for creating different link types --}}
            <flux:dropdown align="end">
                <flux:button variant="primary" icon="plus" icon:trailing="chevron-down">
                    {{ __('webpage::bio.actions.new') }}
                </flux:button>

                <flux:menu>
                    <flux:menu.item wire:click="openCreateModal" icon="user-circle">
                        {{ __('webpage::bio.types.bio_page') }}
                    </flux:menu.item>
                    <flux:menu.item wire:click="createShortLink" icon="link">
                        {{ __('webpage::bio.types.short_link') }}
                    </flux:menu.item>
                    <flux:menu.separator />
                    <flux:menu.item wire:click="createFileLink" icon="file">
                        {{ __('webpage::bio.types.file_link') }}
                    </flux:menu.item>
                    <flux:menu.item wire:click="createVcard" icon="address-card">
                        {{ __('webpage::bio.types.vcard') }}
                    </flux:menu.item>
                    <flux:menu.item wire:click="createEvent" icon="calendar">
                        {{ __('webpage::bio.types.event') }}
                    </flux:menu.item>
                </flux:menu>
            </flux:dropdown>
        </div>
    </div>

    {{-- Project filter bar (if projects exist) --}}
    @if($this->projects->count() > 0 || $project !== null)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-3 mb-4">
            <div class="flex items-center gap-2 overflow-x-auto">
                <span class="text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">Project:</span>
                <flux:button
                    wire:click="clearProjectFilter"
                    size="sm"
                    :variant="$project === null ? 'primary' : 'filled'"
                >
                    All
                </flux:button>
                <flux:button
                    wire:click="filterByProject(-1)"
                    size="sm"
                    icon="folder-open"
                    :variant="$project === -1 ? 'primary' : 'filled'"
                >
                    Unassigned
                </flux:button>
                @foreach($this->projects as $proj)
                    <flux:button
                        wire:click="filterByProject({{ $proj->id }})"
                        size="sm"
                        :variant="$project === $proj->id ? 'primary' : 'filled'"
                    >
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $proj->color }}"></span>
                        {{ $proj->name }}
                    </flux:button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <flux:accordion class="mb-6">
        <flux:accordion.item heading="Filters">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <flux:field>
                    <flux:label>Search</flux:label>
                    <flux:input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by URL..."
                        icon="magnifying-glass"
                        clearable
                    />
                </flux:field>
                <flux:field>
                    <flux:label>Type</flux:label>
                    <flux:select wire:model.live="typeFilter">
                        <option value="">All Types</option>
                        @foreach($this->linkTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
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

    {{-- BioLinks grid --}}
    <div wire:loading.class="opacity-50 pointer-events-none">
    @if($this->biolinks->count())
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($this->biolinks as $biolink)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden group" wire:key="biolink-{{ $biolink->id }}">
                    {{-- Preview header - different gradient for short links --}}
                    <div class="h-32 relative {{ $biolink->type === 'link' ? 'bg-gradient-to-br from-blue-500 to-cyan-600' : 'bg-gradient-to-br from-violet-500 to-purple-600' }}">
                        {{-- Badges in top-left --}}
                        <div class="absolute top-2 left-2 flex items-center gap-1 flex-wrap">
                            @if($biolink->type === 'link')
                                <flux:badge color="zinc" variant="outline" icon="link">Short Link</flux:badge>
                            @endif
                            @if(!$biolink->is_enabled)
                                <flux:badge color="zinc">Disabled</flux:badge>
                            @endif
                            @if($biolink->project)
                                <flux:badge icon="folder" style="background-color: {{ $biolink->project->color }}99; color: white;">
                                    {{ Str::limit($biolink->project->name, 12) }}
                                </flux:badge>
                            @endif
                        </div>
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <flux:button.group>
                                <flux:button
                                    wire:click="openMoveModal({{ $biolink->id }})"
                                    variant="filled"
                                    size="sm"
                                    icon="folder"
                                    tooltip="Move to project"
                                    square
                                />
                                <flux:button
                                    wire:click="duplicate({{ $biolink->id }})"
                                    variant="filled"
                                    size="sm"
                                    icon="copy"
                                    tooltip="Duplicate"
                                    square
                                />
                                <flux:button
                                    wire:click="delete({{ $biolink->id }})"
                                    wire:confirm="Are you sure you want to delete this biolink?"
                                    variant="danger"
                                    size="sm"
                                    icon="trash"
                                    tooltip="Delete"
                                    square
                                />
                            </flux:button.group>
                        </div>
                        <div class="absolute inset-x-0 bottom-0 p-4 bg-gradient-to-t from-black/50 to-transparent">
                            <h3 class="text-white font-semibold truncate">/{{ $biolink->url }}</h3>
                            @if($biolink->type === 'link' && $biolink->location_url)
                                <p class="text-white/80 text-sm truncate" title="{{ $biolink->location_url }}">
                                    <i class="fa-solid fa-arrow-right mr-1"></i> {{ parse_url($biolink->location_url, PHP_URL_HOST) }}
                                </p>
                            @else
                                <p class="text-white/80 text-sm">{{ $biolink->blocks_count }} blocks</p>
                            @endif
                        </div>
                    </div>

                    {{-- Stats and actions --}}
                    <div class="p-4">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <div class="flex items-center gap-4">
                                <span><i class="fa-solid fa-eye mr-1"></i> {{ number_format($biolink->clicks) }}</span>
                                <span class="capitalize">{{ $biolink->type }}</span>
                            </div>
                            <span>{{ $biolink->created_at->diffForHumans() }}</span>
                        </div>

                        <div class="flex items-center gap-2">
                            @if($biolink->type === 'biolink')
                                <core:button :href="route('hub.bio.edit', $biolink->id)" wire:navigate variant="primary" icon="pen-to-square" class="flex-1">
                                    Edit
                                </core:button>
                            @elseif($biolink->type === 'link')
                                {{-- Copy URL button for short links --}}
                                <flux:button
                                    x-data="{ copied: false }"
                                    x-on:click="
                                        navigator.clipboard.writeText('{{ $biolink->full_url }}');
                                        copied = true;
                                        $dispatch('notify', { message: 'Link copied to clipboard', type: 'success' });
                                        setTimeout(() => copied = false, 2000);
                                    "
                                    variant="filled"
                                    class="flex-1"
                                >
                                    <i x-show="!copied" class="fa-solid fa-copy mr-2"></i>
                                    <i x-show="copied" x-cloak class="fa-solid fa-check mr-2"></i>
                                    <span x-show="!copied">Copy Link</span>
                                    <span x-show="copied" x-cloak>Copied</span>
                                </flux:button>
                            @endif
                            <core:button :href="route('hub.bio.qr', $biolink->id)" wire:navigate icon="qrcode" size="sm" tooltip="QR Code" />
                            <core:button :href="route('hub.bio.analytics', $biolink->id)" wire:navigate icon="chart-line" size="sm" tooltip="Analytics" />
                            <core:button :href="$biolink->full_url" target="_blank" icon="arrow-up-right-from-square" size="sm" tooltip="Open in new tab" />
                            <flux:button wire:click="toggleEnabled({{ $biolink->id }})" size="sm" :tooltip="$biolink->is_enabled ? 'Disable' : 'Enable'">
                                <i class="fa-solid {{ $biolink->is_enabled ? 'fa-toggle-on text-green-500' : 'fa-toggle-off text-gray-400' }}"></i>
                            </flux:button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $this->biolinks->links() }}
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
            @if($project)
                <flux:heading size="lg" class="text-center">{{ __('webpage::bio.index.empty.project_empty') }}</flux:heading>
                <flux:subheading class="text-center mt-1 max-w-sm">
                    @if($project === -1)
                        {{ __('webpage::bio.index.empty.project_all_assigned') }}
                    @else
                        {{ __('webpage::bio.index.empty.project_new') }}
                    @endif
                </flux:subheading>
            @else
                <flux:heading size="lg" class="text-center">{{ __('webpage::bio.index.empty.title') }}</flux:heading>
                <flux:subheading class="text-center mt-1 max-w-sm">{{ __('webpage::bio.index.empty.message') }}</flux:subheading>
            @endif
            <flux:button wire:click="openCreateModal" variant="primary" icon="plus" class="mt-4">
                {{ __('webpage::bio.index.empty.action') }}
            </flux:button>
        </div>
    @endif

    {{-- Create Modal --}}
    <flux:modal wire:model="showCreateModal" name="create-biolink" class="max-w-lg">
        <form wire:submit="create" class="space-y-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-violet-100 dark:bg-violet-900/30">
                    <i class="fa-solid fa-plus text-violet-600 dark:text-violet-400"></i>
                </div>
                <flux:heading size="lg">{{ __('webpage::bio.modal.create.title') }}</flux:heading>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('webpage::bio.modal.create.url_label') }}</flux:label>
                    <flux:input.group>
                        <flux:input.group.prefix>bio.host.uk.com/</flux:input.group.prefix>
                        <flux:input
                            wire:model="newUrl"
                            placeholder="{{ __('webpage::bio.modal.create.url_placeholder') }}"
                        />
                    </flux:input.group>
                    <flux:error name="newUrl" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('webpage::bio.modal.create.type_label') }}</flux:label>
                    <flux:select wire:model="newType">
                        @foreach($this->linkTypes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                @if($this->projects->count() > 0)
                    <flux:field>
                        <flux:label>{{ __('webpage::bio.modal.create.project_label') }}</flux:label>
                        <flux:select wire:model="newProjectId">
                            <option value="">{{ __('webpage::bio.modal.create.no_project') }}</option>
                            @foreach($this->projects as $proj)
                                <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                @endif
            </div>

            <div class="flex justify-end gap-3">
                <flux:button x-on:click="$flux.close()">
                    {{ __('webpage::bio.actions.cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary">
                    {{ __('webpage::bio.actions.create') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Move to Project Modal --}}
    <flux:modal wire:model="showMoveModal" name="move-biolink" class="max-w-lg">
        <div class="space-y-6">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full bg-violet-100 dark:bg-violet-900/30">
                    <i class="fa-solid fa-folder text-violet-600 dark:text-violet-400"></i>
                </div>
                <flux:heading size="lg">{{ __('webpage::bio.modal.move.title') }}</flux:heading>
            </div>

            <flux:field>
                <flux:label>{{ __('webpage::bio.modal.move.select_label') }}</flux:label>
                <flux:select wire:model="moveToProjectId">
                    <option value="-1">{{ __('webpage::bio.modal.move.unassigned') }}</option>
                    @foreach($this->projects as $proj)
                        <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex justify-end gap-3">
                <flux:button x-on:click="$flux.close()">
                    {{ __('webpage::bio.actions.cancel') }}
                </flux:button>
                <flux:button wire:click="moveBiolink" variant="primary">
                    {{ __('webpage::bio.actions.move') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
