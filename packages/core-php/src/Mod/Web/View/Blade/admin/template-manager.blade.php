<div>
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Template Manager</h1>
                <p class="text-slate-400">Manage biolink templates for users</p>
            </div>
            <button wire:click="create" class="inline-flex items-center px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white font-medium rounded-lg transition-colors">
                <core:icon name="plus" class="w-4 h-4 mr-2" />
                Create Template
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-4 mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search templates..."
                    class="w-full px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white placeholder-slate-400 focus:ring-2 focus:ring-violet-500 focus:border-transparent"
                >
            </div>
            <select wire:model.live="categoryFilter" class="px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white">
                <option value="all">All Categories</option>
                <option value="business">Business</option>
                <option value="creator">Creator</option>
                <option value="personal">Personal</option>
                <option value="portfolio">Portfolio</option>
                <option value="music">Music</option>
            </select>
            <select wire:model.live="typeFilter" class="px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg text-white">
                <option value="all">All Types</option>
                <option value="system">System</option>
                <option value="custom">Custom</option>
            </select>
        </div>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($this->templates as $template)
            <div class="bg-slate-800/50 border border-slate-700 rounded-xl p-6 hover:border-violet-500/50 transition-colors">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-white">{{ $template->name }}</h3>
                        <flux:badge color="zinc" size="sm" class="mt-1">{{ ucfirst($template->category ?? 'general') }}</flux:badge>
                        @if($template->is_premium ?? false)
                            <flux:badge color="amber" size="sm" class="mt-1 ml-1">Premium</flux:badge>
                        @endif
                    </div>
                    <div class="flex items-center gap-1">
                        @if($template->is_active ?? true)
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                        @endif
                    </div>
                </div>

                <p class="text-sm text-slate-400 mb-4 line-clamp-2">
                    {{ $template->description ?: 'No description' }}
                </p>

                <div class="flex items-center justify-between pt-4 border-t border-slate-700">
                    <span class="text-xs text-slate-500">
                        {{ count($template->blocks ?? []) }} blocks
                    </span>
                    <div class="flex items-center gap-2">
                        <button wire:click="preview({{ $template->id }})" class="p-2 text-slate-400 hover:text-white transition-colors" title="Preview">
                            <core:icon name="eye" class="w-4 h-4" />
                        </button>
                        <button wire:click="edit({{ $template->id }})" class="p-2 text-slate-400 hover:text-violet-400 transition-colors" title="Edit">
                            <core:icon name="pen" class="w-4 h-4" />
                        </button>
                        <button wire:click="confirmDelete({{ $template->id }})" class="p-2 text-slate-400 hover:text-red-400 transition-colors" title="Delete">
                            <core:icon name="trash" class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="flex flex-col items-center justify-center py-12 px-4">
                    <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                        <flux:icon name="document-text" class="size-8 text-zinc-400" />
                    </div>
                    <flux:heading size="lg" class="text-center">No templates found</flux:heading>
                    <flux:subheading class="text-center mt-1 max-w-sm">
                        Create your first template to get started.
                    </flux:subheading>
                    <flux:button wire:click="create" icon="plus" variant="primary" class="mt-4">
                        Create Template
                    </flux:button>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($this->templates instanceof \Illuminate\Pagination\LengthAwarePaginator && $this->templates->hasPages())
        <div class="mt-6">
            {{ $this->templates->links() }}
        </div>
    @endif
</div>
