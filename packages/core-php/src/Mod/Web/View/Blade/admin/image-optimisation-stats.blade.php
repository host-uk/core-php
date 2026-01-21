<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Image Optimisation</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Platform-wide image compression statistics</p>
        </div>
    </div>

    {{-- Filters --}}
    <flux:accordion class="mb-6">
        <flux:accordion.item heading="Filters">
            <div class="flex items-center gap-4 flex-wrap">
                {{-- Workspace filter --}}
                <flux:field class="w-48">
                    <flux:label>Workspace</flux:label>
                    <flux:select wire:model.live="workspaceFilter">
                        <option value="">All workspaces</option>
                        @foreach($this->workspaces as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                {{-- Date range --}}
                <flux:field class="w-32">
                    <flux:label>Date range</flux:label>
                    <flux:select wire:model.live="dateRange">
                        <option value="7">7 days</option>
                        <option value="30">30 days</option>
                        <option value="90">90 days</option>
                    </flux:select>
                </flux:field>

                @if($workspaceFilter || $dateRange !== '30')
                    <flux:button wire:click="clearFilters" variant="ghost" size="sm" icon="x-mark">
                        Clear filters
                    </flux:button>
                @endif
            </div>
        </flux:accordion.item>
    </flux:accordion>

    {{-- Summary stats --}}
    <div wire:loading.class="opacity-50 pointer-events-none">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Optimised</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ number_format($this->overallStats['total_count'] ?? 0) }}
                    </p>
                </div>
                <div class="p-3 rounded-full bg-violet-100 dark:bg-violet-900/30">
                    <core:icon name="photo" class="size-6 text-violet-500" />
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Space Saved</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ $this->overallStats['space_saved_human'] ?? '0 B' }}
                    </p>
                </div>
                <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30">
                    <core:icon name="arrow-trending-down" class="size-6 text-green-500" />
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Avg Compression</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ number_format($this->overallStats['avg_percentage'] ?? 0, 1) }}%
                    </p>
                </div>
                <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <core:icon name="chart-bar" class="size-6 text-blue-500" />
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Active Workspaces</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-1">
                        {{ count($this->workspaceStats) }}
                    </p>
                </div>
                <div class="p-3 rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <core:icon name="building-office" class="size-6 text-amber-500" />
                </div>
            </div>
        </div>
    </div>

    {{-- Top workspaces --}}
    @if(count($this->workspaceStats) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Top Workspaces</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Workspace</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Images</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Space Saved</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Avg %</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->workspaceStats as $stat)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $stat['workspace_name'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 text-right">
                                    {{ number_format($stat['count']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 text-right">
                                    {{ $stat['total_saved_human'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 text-right">
                                    {{ $stat['avg_percentage'] }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Driver stats --}}
    @if(count($this->driverStats) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Driver Usage</h2>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-4">
                    @foreach($this->driverStats as $driver)
                        <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ ucfirst($driver['driver']) }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ number_format($driver['count']) }})</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Recent optimisations --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Optimisations</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Workspace</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">File</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Original</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Optimised</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Saved</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($this->recentOptimisations as $opt)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $opt->workspace?->name ?? 'Unknown' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                {{ $opt->original_filename ?? basename($opt->path ?? '-') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 text-right">
                                {{ $opt->original_size_human ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400 text-right">
                                {{ $opt->optimized_size_human ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 dark:text-green-400 text-right">
                                {{ number_format($opt->percentage_saved ?? 0, 1) }}%
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">
                                {{ $opt->created_at?->diffForHumans() ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No optimisations found for the selected period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->recentOptimisations->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $this->recentOptimisations->links() }}
            </div>
        @endif
    </div>
    </div>
    {{-- Loading indicator --}}
    <div wire:loading class="flex justify-center py-8">
        <flux:icon name="arrow-path" class="size-6 animate-spin text-violet-500" />
    </div>
</div>
