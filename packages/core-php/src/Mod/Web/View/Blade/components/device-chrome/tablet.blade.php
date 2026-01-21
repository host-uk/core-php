@props([
    'viewport' => ['width' => 820, 'height' => 1180],
    'chrome' => [],
    'colours' => [],
])

@php
    $bezel = $chrome['bezel'] ?? 20;
    $cornerRadius = $chrome['corner_radius'] ?? 20;
    $variant = $chrome['variant'] ?? 'modern';

    $bezelColour = $colours['bezel'] ?? '#2a2a2a';
    $screenBg = $colours['screen_bg'] ?? '#000000';
    $homeIndicatorColour = $colours['home_indicator'] ?? 'rgba(255, 255, 255, 0.2)';

    $frameWidth = $viewport['width'] + ($bezel * 2);
    $frameHeight = $viewport['height'] + ($bezel * 2);
    $innerRadius = max($cornerRadius - ($bezel / 2), 0);
@endphp

<div
    class="tablet-device-chrome"
    style="
        --bezel: {{ $bezel }}px;
        --corner-radius: {{ $cornerRadius }}px;
        --inner-radius: {{ $innerRadius }}px;
        --bezel-colour: {{ $bezelColour }};
        --screen-bg: {{ $screenBg }};
        --home-indicator-colour: {{ $homeIndicatorColour }};
        --viewport-width: {{ $viewport['width'] }}px;
        --viewport-height: {{ $viewport['height'] }}px;
        width: {{ $frameWidth }}px;
        height: {{ $frameHeight }}px;
    "
>
    {{-- Device frame (bezel) --}}
    <div class="tablet-bezel">
        {{-- Camera (right side for landscape) --}}
        <div class="tablet-camera"></div>

        {{-- Buttons (top edge for landscape) --}}
        <div class="tablet-button tablet-button-power"></div>
        <div class="tablet-button tablet-button-volume-up"></div>
        <div class="tablet-button tablet-button-volume-down"></div>

        {{-- Screen area --}}
        <div class="tablet-screen">
            {{-- Content viewport --}}
            <div class="tablet-viewport">
                {{ $slot }}
            </div>

            {{-- Home indicator (modern iPad) --}}
            @if($variant === 'modern')
                <div class="tablet-home-indicator"></div>
            @endif
        </div>
    </div>
</div>

<style>
    .tablet-device-chrome {
        position: relative;
        flex-shrink: 0;
    }

    .tablet-bezel {
        position: relative;
        width: 100%;
        height: 100%;
        background: var(--bezel-colour);
        border-radius: var(--corner-radius);
        padding: var(--bezel);
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.08),
            0 25px 50px -12px rgba(0, 0, 0, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }

    /* Camera (right side for landscape) */
    .tablet-camera {
        position: absolute;
        right: calc(var(--bezel) / 2);
        top: 50%;
        transform: translate(50%, -50%);
        width: 8px;
        height: 8px;
        background: #111;
        border-radius: 50%;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1);
    }

    /* Buttons (top edge for landscape) */
    .tablet-button {
        position: absolute;
        background: var(--bezel-colour);
        border-radius: 2px;
    }

    .tablet-button-power {
        top: -3px;
        right: 70px;
        width: 60px;
        height: 3px;
    }

    .tablet-button-volume-up {
        top: -3px;
        right: 150px;
        width: 45px;
        height: 3px;
    }

    .tablet-button-volume-down {
        top: -3px;
        right: 205px;
        width: 45px;
        height: 3px;
    }

    .tablet-screen {
        position: relative;
        width: var(--viewport-width);
        height: var(--viewport-height);
        background: var(--screen-bg);
        border-radius: var(--inner-radius);
        overflow: hidden;
    }

    .tablet-viewport {
        position: relative;
        width: 100%;
        height: 100%;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Home indicator (landscape) */
    .tablet-home-indicator {
        position: absolute;
        bottom: 8px;
        left: 50%;
        transform: translateX(-50%);
        width: 280px;
        height: 5px;
        background: var(--home-indicator-colour);
        border-radius: 3px;
        z-index: 100;
    }
</style>
