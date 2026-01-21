{{--
    Blocks preview with drag support.
    HLCRF ID system: each block gets an ID like H-1, C-3, F-2.
    For nested layouts, pass $prefix (e.g. 'H-' for header's children → H-C-1).
--}}
@php
    $prefix = $prefix ?? null;  // Parent prefix for nested layouts
@endphp

<div class="flex flex-col gap-3" data-preview-blocks>
    @forelse($blocks ?? [] as $block)
        @if($block->is_enabled)
            @php
                $hlcrfId = $block->getHlcrfId($prefix);
                $regionCode = $block->getRegionShortCode();
            @endphp
            <div
                data-block-id="{{ $block->id }}"
                data-hlcrf-id="{{ $hlcrfId }}"
                data-preview-block-id="{{ $block->id }}"
                draggable="true"
                x-on:dragstart="handleDragStart($event, {{ $block->id }}, 'preview')"
                x-on:dragover="handleDragOver($event, {{ $block->id }})"
                x-on:drop="handleDrop($event, {{ $block->id }}, 'preview')"
                x-on:dragend="handleDragEnd"
                x-on:click="$wire.editBlock({{ $block->id }})"
                class="relative group cursor-pointer"
                :class="{ 'opacity-50 scale-95': draggedBlock === {{ $block->id }} }"
                wire:key="preview-block-{{ $hlcrfId }}"
            >
                {{-- Hover overlay with controls --}}
                <div class="absolute inset-0 -m-1 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10"
                     :class="{ 'opacity-100': dragOverBlock === {{ $block->id }} }">
                    <div class="absolute inset-0 rounded-lg ring-2 ring-violet-500/50"></div>
                    <div class="absolute -right-1.5 -top-1.5 pointer-events-auto">
                        <div class="w-5 h-5 bg-violet-500 rounded-full flex items-center justify-center shadow-lg">
                            <i class="fa-solid fa-pen text-white text-[8px]"></i>
                        </div>
                    </div>
                    <div class="absolute -left-1.5 top-1/2 -translate-y-1/2 pointer-events-auto cursor-grab active:cursor-grabbing">
                        <div class="w-4 h-6 bg-gray-900/90 rounded flex items-center justify-center shadow-lg">
                            <i class="fa-solid fa-grip-vertical text-white text-[8px]"></i>
                        </div>
                    </div>
                </div>

                {{-- Block preview (region derived from block) --}}
                @include('webpage::admin.partials.preview-block', ['block' => $block, 'region' => $regionCode])
            </div>
        @endif
    @empty
        <div class="text-center py-16 text-gray-500">
            <i class="fa-solid fa-cubes text-3xl mb-3 opacity-50"></i>
            <p class="text-sm">Add blocks to see preview</p>
        </div>
    @endforelse
</div>
