<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('hub.bio.edit', $biolinkId) }}" wire:navigate class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Form Submissions</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">/{{ $this->biolink->url }}</p>
                </div>
            </div>
        </div>
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <button
                wire:click="exportCsv"
                class="btn border-gray-300 dark:border-gray-600 hover:border-violet-500 text-gray-700 dark:text-gray-300"
            >
                <i class="fa-solid fa-download mr-2"></i> Export CSV
            </button>
            <a
                href="{{ route('hub.bio.edit', $biolinkId) }}"
                wire:navigate
                class="btn border-gray-300 dark:border-gray-600 hover:border-violet-500 text-gray-700 dark:text-gray-300"
            >
                <i class="fa-solid fa-pen-to-square mr-2"></i> Edit
            </a>
        </div>
    </div>

    {{-- Stats cards --}}
    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 cursor-pointer hover:ring-2 hover:ring-violet-500 transition-all {{ $filterType === 'email' ? 'ring-2 ring-violet-500' : '' }}"
             wire:click="setTypeFilter({{ $filterType === 'email' ? 'null' : '\'email\'' }})">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fa-solid fa-envelope text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Email Subscribers</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($this->countsByType['email'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 cursor-pointer hover:ring-2 hover:ring-violet-500 transition-all {{ $filterType === 'phone' ? 'ring-2 ring-violet-500' : '' }}"
             wire:click="setTypeFilter({{ $filterType === 'phone' ? 'null' : '\'phone\'' }})">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <i class="fa-solid fa-phone text-green-600 dark:text-green-400 text-xl"></i>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Phone Subscribers</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($this->countsByType['phone'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 cursor-pointer hover:ring-2 hover:ring-violet-500 transition-all {{ $filterType === 'contact' ? 'ring-2 ring-violet-500' : '' }}"
             wire:click="setTypeFilter({{ $filterType === 'contact' ? 'null' : '\'contact\'' }})">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <i class="fa-solid fa-message text-amber-600 dark:text-amber-400 text-xl"></i>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Contact Messages</div>
                    <div class="text-3xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($this->countsByType['contact'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <flux:accordion class="mb-6">
        <flux:accordion.item heading="Filters">
            <div class="flex flex-wrap gap-4 items-end">
                <flux:field class="flex-1 min-w-[150px]">
                    <flux:label>Block</flux:label>
                    <flux:select wire:model.live="filterBlockId">
                        <option value="">All blocks</option>
                        @foreach($this->collectorBlocks as $id => $type)
                            <option value="{{ $id }}">{{ ucfirst(str_replace('_collector', '', $type)) }} (#{{ $id }})</option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:field class="flex-1 min-w-[150px]">
                    <flux:label>From</flux:label>
                    <flux:input type="date" wire:model.live="dateFrom" />
                </flux:field>
                <flux:field class="flex-1 min-w-[150px]">
                    <flux:label>To</flux:label>
                    <flux:input type="date" wire:model.live="dateTo" />
                </flux:field>
                <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">
                    Clear filters
                </flux:button>
            </div>
        </flux:accordion.item>
    </flux:accordion>

    {{-- Submissions table --}}
    <div wire:loading.class="opacity-50 pointer-events-none">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
        @if($this->submissions->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 px-4">
                <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                    <flux:icon name="inbox" class="size-8 text-zinc-400" />
                </div>
                <flux:heading size="lg" class="text-center">No submissions yet</flux:heading>
                <flux:subheading class="text-center mt-1 max-w-sm">
                    Form submissions will appear here when visitors fill in your collector blocks.
                </flux:subheading>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Country</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->submissions as $submission)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-900/30">
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($submission->type === 'email')
                                    <flux:badge color="blue">Email</flux:badge>
                                @elseif($submission->type === 'phone')
                                    <flux:badge color="green">Phone</flux:badge>
                                @elseif($submission->type === 'contact')
                                    <flux:badge color="amber">Contact</flux:badge>
                                @else
                                    <flux:badge color="zinc">{{ ucfirst($submission->type) }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $submission->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                @if($submission->email)
                                    <a href="mailto:{{ $submission->email }}" class="text-violet-600 hover:text-violet-700 dark:text-violet-400">
                                        {{ $submission->email }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                @if($submission->phone)
                                    <a href="tel:{{ $submission->phone }}" class="text-violet-600 hover:text-violet-700 dark:text-violet-400">
                                        {{ $submission->phone }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $submission->country_code ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $submission->created_at->format('j M Y, g:ia') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <flux:button.group>
                                    @if($submission->message)
                                        <flux:button
                                            x-data
                                            x-on:click="$dispatch('open-modal', { id: 'message-{{ $submission->id }}' })"
                                            variant="ghost"
                                            size="sm"
                                            icon="eye"
                                            tooltip="View message"
                                            square
                                        />
                                    @endif
                                    <flux:button
                                        wire:click="confirmDelete({{ $submission->id }})"
                                        variant="danger"
                                        size="sm"
                                        icon="trash"
                                        tooltip="Delete"
                                        square
                                    />
                                </flux:button.group>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $this->submissions->links() }}
            </div>
        @endif
    </div>
    </div>
    {{-- Loading indicator --}}
    <div wire:loading class="flex justify-center py-8">
        <flux:icon name="arrow-path" class="size-6 animate-spin text-violet-500" />
    </div>

    {{-- Delete confirmation modal --}}
    @if($showDeleteConfirm)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Delete submission?</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">This action cannot be undone. The submission data will be permanently removed.</p>
                <div class="flex justify-end gap-3">
                    <button
                        wire:click="cancelDelete"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="deleteSubmission"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
