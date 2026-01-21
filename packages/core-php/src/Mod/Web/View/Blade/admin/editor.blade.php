<div
    x-data="{
        draggedBlock: null,
        dragSourceRegion: null,
        dragOverBlock: null,
        dragOverRegion: null,
        previewScrollY: 0,

        handleDragStart(e, blockId, region = 'content') {
            this.draggedBlock = blockId;
            this.dragSourceRegion = region;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', blockId);
            e.dataTransfer.setData('source-region', region);
        },
        handleDragOver(e, blockId, region = null) {
            e.preventDefault();
            if (this.draggedBlock !== blockId) {
                this.dragOverBlock = blockId;
            }
            if (region) {
                this.dragOverRegion = region;
            }
        },
        handleRegionDragOver(e, region) {
            e.preventDefault();
            this.dragOverRegion = region;
            this.dragOverBlock = null;
        },
        handleDrop(e, blockId, targetRegion = 'content') {
            e.preventDefault();
            if (!this.draggedBlock) return;

            const sourceRegion = this.dragSourceRegion;
            const isCrossRegion = sourceRegion !== targetRegion;

            if (isCrossRegion) {
                // Moving to different region - use dedicated method
                $wire.call('moveBlockToRegion', this.draggedBlock, targetRegion, blockId);
            } else if (this.draggedBlock !== blockId) {
                // Same region reorder
                const container = document.querySelector(`[data-blocks-list][data-region='${targetRegion}']`);
                if (container) {
                    const blocks = [...container.querySelectorAll('[data-block-id]')];
                    const order = blocks.map(b => parseInt(b.dataset.blockId));
                    const fromIndex = order.indexOf(this.draggedBlock);
                    const toIndex = order.indexOf(blockId);
                    if (fromIndex !== -1 && toIndex !== -1) {
                        order.splice(fromIndex, 1);
                        order.splice(toIndex, 0, this.draggedBlock);
                        $wire.dispatch('blocks-reordered', { order, region: targetRegion });
                    }
                }
            }

            this.resetDragState();
        },
        handleRegionDrop(e, targetRegion) {
            e.preventDefault();
            if (!this.draggedBlock) return;

            const sourceRegion = this.dragSourceRegion;
            if (sourceRegion !== targetRegion) {
                // Move to end of target region
                $wire.call('moveBlockToRegion', this.draggedBlock, targetRegion, null);
            }

            this.resetDragState();
        },
        handleDragEnd() {
            this.resetDragState();
        },
        resetDragState() {
            this.draggedBlock = null;
            this.dragSourceRegion = null;
            this.dragOverBlock = null;
            this.dragOverRegion = null;
        },
        scrollPreviewToBlock(blockId) {
            const previewBlock = document.querySelector(`[data-preview-block-id='${blockId}']`);
            const previewContainer = document.querySelector('[data-preview-scroll]');
            if (previewBlock && previewContainer) {
                previewContainer.scrollTo({
                    top: previewBlock.offsetTop - 60,
                    behavior: 'smooth'
                });
            }
        }
    }"
    x-init="
        // Sync scroll between blocks list and preview
        $watch('previewScrollY', (value) => {
            const preview = document.querySelector('[data-preview-scroll]');
            if (preview) preview.scrollTop = value;
        });
    "
>
    {{-- Editor Toolbar --}}
    <div class="flex items-center justify-between gap-4 mb-6 p-2 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
        {{-- Left: Mode + URL --}}
        <div class="flex items-center gap-3">
            {{-- Mode Toggle --}}
            <div class="flex items-center gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Mode</span>
                <flux:button.group>
                    <flux:button
                        wire:click="$set('editorMode', 'visual')"
                        :variant="$editorMode === 'visual' ? 'primary' : 'ghost'"
                        size="sm"
                        tooltip="Visual editing - preview focused"
                    >
                        <core:icon name="eye" class="w-4 h-4"/>
                    </flux:button>
                    <flux:button
                        wire:click="$set('editorMode', 'structural')"
                        :variant="$editorMode === 'structural' ? 'primary' : 'ghost'"
                        size="sm"
                        tooltip="Structural editing - block list"
                    >
                        <core:icon name="list" class="w-4 h-4"/>
                    </flux:button>
                </flux:button.group>
            </div>

            {{-- Divider --}}
            <div class="h-6 w-px bg-gray-300 dark:bg-gray-600"></div>

            {{-- Public URL --}}
            <a href="{{ $this->publicUrl }}" target="_blank"
               class="hidden sm:flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-violet-500 dark:hover:text-violet-400 transition-colors">
                <span class="truncate max-w-[180px]">{{ $this->publicUrl }}</span>
                <core:icon name="arrow-up-right-from-square" class="text-xs"/>
            </a>
        </div>

        {{-- Centre: Viewport + Layout --}}
        <div class="flex items-center gap-4">
            {{-- Viewport Toggle --}}
            <div class="flex items-center gap-2">
                <span
                    class="hidden md:inline text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">View</span>
                <flux:button.group>
                    @foreach($this->viewports as $viewportKey => $viewport)
                        <flux:button
                            wire:click="selectViewport('{{ $viewportKey }}')"
                            :variant="$selectedViewport === $viewportKey ? 'primary' : 'ghost'"
                            size="sm"
                            tooltip="{{ $viewport['name'] }} ({{ $viewport['viewport']['width'] }}×{{ $viewport['viewport']['height'] }})"
                        >
                            <core:icon name="{{ $viewport['icon'] }}" class="w-4 h-4"/>
                        </flux:button>
                    @endforeach
                </flux:button.group>
            </div>

            {{-- Layout Preset Selector --}}
            <div class="hidden lg:flex items-center gap-2">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Layout</span>
                <div x-data="{ open: false }" class="relative">
                    <button
                        x-on:click="open = !open"
                        type="button"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 text-sm font-medium rounded-md bg-transparent hover:bg-zinc-800/5 dark:hover:bg-white/15 text-zinc-800 dark:text-white"
                    >
                        <span>{{ ucfirst($layoutPreset) }}</span>
                        <i class="fa-solid fa-chevron-down text-[10px] opacity-60"></i>
                    </button>
                    <div
                        x-show="open"
                        x-on:click.away="open = false"
                        x-transition
                        class="absolute top-full left-0 mt-1 py-1 bg-white dark:bg-zinc-700 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-600 min-w-[160px] z-50"
                    >
                        @foreach($this->layoutPresets as $preset)
                            <button
                                wire:click="selectPreset('{{ $preset['key'] }}')"
                                x-on:click="open = false"
                                class="w-full px-3 py-1.5 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-600 {{ $layoutPreset === $preset['key'] ? 'bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300' : 'text-zinc-700 dark:text-zinc-200' }}"
                            >
                                <span class="flex items-center justify-between">
                                    <span>{{ $preset['name'] }}</span>
                                    <span class="text-xs opacity-60 font-mono">{{ $preset[$selectedViewport] }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Actions --}}
        <div class="flex items-center gap-2">
            <flux:button wire:click="openBlockPicker" size="sm" variant="ghost">
                <core:icon name="plus" class="w-4 h-4"/>
                <span class="hidden sm:inline">Add Block</span>
            </flux:button>
            <flux:button wire:click="openThemeEditor" size="sm" variant="ghost" tooltip="Theme">
                <core:icon name="palette" class="w-4 h-4"/>
            </flux:button>
            <flux:button wire:click="openSettings" size="sm" variant="ghost" tooltip="Settings">
                <core:icon name="gear" class="w-4 h-4"/>
            </flux:button>
            <flux:button wire:click="save" size="sm" variant="primary">
                <core:icon name="check" class="w-4 h-4"/>
                Save
            </flux:button>
        </div>
    </div>

    {{-- Visual Mode: Preview-focused editing --}}
    @if($editorMode === 'visual')
        <div class="flex flex-col items-center">
            {{-- Device Preview Panel - centered, primary focus --}}
            <div class="flex justify-center">
                <div class="flex flex-col items-center w-full">
                    {{-- HLCRF Viewport Preview --}}
                    @php
                        $bg = $this->biolink->getBackground();
                        $bgImage = isset($bg['image']) ? (str_contains($bg['image'], '/') ? $bg['image'] : 'theme-backgrounds/' . $bg['image']) : null;
                        $bgStyle = match($bg['type'] ?? 'color') {
                            'gradient' => isset($bg['css']) ? "background: " . $bg['css'] : "background: linear-gradient(" . ($bg['gradient_direction'] ?? '180deg') . ", " . ($bg['gradient_start'] ?? '#667eea') . ", " . ($bg['gradient_end'] ?? '#764ba2') . ")",
                            'advanced' => isset($bg['css']) ? "background: " . $bg['css'] : "background: " . ($bg['color'] ?? '#f9fafb'),
                            'image' => $bgImage ? "background: url('" . Storage::url($bgImage) . "') center/cover" : "background: " . ($bg['color'] ?? '#f9fafb'),
                            default => "background: " . ($bg['color'] ?? '#f9fafb'),
                        };

                        $viewportConfig = $this->currentViewport;
                        $layoutType = $viewportConfig['layout'] ?? 'C';
                        $regions = $viewportConfig['regions'] ?? [];
                    @endphp

                    <div
                        wire:key="viewport-frame-{{ $selectedViewport }}-{{ $headerEnabled }}-{{ $leftEnabled }}-{{ $rightEnabled }}-{{ $footerEnabled }}">
                        <x-webpage::viewport-frame :breakpoint="$selectedViewport">
                            @if($selectedViewport === 'phone')
                                {{-- Phone: Simple C layout (full screen bio) --}}
                                <div class="w-full h-full relative" style="{{ $bgStyle }}">
                                    {{-- Status bar --}}
                                    <div
                                        class="absolute top-0 left-0 right-0 z-10 h-12 flex items-end justify-between px-8 pb-1"
                                        x-data="{ time: '' }"
                                        x-init="
                                        const update = () => time = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });
                                        update(); setInterval(update, 1000);
                                    "
                                    >
                                        <span class="text-[14px] font-semibold text-gray-900"
                                              style="font-family: -apple-system, sans-serif;" x-text="time"></span>
                                        <div class="flex items-center gap-1.5 text-gray-900">
                                            <svg class="w-4 h-3" viewBox="0 0 18 12" fill="currentColor">
                                                <path d="M1 4h2v8H1zM5 3h2v9H5zM9 1h2v11H9zM13 0h2v12h-2z"/>
                                            </svg>
                                            <svg class="w-6 h-3" viewBox="0 0 25 12" fill="currentColor">
                                                <rect x="0" y="1" width="21" height="10" rx="2.5" stroke="currentColor"
                                                      stroke-width="1" fill="none"/>
                                                <rect x="22" y="4" width="2" height="4" rx="0.5"/>
                                                <rect x="2" y="3" width="17" height="6" rx="1"/>
                                            </svg>
                                        </div>
                                    </div>

                                    {{-- Content --}}
                                    <div class="absolute inset-0 overflow-y-auto px-4 pt-14 pb-8"
                                         style="scrollbar-width: none;">
                                        @include('webpage::admin.partials.blocks-preview', ['blocks' => $this->biolink->blocks])
                                    </div>
                                </div>
                            @else
                                {{-- Tablet/Desktop: HLCRF layout --}}
                                @php
                                    $blocksByRegion = $this->blocksByRegion;
                                @endphp
                                <x-webpage::hlcrf-preview
                                    :layout="$layoutType"
                                    :regions="$regions"
                                    :enabledRegions="$this->enabledRegions"
                                    :bgStyle="$bgStyle"
                                >
                                    {{-- Header slot --}}
                                    <x-slot:header>
                                        <div class="w-full h-full flex items-center px-4">
                                            @include('webpage::admin.partials.blocks-preview', ['blocks' => $blocksByRegion['header']])
                                        </div>
                                    </x-slot:header>

                                    {{-- Left sidebar slot --}}
                                    <x-slot:left>
                                        <div class="w-full h-full overflow-y-auto p-3">
                                            @include('webpage::admin.partials.blocks-preview', ['blocks' => $blocksByRegion['left']])
                                        </div>
                                    </x-slot:left>

                                    {{-- Content slot (default) --}}
                                    <div class="p-4">
                                        @include('webpage::admin.partials.blocks-preview', ['blocks' => $blocksByRegion['content']])
                                    </div>

                                    {{-- Right sidebar slot --}}
                                    <x-slot:right>
                                        <div class="w-full h-full overflow-y-auto p-3">
                                            @include('webpage::admin.partials.blocks-preview', ['blocks' => $blocksByRegion['right']])
                                        </div>
                                    </x-slot:right>

                                    {{-- Footer slot --}}
                                    <x-slot:footer>
                                        <div class="w-full h-full flex items-center px-4">
                                            @include('webpage::admin.partials.blocks-preview', ['blocks' => $blocksByRegion['footer']])
                                        </div>
                                    </x-slot:footer>
                                </x-webpage::hlcrf-preview>
                            @endif
                        </x-webpage::viewport-frame>
                    </div>

                    {{-- Preview actions --}}
                    <div class="mt-4 flex items-center gap-4">
                        <a
                            href="{{ $this->publicUrl }}"
                            target="_blank"
                            class="text-sm text-violet-600 dark:text-violet-400 hover:text-violet-800 dark:hover:text-violet-300 flex items-center gap-1"
                        >
                            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                            Open live page
                        </a>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span class="text-xs text-gray-400">Drag blocks to reorder</span>
                    </div>
                </div>
            </div>

            {{-- Mobile preview modal - only shown on small screens when toggled --}}
            @if($showPreview)
                <div class="lg:hidden fixed inset-0 z-50 bg-gray-900/90 flex items-center justify-center p-4">
                    <div class="relative w-full flex flex-col items-center">
                        <button
                            wire:click="togglePreview"
                            class="absolute -top-12 right-4 text-white hover:text-gray-300"
                        >
                            <i class="fa-solid fa-times text-xl"></i>
                        </button>

                        {{-- CSS-only device frame with iframe for simpler mobile experience --}}
                        <x-webpage::viewport-frame breakpoint="phone" :maxHeight="550">
                            <iframe
                                src="{{ $this->previewUrl }}?preview=1&_t={{ time() }}"
                                class="w-full h-full bg-white dark:bg-gray-800"
                                title="BioLink Preview"
                            ></iframe>
                        </x-webpage::viewport-frame>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Structural Mode: Region-based block management --}}
    @if($editorMode === 'structural')
        <div>
            @php
                $regionConfig = [
                    'header' => ['name' => 'Header', 'icon' => 'arrow-up-to-line', 'code' => 'H'],
                    'left' => ['name' => 'Left Sidebar', 'icon' => 'panel-left', 'code' => 'L'],
                    'content' => ['name' => 'Content', 'icon' => 'layout-template', 'code' => 'C'],
                    'right' => ['name' => 'Right Sidebar', 'icon' => 'panel-right', 'code' => 'R'],
                    'footer' => ['name' => 'Footer', 'icon' => 'arrow-down-to-line', 'code' => 'F'],
                ];
                $blocksByRegion = $this->blocksByRegion;
                $layoutType = $this->layoutPresets[array_search($layoutPreset, array_column($this->layoutPresets, 'key'))][$selectedViewport] ?? 'C';
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                {{-- Region Panels --}}
                <div class="space-y-4">
                    {{-- URL setting --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Page URL</label>
                        <div class="flex rounded-md shadow-sm">
                        <span
                            class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm">
                            lt.hn/
                        </span>
                            <input
                                type="text"
                                wire:model.blur="url"
                                class="flex-1 block w-full rounded-none rounded-r-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                            >
                        </div>
                        @error('url') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Current Layout Indicator --}}
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <span
                                    class="text-sm font-medium text-gray-700 dark:text-gray-300">Layout for {{ ucfirst($selectedViewport) }}</span>
                                <span
                                    class="ml-2 px-2 py-0.5 bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 text-xs font-mono rounded">
                                {{ $this->getLayoutTypeForCurrentViewport() }}
                            </span>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ count($this->biolink->blocks ?? []) }} total blocks
                        </span>
                        </div>
                    </div>

                    {{-- Region Panels --}}
                    @foreach($regionConfig as $regionKey => $region)
                        @php
                            $isEnabled = $this->enabledRegions[$regionKey] ?? ($regionKey === 'content');
                            $regionBlocks = $blocksByRegion[$regionKey] ?? collect();
                            $blockCount = $regionBlocks->count();
                            $isInLayout = strpos($this->getLayoutTypeForCurrentViewport(), $region['code']) !== false;
                        @endphp

                        <div
                            x-data="{ open: {{ $regionKey === 'content' || $blockCount > 0 ? 'true' : 'false' }} }"
                            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden {{ !$isInLayout ? 'opacity-50' : '' }}"
                            wire:key="region-panel-{{ $regionKey }}"
                        >
                            {{-- Region Header --}}
                            <button
                                x-on:click="open = !open"
                                class="w-full px-4 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <core:icon name="{{ $region['icon'] }}"
                                               class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                                    <span
                                        class="font-medium text-gray-900 dark:text-gray-100">{{ $region['name'] }}</span>
                                    <span
                                        class="px-1.5 py-0.5 text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                    {{ $region['code'] }}
                                </span>
                                    @if($blockCount > 0)
                                        <span
                                            class="px-2 py-0.5 text-xs bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300 rounded-full">
                                        {{ $blockCount }}
                                    </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2">
                                    @if(!$isInLayout)
                                        <span class="text-xs text-gray-400 dark:text-gray-500">Not in layout</span>
                                    @endif
                                    <core:icon
                                        name="chevron-down"
                                        class="w-4 h-4 text-gray-400 transition-transform"
                                        x-bind:class="{ 'rotate-180': open }"
                                    />
                                </div>
                            </button>

                            {{-- Region Content --}}
                            <div x-show="open" x-collapse>
                                <div class="border-t border-gray-200 dark:border-gray-700">
                                    @if($regionBlocks->count() > 0)
                                        <div
                                            class="divide-y divide-gray-100 dark:divide-gray-700/50"
                                            data-blocks-list
                                            data-region="{{ $regionKey }}"
                                            x-on:dragover="handleRegionDragOver($event, '{{ $regionKey }}')"
                                            x-on:drop="handleRegionDrop($event, '{{ $regionKey }}')"
                                            :class="{ 'bg-violet-50/50 dark:bg-violet-900/10': dragOverRegion === '{{ $regionKey }}' && dragSourceRegion !== '{{ $regionKey }}' }"
                                        >
                                            @foreach($regionBlocks as $block)
                                                <div
                                                    data-block-id="{{ $block->id }}"
                                                    draggable="true"
                                                    x-on:dragstart="handleDragStart($event, {{ $block->id }}, '{{ $regionKey }}')"
                                                    x-on:dragover.stop="handleDragOver($event, {{ $block->id }}, '{{ $regionKey }}')"
                                                    x-on:drop.stop="handleDrop($event, {{ $block->id }}, '{{ $regionKey }}')"
                                                    x-on:dragend="handleDragEnd"
                                                    class="px-4 py-3 flex items-center gap-3 transition-colors {{ !$block->is_enabled ? 'opacity-50' : '' }}"
                                                    :class="{
                                                    'bg-violet-50 dark:bg-violet-900/20': dragOverBlock === {{ $block->id }},
                                                    'opacity-50': draggedBlock === {{ $block->id }}
                                                }"
                                                    wire:key="structural-block-{{ $block->id }}"
                                                >
                                                    {{-- Drag handle --}}
                                                    <div
                                                        class="cursor-grab active:cursor-grabbing text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                                        <i class="fa-solid fa-grip-vertical text-xs"></i>
                                                    </div>

                                                    {{-- Block icon --}}
                                                    <div
                                                        class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                                                        @php
                                                            $blockType = config("bio.block_types.{$block->type}", []);
                                                            $icon = $blockType['icon'] ?? 'fas fa-cube';
                                                        @endphp
                                                        <i class="{{ $icon }} text-gray-500 dark:text-gray-400 text-xs"></i>
                                                    </div>

                                                    {{-- Block info --}}
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-center gap-2">
                                                        <span
                                                            class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                                                            {{ $blockType['name'] ?? ucfirst($block->type) }}
                                                        </span>
                                                            {{-- Breakpoint visibility indicators --}}
                                                            @if($block->breakpoint_visibility !== null)
                                                                <div class="flex items-center gap-0.5">
                                                                    @php
                                                                        $bpIcons = ['phone' => 'mobile', 'tablet' => 'tablet', 'desktop' => 'desktop'];
                                                                    @endphp
                                                                    @foreach(['phone', 'tablet', 'desktop'] as $bp)
                                                                        @if($block->isVisibleAt($bp))
                                                                            <i class="fa-solid fa-{{ $bpIcons[$bp] }} text-[9px] text-green-500"
                                                                               title="Visible on {{ ucfirst($bp) }}"></i>
                                                                        @else
                                                                            <i class="fa-solid fa-{{ $bpIcons[$bp] }} text-[9px] text-gray-300 dark:text-gray-600"
                                                                               title="Hidden on {{ ucfirst($bp) }}"></i>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                            @if($block->type === 'link')
                                                                {{ $block->settings['name'] ?? $block->location_url ?? 'Link' }}
                                                            @elseif($block->type === 'heading')
                                                                {{ $block->settings['text'] ?? 'Heading' }}
                                                            @elseif($block->type === 'paragraph')
                                                                {{ Str::limit($block->settings['text'] ?? 'Text', 30) }}
                                                            @endif
                                                        </div>
                                                    </div>

                                                    {{-- Compact Actions --}}
                                                    <div class="flex items-center gap-0.5">
                                                        <button
                                                            wire:click="editBlock({{ $block->id }})"
                                                            class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400"
                                                            title="Edit"
                                                        >
                                                            <i class="fa-solid fa-pen text-xs"></i>
                                                        </button>
                                                        {{-- Breakpoint visibility dropdown --}}
                                                        <div x-data="{ open: false }" class="relative">
                                                            <button
                                                                x-on:click="open = !open"
                                                                class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400"
                                                                title="Breakpoint visibility"
                                                            >
                                                                <i class="fa-solid fa-display text-xs"></i>
                                                            </button>
                                                            <div
                                                                x-show="open"
                                                                x-on:click.away="open = false"
                                                                x-transition
                                                                class="absolute right-0 top-full mt-1 z-20 w-36 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1"
                                                            >
                                                                <div
                                                                    class="px-2 py-1 text-[10px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                                                    Show on
                                                                </div>
                                                                @foreach(['phone' => 'Mobile', 'tablet' => 'Tablet', 'desktop' => 'Desktop'] as $bp => $label)
                                                                    <button
                                                                        wire:click="toggleBreakpointVisibility({{ $block->id }}, '{{ $bp }}')"
                                                                        class="w-full px-2 py-1.5 text-left text-xs flex items-center gap-2 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                                                    >
                                                                        @if($block->breakpoint_visibility === null || $block->isVisibleAt($bp))
                                                                            <i class="fa-solid fa-check text-green-500 w-3"></i>
                                                                        @else
                                                                            <i class="fa-solid fa-times text-gray-300 dark:text-gray-600 w-3"></i>
                                                                        @endif
                                                                        <i class="fa-solid fa-{{ $bp === 'phone' ? 'mobile' : ($bp === 'tablet' ? 'tablet' : 'desktop') }} text-gray-400 w-3"></i>
                                                                        <span
                                                                            class="text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                                                    </button>
                                                                @endforeach
                                                                @if($block->breakpoint_visibility !== null)
                                                                    <div
                                                                        class="border-t border-gray-100 dark:border-gray-700 mt-1 pt-1">
                                                                        <button
                                                                            wire:click="resetBreakpointVisibility({{ $block->id }})"
                                                                            class="w-full px-2 py-1.5 text-left text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                                                        >
                                                                            <i class="fa-solid fa-rotate-left w-3 mr-2"></i>
                                                                            Show on all
                                                                        </button>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <button
                                                            wire:click="toggleBlock({{ $block->id }})"
                                                            class="p-1.5 {{ $block->is_enabled ? 'text-green-500 hover:text-red-500' : 'text-gray-400 hover:text-green-500' }}"
                                                            title="{{ $block->is_enabled ? 'Disable' : 'Enable' }}"
                                                        >
                                                            <i class="fa-solid {{ $block->is_enabled ? 'fa-eye' : 'fa-eye-slash' }} text-xs"></i>
                                                        </button>
                                                        <button
                                                            wire:click="deleteBlock({{ $block->id }})"
                                                            wire:confirm="Delete this block?"
                                                            class="p-1.5 text-gray-400 hover:text-red-600"
                                                            title="Delete"
                                                        >
                                                            <i class="fa-solid fa-trash text-xs"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Add block to region / Drop zone --}}
                                    @if($isInLayout || $regionKey === 'content')
                                        <div
                                            class="px-4 py-3 {{ $regionBlocks->count() > 0 ? 'border-t border-gray-100 dark:border-gray-700/50' : '' }} transition-colors"
                                            x-on:dragover="handleRegionDragOver($event, '{{ $regionKey }}')"
                                            x-on:drop="handleRegionDrop($event, '{{ $regionKey }}')"
                                            :class="{ 'bg-violet-50 dark:bg-violet-900/20': dragOverRegion === '{{ $regionKey }}' && dragSourceRegion !== '{{ $regionKey }}' }"
                                        >
                                            <button
                                                wire:click="openBlockPickerForRegion('{{ $regionKey }}')"
                                                class="w-full py-2 px-3 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg text-sm text-gray-500 dark:text-gray-400 hover:border-violet-500 hover:text-violet-500 dark:hover:border-violet-500 dark:hover:text-violet-400 transition-colors flex items-center justify-center gap-2"
                                                :class="{ 'border-violet-500 text-violet-500': dragOverRegion === '{{ $regionKey }}' && dragSourceRegion !== '{{ $regionKey }}' }"
                                            >
                                                <i class="fa-solid fa-plus text-xs"
                                                   :class="{ 'hidden': draggedBlock && dragSourceRegion !== '{{ $regionKey }}' }"></i>
                                                <i class="fa-solid fa-arrow-down text-xs"
                                                   :class="{ 'hidden': !draggedBlock || dragSourceRegion === '{{ $regionKey }}' }"
                                                   x-cloak></i>
                                                <span x-show="!draggedBlock || dragSourceRegion === '{{ $regionKey }}'">Add to {{ strtolower($region['name']) }}</span>
                                                <span x-show="draggedBlock && dragSourceRegion !== '{{ $regionKey }}'"
                                                      x-cloak>Drop here</span>
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Compact Preview Panel - Browser frame style --}}
                <div class="hidden lg:flex sticky top-4 justify-center">
                    <div class="flex flex-col items-center">
                        @php
                            $bg = $this->biolink->getBackground();
                            $bgImage = isset($bg['image']) ? (str_contains($bg['image'], '/') ? $bg['image'] : 'theme-backgrounds/' . $bg['image']) : null;
                            $bgStyle = match($bg['type'] ?? 'color') {
                                'gradient' => isset($bg['css']) ? "background: " . $bg['css'] : "background: linear-gradient(" . ($bg['gradient_direction'] ?? '180deg') . ", " . ($bg['gradient_start'] ?? '#667eea') . ", " . ($bg['gradient_end'] ?? '#764ba2') . ")",
                                'advanced' => isset($bg['css']) ? "background: " . $bg['css'] : "background: " . ($bg['color'] ?? '#f9fafb'),
                                'image' => $bgImage ? "background: url('" . Storage::url($bgImage) . "') center/cover" : "background: " . ($bg['color'] ?? '#f9fafb'),
                                default => "background: " . ($bg['color'] ?? '#f9fafb'),
                            };
                        @endphp

                        {{-- Simple browser frame --}}
                        <div
                            class="w-[280px] rounded-lg overflow-hidden shadow-lg border border-gray-200 dark:border-gray-700">
                            {{-- Browser chrome --}}
                            <div
                                class="bg-gray-100 dark:bg-gray-800 px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-2">
                                    <div class="flex gap-1.5">
                                        <span class="w-2.5 h-2.5 rounded-full bg-red-400"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-400"></span>
                                        <span class="w-2.5 h-2.5 rounded-full bg-green-400"></span>
                                    </div>
                                    <div
                                        class="flex-1 bg-white dark:bg-gray-900 rounded px-2 py-0.5 text-[10px] text-gray-500 dark:text-gray-400 truncate">
                                        {{ $this->publicUrl }}
                                    </div>
                                </div>
                            </div>

                            {{-- Preview content --}}
                            <div class="h-[400px] overflow-y-auto" style="{{ $bgStyle }}; scrollbar-width: none;">
                                <div class="p-3 flex flex-col gap-2">
                                    @forelse($this->biolink->blocks ?? [] as $block)
                                        @if($block->is_enabled)
                                            <div wire:key="structural-preview-{{ $block->id }}"
                                                 class="transform scale-90 origin-top">
                                                @include('webpage::admin.partials.preview-block', ['block' => $block, 'region' => 'C'])
                                            </div>
                                        @endif
                                    @empty
                                        <div class="text-center py-12 text-gray-500">
                                            <i class="fa-solid fa-cubes text-2xl mb-2 opacity-50"></i>
                                            <p class="text-xs">No blocks</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <a href="{{ $this->publicUrl }}" target="_blank"
                           class="mt-3 text-xs text-gray-500 dark:text-gray-400 hover:text-violet-500 flex items-center gap-1">
                            Open live page <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Block Picker Modal --}}
    @if($showBlockPicker)
        @php
            $targetShortCode = \Core\Mod\Web\Models\Block::REGION_SHORT_CODES[$targetRegion] ?? 'C';
            $regionNames = ['header' => 'Header', 'left' => 'Left Sidebar', 'content' => 'Content', 'right' => 'Right Sidebar', 'footer' => 'Footer'];
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity z-40"
                     wire:click="closeBlockPicker"></div>

                <div
                    class="relative z-50 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-2xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Add Block</h3>
                                @if($targetRegion !== 'content')
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                        Adding to <span
                                            class="font-medium text-violet-600 dark:text-violet-400">{{ $regionNames[$targetRegion] ?? ucfirst($targetRegion) }}</span>
                                        <span class="text-xs font-mono ml-1">({{ $targetShortCode }})</span>
                                    </p>
                                @endif
                            </div>
                            <flux:button wire:click="closeBlockPicker" variant="ghost" size="sm">
                                <i class="fa-solid fa-times"></i>
                            </flux:button>
                        </div>

                        {{-- Quick search --}}
                        <div class="mb-4">
                            <flux:input
                                wire:model.live="blockSearch"
                                placeholder="Search blocks..."
                                icon="magnifying-glass"
                                clearable
                            />
                        </div>

                        <div wire:transition class="space-y-6 max-h-[60vh] overflow-y-auto">
                            @foreach($this->blockTypesByCategory as $category => $types)
                                @php
                                    $categoryConfig = $this->categories[$category] ?? ['name' => ucfirst($category), 'icon' => 'fa-folder'];
                                    // Filter to blocks allowed in target region
                                    $availableTypes = collect($types)->filter(function ($config, $key) use ($targetShortCode, $blockSearch) {
                                        $allowed = $config['allowed_regions'] ?? null;
                                        $regionAllowed = $allowed === null ? $targetShortCode === 'C' : in_array($targetShortCode, $allowed);

                                        // Filter by search term
                                        if ($blockSearch) {
                                            $name = $config['name'] ?? str_replace('_', ' ', $key);
                                            $searchMatch = str_contains(strtolower($name), strtolower($blockSearch));
                                            return $regionAllowed && $searchMatch;
                                        }

                                        return $regionAllowed;
                                    });
                                @endphp
                                @if($availableTypes->count() > 0)
                                    <div wire:transition wire:key="category-{{ $category }}">
                                        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
                                            <i class="fa-solid {{ $categoryConfig['icon'] }}"></i>
                                            {{ $categoryConfig['name'] }}
                                        </h4>
                                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                            @foreach($availableTypes as $typeKey => $typeConfig)
                                                @php
                                                    $isLocked = $typeConfig['locked'] ?? false;
                                                @endphp
                                                <button
                                                    wire:transition
                                                    wire:key="block-{{ $typeKey }}"
                                                    wire:click="addBlock('{{ $typeKey }}')"
                                                    @if($isLocked) disabled @endif
                                                    class="p-3 border border-gray-200 dark:border-gray-700 rounded-lg text-left transition-colors {{ $isLocked ? 'opacity-50 cursor-not-allowed' : 'hover:border-violet-500 dark:hover:border-violet-500 hover:bg-violet-50 dark:hover:bg-violet-900/20' }}"
                                                >
                                                    <div class="flex items-center gap-3">
                                                        <div
                                                            class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center shrink-0">
                                                            <i class="{{ $typeConfig['icon'] ?? 'fas fa-cube' }} text-gray-500 dark:text-gray-400"></i>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <span
                                                                class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate block">
                                                                {{ $typeConfig['name'] ?? ucfirst(str_replace('_', ' ', $typeKey)) }}
                                                            </span>
                                                            @if($isLocked && isset($typeConfig['tier_label']))
                                                                <span
                                                                    class="text-xs text-amber-600 dark:text-amber-400">
                                                                    <i class="fa-solid fa-lock mr-1"></i>{{ $typeConfig['tier_label'] }}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Block Editor Modal --}}
    @if($showBlockEditor)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity z-40"
                     wire:click="closeBlockEditor"></div>

                <div
                    class="relative z-50 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Edit {{ config("bio.block_types.{$editingBlockType}.name", ucfirst($editingBlockType)) }}
                            </h3>
                            <button wire:click="closeBlockEditor"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            {{-- Block-specific settings --}}
                            @include('webpage::admin.partials.block-settings')
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            wire:click="saveBlock"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-violet-600 text-base font-medium text-white hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Save Block
                        </button>
                        <button
                            wire:click="closeBlockEditor"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Settings Panel (Slide-over) --}}
    @if($showSettings)
        <div class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog"
             aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity z-40"
                     wire:click="closeSettings"></div>

                <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex z-50">
                    <div class="w-screen max-w-md">
                        <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                            <div class="px-4 py-6 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-start justify-between">
                                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Page Settings</h2>
                                    <button wire:click="closeSettings"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                                {{-- Settings content --}}
                                <div class="space-y-6">
                                    {{-- Status --}}
                                    <div>
                                        <label class="flex items-center justify-between cursor-pointer">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Page Enabled</span>
                                            <input
                                                type="checkbox"
                                                wire:model="isEnabled"
                                                class="rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                            >
                                        </label>
                                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                            When disabled, visitors will see a 404 page.
                                        </p>
                                    </div>

                                    {{-- SEO Settings --}}
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">SEO</h3>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Page
                                                    Title</label>
                                                <input
                                                    type="text"
                                                    wire:model="settings.seo.title"
                                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                                    placeholder="My Page"
                                                >
                                            </div>
                                            <div>
                                                <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                                <textarea
                                                    wire:model="settings.seo.description"
                                                    rows="3"
                                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                                    placeholder="A short description..."
                                                ></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Background Settings --}}
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                            Background</h3>
                                        <div class="space-y-3">
                                            <div>
                                                <label
                                                    class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                                <select
                                                    wire:model.live="settings.background.type"
                                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                                >
                                                    <option value="color">Solid Color</option>
                                                    <option value="gradient">Gradient</option>
                                                    <option value="image">Image</option>
                                                </select>
                                            </div>
                                            @if(($settings['background']['type'] ?? 'color') === 'color')
                                                <div>
                                                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Color</label>
                                                    <input
                                                        type="color"
                                                        wire:model="settings.background.color"
                                                        class="w-full h-10 rounded-md border-gray-300 dark:border-gray-600"
                                                    >
                                                </div>
                                            @elseif(($settings['background']['type'] ?? 'color') === 'gradient')
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label
                                                            class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Start</label>
                                                        <input type="color" wire:model="settings.background.start"
                                                               class="w-full h-10 rounded-md border-gray-300 dark:border-gray-600">
                                                    </div>
                                                    <div>
                                                        <label
                                                            class="block text-sm text-gray-700 dark:text-gray-300 mb-1">End</label>
                                                        <input type="color" wire:model="settings.background.end"
                                                               class="w-full h-10 rounded-md border-gray-300 dark:border-gray-600">
                                                    </div>
                                                </div>
                                            @elseif(($settings['background']['type'] ?? 'color') === 'image')
                                                <div>
                                                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Image
                                                        URL</label>
                                                    <input
                                                        type="url"
                                                        wire:model="settings.background.url"
                                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                                        placeholder="https://..."
                                                    >
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Background Effects --}}
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">
                                            <i class="fa-solid fa-wand-magic-sparkles mr-1.5 text-violet-500"></i>
                                            Effects
                                        </h3>
                                        <div class="space-y-3">
                                            {{-- Effect Selector --}}
                                            <div>
                                                <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">Background
                                                    Effect</label>
                                                <select
                                                    wire:model.live="selectedEffect"
                                                    wire:change="selectBackgroundEffect($event.target.value)"
                                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                                >
                                                    <option value="">None</option>
                                                    @foreach($this->effectCategories as $catKey => $category)
                                                        <optgroup label="{{ $category['name'] }}">
                                                            @foreach($category['effects'] as $effectSlug)
                                                                @if(isset($this->availableBackgroundEffects[$effectSlug]))
                                                                    <option value="{{ $effectSlug }}">
                                                                        {{ $this->availableBackgroundEffects[$effectSlug]['name'] }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>

                                            {{-- Effect Parameters --}}
                                            @if($selectedEffect && count($this->currentEffectParameters) > 0)
                                                <div
                                                    class="pt-2 space-y-3 border-t border-gray-200 dark:border-gray-700">
                                                    @foreach($this->currentEffectParameters as $paramKey => $param)
                                                        <div>
                                                            <div class="flex items-center justify-between mb-1">
                                                                <label
                                                                    class="block text-sm text-gray-700 dark:text-gray-300">
                                                                    {{ $param['label'] }}
                                                                </label>
                                                                <span
                                                                    class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                                                    {{ $effectConfig[$paramKey] ?? $param['min'] ?? 0 }}
                                                                </span>
                                                            </div>
                                                            @if($param['type'] === 'range')
                                                                <input
                                                                    type="range"
                                                                    wire:model.live.debounce.300ms="effectConfig.{{ $paramKey }}"
                                                                    wire:change="updateEffectConfig('{{ $paramKey }}', $event.target.value)"
                                                                    min="{{ $param['min'] ?? 0 }}"
                                                                    max="{{ $param['max'] ?? 100 }}"
                                                                    step="{{ $param['step'] ?? 1 }}"
                                                                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-violet-500"
                                                                >
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>

                                                {{-- Clear Effect Button --}}
                                                <button
                                                    wire:click="clearBackgroundEffect"
                                                    class="w-full py-2 px-3 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"
                                                >
                                                    <i class="fa-solid fa-times mr-1.5"></i>
                                                    Remove Effect
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Tracking Pixels --}}
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Tracking
                                                Pixels</h3>
                                            <a
                                                href="{{ route('hub.bio.pixels') }}"
                                                wire:navigate
                                                class="text-xs text-violet-600 dark:text-violet-400 hover:underline"
                                            >
                                                Manage pixels
                                            </a>
                                        </div>
                                        @if($this->availablePixels->count() > 0)
                                            <div class="space-y-2">
                                                @foreach($this->availablePixels as $pixel)
                                                    <label
                                                        class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                        <input
                                                            type="checkbox"
                                                            wire:click="togglePixel({{ $pixel->id }})"
                                                            @checked(in_array($pixel->id, $selectedPixelIds))
                                                            class="rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                                        >
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $pixel->name }}</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $pixel->type_label }}</p>
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                            <button
                                                wire:click="savePixels"
                                                class="mt-3 w-full btn border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-violet-500"
                                            >
                                                <i class="fa-solid fa-save mr-2"></i> Save pixels
                                            </button>
                                        @else
                                            <div
                                                class="text-center py-4 border border-dashed border-gray-300 dark:border-gray-600 rounded-lg">
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">No tracking
                                                    pixels configured.</p>
                                                <a
                                                    href="{{ route('hub.bio.pixels') }}"
                                                    wire:navigate
                                                    class="text-sm text-violet-600 dark:text-violet-400 hover:underline"
                                                >
                                                    <i class="fa-solid fa-plus mr-1"></i> Add your first pixel
                                                </a>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Quick Actions --}}
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Quick
                                            Actions</h3>
                                        <div class="grid grid-cols-2 gap-2">
                                            <a
                                                href="{{ route('hub.bio.submissions', $biolinkId) }}"
                                                wire:navigate
                                                class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                            >
                                                <i class="fa-solid fa-envelope text-violet-500"></i>
                                                <span
                                                    class="text-sm text-gray-700 dark:text-gray-300">Submissions</span>
                                            </a>
                                            <a
                                                href="{{ route('hub.bio.notifications', $biolinkId) }}"
                                                wire:navigate
                                                class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                            >
                                                <i class="fa-solid fa-bell text-violet-500"></i>
                                                <span
                                                    class="text-sm text-gray-700 dark:text-gray-300">Notifications</span>
                                            </a>
                                            <a
                                                href="{{ route('hub.bio.analytics', $biolinkId) }}"
                                                wire:navigate
                                                class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                            >
                                                <i class="fa-solid fa-chart-line text-violet-500"></i>
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Analytics</span>
                                            </a>
                                            <a
                                                href="{{ route('hub.bio.themes') }}"
                                                wire:navigate
                                                class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                            >
                                                <i class="fa-solid fa-palette text-violet-500"></i>
                                                <span
                                                    class="text-sm text-gray-700 dark:text-gray-300">Theme Gallery</span>
                                            </a>
                                        </div>
                                    </div>

                                    {{-- Password Protection --}}
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Password
                                            Protection</h3>
                                        <div class="space-y-3">
                                            <label class="flex items-center justify-between cursor-pointer">
                                                <span class="text-sm text-gray-700 dark:text-gray-300">Require password to view</span>
                                                <input
                                                    type="checkbox"
                                                    wire:model.live="settings.password_protected"
                                                    class="rounded border-gray-300 text-violet-600 focus:ring-violet-500"
                                                >
                                            </label>

                                            @if($settings['password_protected'] ?? false)
                                                <div>
                                                    <label class="block text-sm text-gray-700 dark:text-gray-300 mb-1">
                                                        {{ ($settings['password'] ?? false) ? 'Change password' : 'Set password' }}
                                                    </label>
                                                    <input
                                                        type="password"
                                                        wire:model="newPassword"
                                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                                        placeholder="{{ ($settings['password'] ?? false) ? 'Leave blank to keep current' : 'Enter password' }}"
                                                    >
                                                    @if($settings['password'] ?? false)
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            <i class="fa-solid fa-lock mr-1"></i>
                                                            Password is currently set. Enter a new one to change it.
                                                        </p>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-4 sm:px-6">
                                <button
                                    wire:click="save"
                                    class="w-full btn bg-violet-500 hover:bg-violet-600 text-white"
                                >
                                    Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Theme Editor Panel (Slide-over) --}}
    @if($showThemeEditor)
        <div class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="theme-editor-title" role="dialog"
             aria-modal="true">
            <div class="absolute inset-0 overflow-hidden">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity z-40"
                     wire:click="closeThemeEditor"></div>

                <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex z-50">
                    <div class="w-screen max-w-md">
                        <div class="h-full flex flex-col bg-white dark:bg-gray-800 shadow-xl">
                            <div class="px-4 py-6 sm:px-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-start justify-between">
                                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                        <i class="fa-solid fa-palette mr-2 text-violet-500"></i>
                                        Page Theme
                                    </h2>
                                    <button wire:click="closeThemeEditor"
                                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
                                {{-- Current Theme Display --}}
                                @if($this->currentTheme)
                                    <div
                                        class="mb-6 p-4 bg-violet-50 dark:bg-violet-900/20 rounded-lg border border-violet-200 dark:border-violet-800">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <span
                                                    class="text-sm text-gray-600 dark:text-gray-400">Current theme:</span>
                                                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $this->currentTheme->name }}</p>
                                            </div>
                                            <button
                                                wire:click="removeTheme"
                                                class="text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                <i class="fa-solid fa-times mr-1"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                @endif

                                {{-- Theme Gallery --}}
                                <div class="space-y-4">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">Choose a Theme</h3>

                                    <div class="grid grid-cols-2 gap-3">
                                        @foreach($this->availableThemes as $theme)
                                            @php
                                                $bg = $theme->getBackground();
                                                $btn = $theme->getButton();
                                                $bgImage = isset($bg['image']) ? (str_contains($bg['image'], '/') ? $bg['image'] : 'theme-backgrounds/' . $bg['image']) : null;
                                                $bgStyle = match($bg['type'] ?? 'color') {
                                                    'gradient' => isset($bg['css']) ? "background: " . $bg['css'] : "background: linear-gradient(135deg, " . ($bg['gradient_start'] ?? '#fff') . ", " . ($bg['gradient_end'] ?? '#000') . ")",
                                                    'advanced' => isset($bg['css']) ? "background: " . $bg['css'] : "background: " . ($bg['color'] ?? '#ffffff'),
                                                    'image' => $bgImage ? "background: url('" . Storage::url($bgImage) . "') center/cover" : "background: " . ($bg['color'] ?? '#ffffff'),
                                                    default => "background: " . ($bg['color'] ?? '#ffffff'),
                                                };
                                                $isLocked = $theme->is_locked ?? false;
                                                $isSelected = $selectedThemeId === $theme->id;
                                            @endphp
                                            <button
                                                wire:click="applyTheme({{ $theme->id }})"
                                                class="relative rounded-lg overflow-hidden border-2 transition-all hover:shadow-md {{ $isSelected ? 'border-violet-500 ring-2 ring-violet-500/20' : 'border-gray-200 dark:border-gray-700' }} {{ $isLocked ? 'opacity-75' : '' }}"
                                                wire:key="theme-select-{{ $theme->id }}"
                                            >
                                                {{-- Theme Preview --}}
                                                <div
                                                    class="aspect-[4/3] p-2 flex flex-col items-center justify-center"
                                                    style="{{ $bgStyle }}"
                                                >
                                                    {{-- Avatar placeholder --}}
                                                    <div class="w-6 h-6 rounded-full bg-white/30 mb-1.5"></div>

                                                    {{-- Button previews --}}
                                                    <div class="w-full space-y-1 px-2">
                                                        @for($i = 0; $i < 2; $i++)
                                                            <div
                                                                class="h-4 w-full"
                                                                style="background: {{ $btn['background_color'] ?? '#000' }}; border-radius: {{ $btn['border_radius'] ?? '4px' }};"
                                                            ></div>
                                                        @endfor
                                                    </div>
                                                </div>

                                                {{-- Theme Name --}}
                                                <div
                                                    class="px-2 py-1.5 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
                                                    <div class="flex items-center justify-between">
                                                        <span
                                                            class="text-xs font-medium text-gray-900 dark:text-gray-100 truncate">{{ $theme->name }}</span>
                                                        @if($theme->is_premium)
                                                            <span
                                                                class="text-[10px] text-amber-600 dark:text-amber-400">
                                                                <i class="fa-solid fa-crown"></i>
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>

                                                {{-- Locked Overlay --}}
                                                @if($isLocked)
                                                    <div
                                                        class="absolute inset-0 bg-gray-900/50 flex items-center justify-center">
                                                        <div class="text-white text-center">
                                                            <i class="fa-solid fa-lock text-lg"></i>
                                                            <p class="text-[10px] mt-0.5">Pro</p>
                                                        </div>
                                                    </div>
                                                @endif

                                                {{-- Selected Check --}}
                                                @if($isSelected)
                                                    <div
                                                        class="absolute top-1.5 right-1.5 w-5 h-5 bg-violet-500 rounded-full flex items-center justify-center">
                                                        <i class="fa-solid fa-check text-white text-[10px]"></i>
                                                    </div>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Info about customisation via settings --}}
                                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        <i class="fa-solid fa-info-circle mr-1 text-violet-500"></i>
                                        You can also customise background colours in the
                                        <button wire:click="closeThemeEditor(); openSettings();"
                                                class="text-violet-600 dark:text-violet-400 hover:underline">page
                                            settings
                                        </button>
                                        .
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
