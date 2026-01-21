{{--
    Generic Device Frame Component
    Renders any device mockup from the device-frames config.

    @props
    - device: string (required) - Device slug from config (e.g., 'iphone-16-pro')
    - variant: string|null - Variant slug (uses default if null)
    - width: int|null - Override width (maintains aspect ratio)
    - height: int|null - Override height (maintains aspect ratio)
    - debug: bool - Show calibration grid overlay

    Usage:
    <x-device-mockups.device-frame device="iphone-16-pro" variant="black-titanium">
        <!-- Slot content renders inside the screen area -->
    </x-device-mockups.device-frame>
--}}

@props([
    'device',
    'variant' => null,
    'width' => null,
    'height' => null,
    'debug' => false,
])

@php
    $config = config("device-frames.devices.{$device}");

    if (!$config) {
        throw new \InvalidArgumentException("Unknown device: {$device}");
    }

    // Get effective variant
    $effectiveVariant = $variant ?? $config['default_variant'] ?? array_key_first($config['variants']);
    $variantConfig = $config['variants'][$effectiveVariant] ?? null;

    if (!$variantConfig) {
        throw new \InvalidArgumentException("Unknown variant: {$effectiveVariant} for device: {$device}");
    }

    // Get dimensions
    $nativeWidth = $config['dimensions']['width'];
    $nativeHeight = $config['dimensions']['height'];
    $aspectRatio = $nativeWidth / $nativeHeight;

    // Calculate display dimensions
    if ($width && !$height) {
        $displayWidth = $width;
        $displayHeight = round($width / $aspectRatio);
    } elseif ($height && !$width) {
        $displayHeight = $height;
        $displayWidth = round($height * $aspectRatio);
    } elseif ($width && $height) {
        $displayWidth = $width;
        $displayHeight = $height;
    } else {
        // Default sizing based on viewport type
        $displayWidth = match($config['viewport']) {
            'phone' => 280,
            'tablet' => 500,
            'desktop' => 600,
            default => 280,
        };
        $displayHeight = round($displayWidth / $aspectRatio);
    }

    // Screen area as percentages
    $screen = $config['screen'];

    // Build the image path
    $publicPath = config('device-frames.public_path', 'images/device-frames');
    $extension = $config['format'];
    $imagePath = asset("{$publicPath}/{$device}/{$effectiveVariant}.{$extension}");
@endphp

<div
    class="relative"
    style="width: {{ $displayWidth }}px; height: {{ $displayHeight }}px;"
    data-device="{{ $device }}"
    data-variant="{{ $effectiveVariant }}"
>
    {{-- Screen content area (below frame) --}}
    <div
        class="absolute overflow-hidden z-0"
        style="
            left: {{ $screen['x'] }}%;
            top: {{ $screen['y'] }}%;
            width: {{ $screen['width'] }}%;
            height: {{ $screen['height'] }}%;
            border-radius: {{ $screen['radius'] }}%;
        "
    >
        {{ $slot }}
    </div>

    {{-- Device frame image (above content to mask edges) --}}
    <img
        src="{{ $imagePath }}"
        alt="{{ $config['name'] }} ({{ $variantConfig['name'] }})"
        class="absolute inset-0 w-full h-full object-contain pointer-events-none select-none z-20"
        draggable="false"
    >

    {{-- Debug calibration grid overlay --}}
    @if($debug)
        <div class="absolute inset-0 z-30 pointer-events-none">
            {{-- Percentage grid lines --}}
            <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid-{{ $device }}" width="10%" height="10%" patternUnits="userSpaceOnUse">
                        <path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,0,0,0.3)" stroke-width="0.5"/>
                    </pattern>
                    <pattern id="grid-fine-{{ $device }}" width="5%" height="5%" patternUnits="userSpaceOnUse">
                        <path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,0,0,0.15)" stroke-width="0.25"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid-fine-{{ $device }})"/>
                <rect width="100%" height="100%" fill="url(#grid-{{ $device }})"/>

                {{-- Center crosshair --}}
                <line x1="50%" y1="0" x2="50%" y2="100%" stroke="rgba(0,255,0,0.5)" stroke-width="1"/>
                <line x1="0" y1="50%" x2="100%" y2="50%" stroke="rgba(0,255,0,0.5)" stroke-width="1"/>
            </svg>

            {{-- Current screen area indicator --}}
            <div
                class="absolute border-2 border-blue-500"
                style="
                    left: {{ $screen['x'] }}%;
                    top: {{ $screen['y'] }}%;
                    width: {{ $screen['width'] }}%;
                    height: {{ $screen['height'] }}%;
                    border-radius: {{ $screen['radius'] }}%;
                "
            ></div>

            {{-- Coordinate labels --}}
            <div class="absolute top-1 left-1 bg-black/80 text-white text-[8px] px-1 py-0.5 rounded font-mono">
                {{ $device }}
            </div>
            <div class="absolute bottom-1 left-1 bg-black/80 text-white text-[8px] px-1 py-0.5 rounded font-mono leading-tight">
                x: {{ $screen['x'] }}%<br>
                y: {{ $screen['y'] }}%<br>
                w: {{ $screen['width'] }}%<br>
                h: {{ $screen['height'] }}%<br>
                r: {{ $screen['radius'] }}%
            </div>
            <div class="absolute bottom-1 right-1 bg-black/80 text-white text-[8px] px-1 py-0.5 rounded font-mono">
                {{ $displayWidth }}x{{ $displayHeight }}px
            </div>
        </div>
    @endif
</div>
