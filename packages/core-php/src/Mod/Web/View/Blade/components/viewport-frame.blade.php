@props([
    'breakpoint' => 'phone',
    'maxHeight' => null,
    'showChrome' => true,
])

@php
    $viewports = config('webpage.viewports.viewports');
    $config = $viewports[$breakpoint] ?? $viewports['phone'];
    $viewport = $config['viewport'];
    $chrome = $config['chrome'];
    $colours = config('webpage.viewports.colours.' . $chrome['type'], []);

    // Calculate the scale to fit content within the available space
    // The container will set --viewport-max-height via style
    $viewportWidth = $viewport['width'];
    $viewportHeight = $viewport['height'];

    // Add chrome dimensions to total frame size
    $chromeType = $chrome['type'];
    $frameWidth = $viewportWidth;
    $frameHeight = $viewportHeight;

    if ($chromeType === 'phone' || $chromeType === 'tablet') {
        $bezel = $chrome['bezel'] ?? 12;
        $frameWidth += ($bezel * 2);
        $frameHeight += ($bezel * 2);
    } elseif ($chromeType === 'browser') {
        $toolbarHeight = $chrome['toolbar_height'] ?? 40;
        $frameHeight += $toolbarHeight + 8; // toolbar + padding
    }
@endphp

<div
    {{ $attributes->merge(['class' => 'viewport-frame-container']) }}
    x-data="{
        viewportWidth: {{ $viewportWidth }},
        viewportHeight: {{ $viewportHeight }},
        frameWidth: {{ $frameWidth }},
        frameHeight: {{ $frameHeight }},
        scale: 0,
        maxHeight: {{ $maxHeight ?? 'null' }},

        calculateScale() {
            const container = this.$el;
            const parent = container.parentElement;

            // Get available width from parent, fallback to container width
            let availableWidth = parent?.clientWidth || container.clientWidth || window.innerWidth * 0.8;

            // Use maxHeight prop or 70% of viewport height
            const availableHeight = this.maxHeight || (window.innerHeight * 0.7);

            // Calculate scale to fit both dimensions
            const scaleX = availableWidth / this.frameWidth;
            const scaleY = availableHeight / this.frameHeight;

            // Use the smaller scale to ensure it fits, cap at 1
            this.scale = Math.min(scaleX, scaleY, 1);
        },

        init() {
            // Wait for next frame to ensure DOM is rendered
            requestAnimationFrame(() => {
                this.calculateScale();
                // Recalculate again after a short delay in case of layout shifts
                setTimeout(() => this.calculateScale(), 100);
            });

            window.addEventListener('resize', () => this.calculateScale());
        }
    }"
    x-init="init()"
    style="
        --viewport-width: {{ $viewportWidth }}px;
        --viewport-height: {{ $viewportHeight }}px;
        --frame-width: {{ $frameWidth }}px;
        --frame-height: {{ $frameHeight }}px;
    "
>
    <div
        class="viewport-frame-scaler"
        :style="`transform: scale(${scale}); transform-origin: top center; opacity: ${scale > 0 ? 1 : 0}; transition: opacity 0.15s ease-out;`"
    >
        @if($showChrome)
            @switch($chrome['type'])
                @case('phone')
                    <x-webpage::device-chrome.phone
                        :viewport="$viewport"
                        :chrome="$chrome"
                        :colours="$colours"
                    >
                        {{ $slot }}
                    </x-webpage::device-chrome.phone>
                    @break

                @case('tablet')
                    <x-webpage::device-chrome.tablet
                        :viewport="$viewport"
                        :chrome="$chrome"
                        :colours="$colours"
                    >
                        {{ $slot }}
                    </x-webpage::device-chrome.tablet>
                    @break

                @case('browser')
                    <x-webpage::device-chrome.browser
                        :viewport="$viewport"
                        :chrome="$chrome"
                        :colours="$colours"
                    >
                        {{ $slot }}
                    </x-webpage::device-chrome.browser>
                    @break

                @default
                    <div
                        class="viewport-content-raw"
                        style="width: {{ $viewportWidth }}px; height: {{ $viewportHeight }}px; overflow: hidden;"
                    >
                        {{ $slot }}
                    </div>
            @endswitch
        @else
            <div
                class="viewport-content-raw"
                style="width: {{ $viewportWidth }}px; height: {{ $viewportHeight }}px; overflow: hidden;"
            >
                {{ $slot }}
            </div>
        @endif
    </div>

    {{-- Scale indicator (optional, for debugging) --}}
    @if(config('app.debug'))
        <div
            class="text-xs text-gray-500 mt-2 text-center"
            x-text="`${viewportWidth}×${viewportHeight} @ ${Math.round(scale * 100)}%`"
        ></div>
    @endif
</div>

<style>
    .viewport-frame-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
    }

    .viewport-frame-scaler {
        flex-shrink: 0;
    }
</style>
