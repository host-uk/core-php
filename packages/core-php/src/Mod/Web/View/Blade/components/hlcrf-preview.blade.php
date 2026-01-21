@props([
    'layout' => 'C',
    'regions' => [],
    'enabledRegions' => ['content' => true],
    'bgStyle' => 'background: #f9fafb',
])

@php
    // Determine which regions to show based on layout type
    $showHeader = in_array($layout, ['HCF', 'HLCRF', 'HLCF', 'HCRF']);
    $showLeft = in_array($layout, ['HLCRF', 'HLCF']);
    $showRight = in_array($layout, ['HLCRF', 'HCRF']);
    $showFooter = in_array($layout, ['HCF', 'HLCRF', 'HLCF', 'HCRF']);

    // Get region dimensions from config
    $headerHeight = $regions['header']['height'] ?? 64;
    $footerHeight = $regions['footer']['height'] ?? 80;
    $leftWidth = $regions['left']['width'] ?? 280;
    $rightWidth = $regions['right']['width'] ?? 320;
    $contentMaxWidth = $regions['content']['max_width'] ?? 680;

    // Check which regions are enabled
    $headerEnabled = $enabledRegions['header'] ?? false;
    $leftEnabled = $enabledRegions['left'] ?? false;
    $rightEnabled = $enabledRegions['right'] ?? false;
    $footerEnabled = $enabledRegions['footer'] ?? false;
@endphp

<div
    {{ $attributes->merge(['class' => 'hlcrf-preview']) }}
    style="
        --header-height: {{ $headerHeight }}px;
        --footer-height: {{ $footerHeight }}px;
        --left-width: {{ $leftWidth }}px;
        --right-width: {{ $rightWidth }}px;
        --content-max-width: {{ $contentMaxWidth }}px;
        {{ $bgStyle }};
    "
>
    {{-- Header Region --}}
    @if($showHeader)
        <div class="hlcrf-header {{ $headerEnabled ? 'hlcrf-region-enabled' : 'hlcrf-region-empty' }}">
            @if($headerEnabled)
                {{ $header ?? '' }}
            @else
                <button
                    type="button"
                    wire:click="enableRegion('header')"
                    class="hlcrf-add-region"
                >
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Header</span>
                </button>
            @endif
        </div>
    @endif

    {{-- Main Body (Left + Content + Right) --}}
    <div class="hlcrf-body">
        {{-- Left Sidebar --}}
        @if($showLeft)
            <div class="hlcrf-left {{ $leftEnabled ? 'hlcrf-region-enabled' : 'hlcrf-region-empty' }}">
                @if($leftEnabled)
                    {{ $left ?? '' }}
                @else
                    <button
                        type="button"
                        wire:click="enableRegion('left')"
                        class="hlcrf-add-region hlcrf-add-region-vertical"
                    >
                        <i class="fa-solid fa-plus"></i>
                        <span>Add Left</span>
                    </button>
                @endif
            </div>
        @endif

        {{-- Content Area (always shown, bio blocks go here) --}}
        <div class="hlcrf-content">
            <div class="hlcrf-content-inner" style="{{ $bgStyle }}">
                {{ $slot }}
            </div>
        </div>

        {{-- Right Sidebar --}}
        @if($showRight)
            <div class="hlcrf-right {{ $rightEnabled ? 'hlcrf-region-enabled' : 'hlcrf-region-empty' }}">
                @if($rightEnabled)
                    {{ $right ?? '' }}
                @else
                    <button
                        type="button"
                        wire:click="enableRegion('right')"
                        class="hlcrf-add-region hlcrf-add-region-vertical"
                    >
                        <i class="fa-solid fa-plus"></i>
                        <span>Add Right</span>
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Footer Region --}}
    @if($showFooter)
        <div class="hlcrf-footer {{ $footerEnabled ? 'hlcrf-region-enabled' : 'hlcrf-region-empty' }}">
            @if($footerEnabled)
                {{ $footer ?? '' }}
            @else
                <button
                    type="button"
                    wire:click="enableRegion('footer')"
                    class="hlcrf-add-region"
                >
                    <i class="fa-solid fa-plus"></i>
                    <span>Add Footer</span>
                </button>
            @endif
        </div>
    @endif
</div>

<style>
    .hlcrf-preview {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        background: #e5e7eb;
    }

    /* Header */
    .hlcrf-header {
        height: var(--header-height);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px dashed rgba(139, 92, 246, 0.3);
    }

    /* Footer */
    .hlcrf-footer {
        height: var(--footer-height);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-top: 1px dashed rgba(139, 92, 246, 0.3);
    }

    /* Body container */
    .hlcrf-body {
        flex: 1;
        display: flex;
        min-height: 0;
        overflow: hidden;
    }

    /* Left sidebar */
    .hlcrf-left {
        width: var(--left-width);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-right: 1px dashed rgba(139, 92, 246, 0.3);
    }

    /* Right sidebar */
    .hlcrf-right {
        width: var(--right-width);
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-left: 1px dashed rgba(139, 92, 246, 0.3);
    }

    /* Content area */
    .hlcrf-content {
        flex: 1;
        display: flex;
        justify-content: center;
        overflow: hidden;
    }

    .hlcrf-content-inner {
        width: 100%;
        max-width: var(--content-max-width);
        height: 100%;
        overflow-y: auto;
        scrollbar-width: none;
    }

    .hlcrf-content-inner::-webkit-scrollbar {
        display: none;
    }

    /* Empty region state */
    .hlcrf-region-empty {
        background: repeating-linear-gradient(
            45deg,
            transparent,
            transparent 10px,
            rgba(139, 92, 246, 0.03) 10px,
            rgba(139, 92, 246, 0.03) 20px
        );
    }

    /* Enabled region state */
    .hlcrf-region-enabled {
        background: rgba(255, 255, 255, 0.5);
    }

    /* Add region button */
    .hlcrf-add-region {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: rgba(139, 92, 246, 0.1);
        border: 1px dashed rgba(139, 92, 246, 0.4);
        border-radius: 8px;
        color: #7c3aed;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .hlcrf-add-region:hover {
        background: rgba(139, 92, 246, 0.2);
        border-color: rgba(139, 92, 246, 0.6);
    }

    .hlcrf-add-region i {
        font-size: 11px;
    }

    /* Vertical button for sidebars */
    .hlcrf-add-region-vertical {
        flex-direction: column;
        padding: 16px 12px;
    }

    .hlcrf-add-region-vertical span {
        writing-mode: vertical-rl;
        text-orientation: mixed;
    }
</style>
