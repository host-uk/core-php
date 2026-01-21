@props([
    'viewport' => ['width' => 393, 'height' => 852],
    'chrome' => [],
    'colours' => [],
])

@php
    $bezel = $chrome['bezel'] ?? 12;
    $cornerRadius = $chrome['corner_radius'] ?? 55;
    $variant = $chrome['variant'] ?? 'dynamic-island';

    $bezelColour = $colours['bezel'] ?? '#1a1a1a';
    $screenBg = $colours['screen_bg'] ?? '#000000';
    $dynamicIslandColour = $colours['dynamic_island'] ?? '#000000';
    $homeIndicatorColour = $colours['home_indicator'] ?? 'rgba(255, 255, 255, 0.3)';

    $frameWidth = $viewport['width'] + ($bezel * 2);
    $frameHeight = $viewport['height'] + ($bezel * 2);
    $innerRadius = max($cornerRadius - $bezel, 0);
@endphp

<div
    class="phone-device-chrome"
    style="
        --bezel: {{ $bezel }}px;
        --corner-radius: {{ $cornerRadius }}px;
        --inner-radius: {{ $innerRadius }}px;
        --bezel-colour: {{ $bezelColour }};
        --screen-bg: {{ $screenBg }};
        --dynamic-island-colour: {{ $dynamicIslandColour }};
        --home-indicator-colour: {{ $homeIndicatorColour }};
        --viewport-width: {{ $viewport['width'] }}px;
        --viewport-height: {{ $viewport['height'] }}px;
        width: {{ $frameWidth }}px;
        height: {{ $frameHeight }}px;
    "
>
    {{-- Device frame (bezel) --}}
    <div class="phone-bezel">
        {{-- Side buttons (visual detail) --}}
        <div class="phone-button phone-button-silent"></div>
        <div class="phone-button phone-button-volume-up"></div>
        <div class="phone-button phone-button-volume-down"></div>
        <div class="phone-button phone-button-power"></div>

        {{-- Screen area --}}
        <div class="phone-screen">
            {{-- Dynamic Island / Notch --}}
            @if($variant === 'dynamic-island')
                <div class="phone-dynamic-island"></div>
            @elseif($variant === 'notch')
                <div class="phone-notch"></div>
            @endif

            {{-- Content viewport --}}
            <div class="phone-viewport">
                {{ $slot }}
            </div>

            {{-- Home indicator --}}
            <div class="phone-home-indicator"></div>
        </div>
    </div>
</div>

<style>
    .phone-device-chrome {
        position: relative;
        flex-shrink: 0;
    }

    .phone-bezel {
        position: relative;
        width: 100%;
        height: 100%;
        background: var(--bezel-colour);
        border-radius: var(--corner-radius);
        padding: var(--bezel);
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.1),
            0 25px 50px -12px rgba(0, 0, 0, 0.5),
            inset 0 1px 0 rgba(255, 255, 255, 0.1);
    }

    /* Side buttons */
    .phone-button {
        position: absolute;
        background: var(--bezel-colour);
        border-radius: 2px;
    }

    .phone-button-silent {
        left: -3px;
        top: 100px;
        width: 3px;
        height: 28px;
    }

    .phone-button-volume-up {
        left: -3px;
        top: 150px;
        width: 3px;
        height: 52px;
    }

    .phone-button-volume-down {
        left: -3px;
        top: 210px;
        width: 3px;
        height: 52px;
    }

    .phone-button-power {
        right: -3px;
        top: 170px;
        width: 3px;
        height: 80px;
    }

    .phone-screen {
        position: relative;
        width: var(--viewport-width);
        height: var(--viewport-height);
        background: var(--screen-bg);
        border-radius: var(--inner-radius);
        overflow: hidden;
    }

    /* Dynamic Island */
    .phone-dynamic-island {
        position: absolute;
        top: 12px;
        left: 50%;
        transform: translateX(-50%);
        width: 126px;
        height: 37px;
        background: var(--dynamic-island-colour);
        border-radius: 20px;
        z-index: 100;
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
    }

    /* Notch (older iPhones) */
    .phone-notch {
        position: absolute;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 210px;
        height: 30px;
        background: var(--dynamic-island-colour);
        border-radius: 0 0 20px 20px;
        z-index: 100;
    }

    .phone-viewport {
        position: relative;
        width: 100%;
        height: 100%;
        overflow-y: auto;
        overflow-x: hidden;
    }

    /* Home indicator */
    .phone-home-indicator {
        position: absolute;
        bottom: 8px;
        left: 50%;
        transform: translateX(-50%);
        width: 134px;
        height: 5px;
        background: var(--home-indicator-colour);
        border-radius: 3px;
        z-index: 100;
    }
</style>
