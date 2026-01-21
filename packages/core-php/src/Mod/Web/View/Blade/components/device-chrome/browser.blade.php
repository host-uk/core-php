@props([
    'viewport' => ['width' => 1440, 'height' => 900],
    'chrome' => [],
    'colours' => [],
    'url' => null,
])

@php
    $toolbarHeight = $chrome['toolbar_height'] ?? 40;
    $cornerRadius = $chrome['corner_radius'] ?? 12;
    $variant = $chrome['variant'] ?? 'minimal';

    $frameColour = $colours['frame'] ?? '#1e1e1e';
    $toolbarColour = $colours['toolbar'] ?? '#2d2d2d';
    $toolbarTextColour = $colours['toolbar_text'] ?? '#9ca3af';
    $controlsColour = $colours['controls'] ?? '#4b5563';

    $frameWidth = $viewport['width'];
    $frameHeight = $viewport['height'] + $toolbarHeight;
@endphp

<div
    class="browser-device-chrome"
    style="
        --toolbar-height: {{ $toolbarHeight }}px;
        --corner-radius: {{ $cornerRadius }}px;
        --frame-colour: {{ $frameColour }};
        --toolbar-colour: {{ $toolbarColour }};
        --toolbar-text-colour: {{ $toolbarTextColour }};
        --controls-colour: {{ $controlsColour }};
        --viewport-width: {{ $viewport['width'] }}px;
        --viewport-height: {{ $viewport['height'] }}px;
        width: {{ $frameWidth }}px;
        height: {{ $frameHeight }}px;
    "
>
    {{-- Browser frame --}}
    <div class="browser-frame">
        {{-- Toolbar --}}
        <div class="browser-toolbar">
            {{-- Traffic lights --}}
            <div class="browser-traffic-lights">
                <span class="browser-btn browser-btn-close"></span>
                <span class="browser-btn browser-btn-minimize"></span>
                <span class="browser-btn browser-btn-maximize"></span>
            </div>

            {{-- URL bar --}}
            <div class="browser-url-bar">
                @if($variant === 'full')
                    <div class="browser-nav-buttons">
                        <span class="browser-nav-btn">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 18l-6-6 6-6"/>
                            </svg>
                        </span>
                        <span class="browser-nav-btn">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </span>
                    </div>
                @endif

                <div class="browser-url-input">
                    <svg class="browser-lock-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <span class="browser-url-text">{{ $url ?? 'lt.hn' }}</span>
                </div>
            </div>

            @if($variant === 'full')
                {{-- Browser actions --}}
                <div class="browser-actions">
                    <span class="browser-action-btn">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                            <polyline points="16 6 12 2 8 6"/>
                            <line x1="12" y1="2" x2="12" y2="15"/>
                        </svg>
                    </span>
                </div>
            @endif
        </div>

        {{-- Screen area --}}
        <div class="browser-screen">
            <div class="browser-viewport">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

<style>
    .browser-device-chrome {
        position: relative;
        flex-shrink: 0;
    }

    .browser-frame {
        width: 100%;
        height: 100%;
        background: var(--frame-colour);
        border-radius: var(--corner-radius);
        overflow: hidden;
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.1),
            0 25px 50px -12px rgba(0, 0, 0, 0.4);
    }

    .browser-toolbar {
        display: flex;
        align-items: center;
        gap: 12px;
        height: var(--toolbar-height);
        padding: 0 12px;
        background: var(--toolbar-colour);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Traffic lights */
    .browser-traffic-lights {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .browser-btn {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }

    .browser-btn-close {
        background: #ff5f57;
    }

    .browser-btn-minimize {
        background: #febc2e;
    }

    .browser-btn-maximize {
        background: #28c840;
    }

    /* URL bar */
    .browser-url-bar {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }

    .browser-nav-buttons {
        display: flex;
        gap: 4px;
        flex-shrink: 0;
    }

    .browser-nav-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        color: var(--controls-colour);
        border-radius: 4px;
    }

    .browser-nav-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .browser-url-input {
        display: flex;
        align-items: center;
        gap: 6px;
        flex: 1;
        max-width: 400px;
        padding: 6px 12px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 6px;
        font-size: 13px;
    }

    .browser-lock-icon {
        flex-shrink: 0;
        color: #10b981;
    }

    .browser-url-text {
        color: var(--toolbar-text-colour);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Actions */
    .browser-actions {
        display: flex;
        gap: 8px;
        flex-shrink: 0;
    }

    .browser-action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        color: var(--controls-colour);
        border-radius: 4px;
    }

    .browser-action-btn:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    /* Screen */
    .browser-screen {
        width: var(--viewport-width);
        height: var(--viewport-height);
        background: #000;
        overflow: hidden;
    }

    .browser-viewport {
        width: 100%;
        height: 100%;
        overflow-y: auto;
        overflow-x: hidden;
    }
</style>
