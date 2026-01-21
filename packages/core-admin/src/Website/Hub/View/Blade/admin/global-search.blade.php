{{--
Global search component with ⌘K keyboard shortcut.

Include in your layout:
<admin:global-search />
--}}

<div
    x-data="{
        init() {
            // Listen for ⌘K / Ctrl+K keyboard shortcut
            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    $wire.openSearch();
                }
            });
        }
    }"
    x-on:navigate-to-url.window="Livewire.navigate($event.detail.url)"
>
    {{-- Search trigger button (optional - can be placed in navbar) --}}
    @if(false)
    <button
        wire:click="openSearch"
        type="button"
        class="flex items-center gap-2 rounded-lg bg-zinc-100 px-3 py-2 text-sm text-zinc-500 transition hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700"
    >
        <core:icon name="magnifying-glass" class="h-4 w-4" />
        <span>{{ __('hub::hub.search.button') }}</span>
        <kbd class="ml-2 hidden rounded bg-zinc-200 px-1.5 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400 sm:inline-block">
            ⌘K
        </kbd>
    </button>
    @endif

    {{-- Search modal --}}
    <core:modal wire:model="open" class="max-w-xl" variant="bare">
        <div
            class="overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-zinc-900/5 dark:bg-zinc-800 dark:ring-zinc-700"
            x-on:keydown.arrow-up.prevent="$wire.navigateUp()"
            x-on:keydown.arrow-down.prevent="$wire.navigateDown()"
            x-on:keydown.enter.prevent="$wire.selectCurrent()"
            x-on:keydown.escape.prevent="$wire.closeSearch()"
        >
            {{-- Search input --}}
            <div class="relative">
                <core:icon name="magnifying-glass" class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" />
                <input
                    wire:model.live.debounce.300ms="query"
                    type="text"
                    placeholder="{{ __('hub::hub.search.placeholder') }}"
                    class="w-full border-0 bg-transparent py-4 pl-12 pr-4 text-zinc-900 placeholder-zinc-400 focus:outline-none focus:ring-0 dark:text-white"
                    autofocus
                />
                @if($query)
                    <button
                        wire:click="$set('query', '')"
                        type="button"
                        class="absolute right-4 top-1/2 -translate-y-1/2 rounded p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                    >
                        <core:icon name="x-mark" class="h-4 w-4" />
                    </button>
                @endif
            </div>

            {{-- Results --}}
            @if(strlen($query) >= 2)
                <div class="max-h-96 overflow-y-auto border-t border-zinc-200 dark:border-zinc-700">
                    @php $currentIndex = 0; @endphp

                    @forelse($this->results as $type => $items)
                        @if(count($items) > 0)
                            {{-- Category header --}}
                            <div class="sticky top-0 bg-zinc-50 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-800/50 dark:text-zinc-400">
                                {{ str($type)->title()->plural() }}
                            </div>

                            {{-- Results list --}}
                            @foreach($items as $item)
                                <button
                                    wire:click="navigateTo({{ json_encode($item) }})"
                                    type="button"
                                    class="flex w-full items-center gap-3 px-4 py-3 text-left transition {{ $selectedIndex === $currentIndex ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/50' }}"
                                >
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                        <core:icon name="{{ $item['icon'] }}" class="h-5 w-5" />
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate font-medium text-zinc-900 dark:text-white">
                                            {{ $item['title'] }}
                                        </div>
                                        <div class="truncate text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $item['subtitle'] }}
                                        </div>
                                    </div>
                                    @if($selectedIndex === $currentIndex)
                                        <core:icon name="arrow-right" class="h-4 w-4 text-blue-500" />
                                    @endif
                                </button>
                                @php $currentIndex++; @endphp
                            @endforeach
                        @endif
                    @empty
                        {{-- No results --}}
                        <div class="px-4 py-12 text-center">
                            <core:icon name="magnifying-glass" class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('hub::hub.search.no_results', ['query' => $query]) }}
                            </p>
                        </div>
                    @endforelse

                    @if(collect($this->results)->flatten(1)->isEmpty() && strlen($query) >= 2)
                        <div class="px-4 py-12 text-center">
                            <core:icon name="magnifying-glass" class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('hub::hub.search.no_results', ['query' => $query]) }}
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Footer with keyboard hints --}}
                <div class="flex items-center justify-between border-t border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-800/50">
                    <div class="flex items-center gap-4 text-xs text-zinc-400">
                        <span class="flex items-center gap-1">
                            <kbd class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono dark:bg-zinc-700">↑</kbd>
                            <kbd class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono dark:bg-zinc-700">↓</kbd>
                            {{ __('hub::hub.search.navigate') }}
                        </span>
                        <span class="flex items-center gap-1">
                            <kbd class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono dark:bg-zinc-700">↵</kbd>
                            {{ __('hub::hub.search.select') }}
                        </span>
                        <span class="flex items-center gap-1">
                            <kbd class="rounded bg-zinc-200 px-1.5 py-0.5 font-mono dark:bg-zinc-700">esc</kbd>
                            {{ __('hub::hub.search.close') }}
                        </span>
                    </div>
                </div>
            @else
                {{-- Initial state --}}
                <div class="border-t border-zinc-200 px-4 py-12 text-center dark:border-zinc-700">
                    <core:icon name="magnifying-glass" class="mx-auto h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('hub::hub.search.start_typing') }}
                    </p>
                </div>
            @endif
        </div>
    </core:modal>
</div>
