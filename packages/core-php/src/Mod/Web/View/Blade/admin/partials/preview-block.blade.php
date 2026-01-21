{{--
    Preview Block Renderer
    Renders a simplified visual preview of each block type for the editor preview.
    These are visual approximations - the actual public page uses the full block templates.

    Region-aware rendering: same block type renders differently based on parent region.
    - H (Header): compact nav-style
    - L/R (Sidebars): sidebar-optimised
    - C (Content): full bio-style (default)
    - F (Footer): footer-style
--}}

@use('Illuminate\Support\Facades\Storage')

@php
    $settings = is_array($block->settings) ? $block->settings : (array) $block->settings;
    $region = $region ?? 'C';  // Default to Content region

    // Get theme button styles for fallback (via block's biolink relationship)
    $themeButton = $block->biolink?->getButtonStyle() ?? [];
    $themeTextColor = $block->biolink?->getTextColor() ?? '#000000';
@endphp

@switch($block->type)
    @case('link')
        @php
            $name = $settings['name'] ?? 'Link Button';
            $bgColor = $settings['background_color'] ?? $themeButton['background_color'] ?? '#000000';
            $textColor = $settings['text_color'] ?? $themeButton['text_color'] ?? '#ffffff';
            $themeBorderRadius = $themeButton['border_radius'] ?? '8px';
            $borderRadius = match($settings['border_radius'] ?? null) {
                'square' => 'rounded-none',
                'rounded' => 'rounded-lg',
                'pill' => 'rounded-full',
                default => match(true) {
                    str_contains($themeBorderRadius, '999') || str_contains($themeBorderRadius, 'full') || (int)$themeBorderRadius >= 24 => 'rounded-full',
                    (int)$themeBorderRadius >= 8 => 'rounded-lg',
                    (int)$themeBorderRadius >= 4 => 'rounded-md',
                    default => 'rounded-lg',
                },
            };
        @endphp

        @if($region === 'H')
            {{-- Header: Nav menu item --}}
            <span class="px-3 py-1.5 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors">
                {{ $name }}
            </span>
        @elseif($region === 'F')
            {{-- Footer: Small muted link --}}
            <span class="text-xs text-gray-500 hover:text-gray-700 transition-colors">
                {{ $name }}
            </span>
        @elseif(in_array($region, ['L', 'R']))
            {{-- Sidebar: Compact link with subtle background --}}
            <div class="px-3 py-2 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors text-gray-700">
                {{ $name }}
            </div>
        @else
            {{-- Content: Full bio button (default) --}}
            <div
                class="w-full py-3 px-4 text-center font-medium {{ $borderRadius }} transition-transform hover:scale-[1.02]"
                style="background: {{ $bgColor }}; color: {{ $textColor }};"
            >
                {{ $name }}
            </div>
        @endif
        @break

    @case('heading')
        @php
            $text = $settings['text'] ?? 'Heading';
            $size = $settings['size'] ?? 'h2';
            $alignment = $settings['alignment'] ?? 'center';
            $textSize = match($size) {
                'h1' => 'text-2xl',
                'h2' => 'text-xl',
                'h3' => 'text-lg',
                'h4' => 'text-base',
                default => 'text-xl',
            };
            $textAlign = match($alignment) {
                'left' => 'text-left',
                'right' => 'text-right',
                default => 'text-center',
            };
        @endphp

        @if($region === 'H')
            {{-- Header: Site title / logo text --}}
            <span class="text-lg font-bold text-gray-900 tracking-tight">
                {{ $text }}
            </span>
        @elseif($region === 'F')
            {{-- Footer: Section title --}}
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ $text }}
            </p>
        @elseif(in_array($region, ['L', 'R']))
            {{-- Sidebar: Compact heading --}}
            <p class="text-sm font-semibold text-gray-700 {{ $textAlign }}">
                {{ $text }}
            </p>
        @else
            {{-- Content: Full section heading (default) --}}
            <div class="{{ $textSize }} {{ $textAlign }} font-bold" style="color: {{ $themeTextColor }};">
                {{ $text }}
            </div>
        @endif
        @break

    @case('paragraph')
        @php
            $text = $settings['text'] ?? 'Your text here...';
            $alignment = $settings['alignment'] ?? 'center';
            $textAlign = match($alignment) {
                'left' => 'text-left',
                'right' => 'text-right',
                default => 'text-center',
            };
        @endphp
        <div class="text-sm {{ $textAlign }}" style="color: {{ $themeTextColor }};">
            {{ Str::limit($text, 120) }}
        </div>
        @break

    @case('avatar')
        @php
            $size = $settings['size'] ?? 96;
            $borderRadius = ($settings['border_radius'] ?? 'round') === 'round' ? 'rounded-full' : 'rounded-lg';
            $imageUrl = $settings['image'] ?? null;
            if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
                $imageUrl = Storage::url($imageUrl);
            }
        @endphp
        <div class="flex justify-center">
            @if($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    alt="Avatar"
                    class="{{ $borderRadius }} object-cover"
                    style="width: {{ min($size, 120) }}px; height: {{ min($size, 120) }}px;"
                >
            @else
                <div
                    class="{{ $borderRadius }} bg-gray-300 flex items-center justify-center"
                    style="width: {{ min($size, 120) }}px; height: {{ min($size, 120) }}px;"
                >
                    <i class="fa-solid fa-user text-gray-500 text-2xl"></i>
                </div>
            @endif
        </div>
        @break

    @case('divider')
        @php
            $style = $settings['style'] ?? 'solid';
            $color = $settings['color'] ?? '#e5e7eb';
            $borderStyle = match($style) {
                'dashed' => 'dashed',
                'dotted' => 'dotted',
                default => 'solid',
            };
        @endphp

        @if($region === 'H')
            {{-- Header: Vertical separator --}}
            <div class="h-4 w-px bg-gray-300 mx-2"></div>
        @elseif($region === 'F')
            {{-- Footer: Subtle full-width --}}
            <div class="w-full" style="height: 1px; background: {{ $color }}; opacity: 0.5;"></div>
        @elseif(in_array($region, ['L', 'R']))
            {{-- Sidebar: Short horizontal rule --}}
            <div class="w-8 mx-auto my-2" style="height: 1px; background: {{ $color }};"></div>
        @else
            {{-- Content: Full width divider (default) --}}
            <div
                class="w-full my-2"
                style="height: 1px; border-top: 1px {{ $borderStyle }} {{ $color }};"
            ></div>
        @endif
        @break

    @case('socials')
        @php
            $userPlatforms = $settings['platforms'] ?? [];
            $style = $settings['style'] ?? 'colored';
            $size = $settings['size'] ?? 'medium';
            $configPlatforms = config('webpage.social_platforms', []);

            // Helpers using config
            $getIconClass = fn($platform) => $configPlatforms[$platform]['icon'] ?? 'fa-solid fa-link';
            $getBgColor = fn($platform) => $configPlatforms[$platform]['color'] ?? '#6b7280';

            // Filter to only platforms with values
            $platforms = array_filter($userPlatforms, fn($url) => !empty($url));
        @endphp

        @if($region === 'H')
            {{-- Header: Compact icon row (no labels, smaller) --}}
            <div class="flex items-center gap-1">
                @forelse($platforms as $platform => $url)
                    <div class="w-6 h-6 flex items-center justify-center text-gray-600 hover:text-gray-900 transition-colors">
                        <i class="{{ $getIconClass($platform) }} text-sm"></i>
                    </div>
                @empty
                    <div class="w-6 h-6 flex items-center justify-center text-gray-400">
                        <i class="fa-solid fa-share-nodes text-xs"></i>
                    </div>
                @endforelse
            </div>
        @elseif($region === 'F')
            {{-- Footer: Small muted icons --}}
            <div class="flex items-center gap-3">
                @forelse($platforms as $platform => $url)
                    <div class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="{{ $getIconClass($platform) }} text-sm"></i>
                    </div>
                @empty
                    <div class="text-gray-300">
                        <i class="fa-solid fa-share-nodes text-xs"></i>
                    </div>
                @endforelse
            </div>
        @elseif(in_array($region, ['L', 'R']))
            {{-- Sidebar: Vertical icon stack --}}
            <div class="flex flex-col items-center gap-2">
                @forelse($platforms as $platform => $url)
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-100 transition-colors">
                        <i class="{{ $getIconClass($platform) }}"></i>
                    </div>
                @empty
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-400">
                        <i class="fa-solid fa-share-nodes text-xs"></i>
                    </div>
                @endforelse
            </div>
        @else
            {{-- Content: Full social buttons (default) --}}
            @php
                $iconSize = match($size) {
                    'small' => 'w-8 h-8 text-sm',
                    'large' => 'w-12 h-12 text-xl',
                    default => 'w-10 h-10 text-base',
                };
            @endphp
            <div class="flex justify-center gap-2 flex-wrap">
                @forelse($platforms as $platform => $url)
                    <div
                        class="{{ $iconSize }} rounded-full flex items-center justify-center"
                        style="{{ $style === 'colored' ? "background: {$getBgColor($platform)}; color: white;" : 'background: #f3f4f6; color: #374151;' }}"
                    >
                        <i class="{{ $getIconClass($platform) }}"></i>
                    </div>
                @empty
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <i class="fa-solid fa-share-nodes text-gray-400"></i>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <i class="fa-solid fa-share-nodes text-gray-400"></i>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <i class="fa-solid fa-share-nodes text-gray-400"></i>
                    </div>
                @endforelse
            </div>
        @endif
        @break

    @case('youtube')
        @php
            $videoId = $settings['video_id'] ?? '';
        @endphp
        <div class="aspect-video bg-gray-900 rounded-lg overflow-hidden flex items-center justify-center">
            @if($videoId)
                <img
                    src="https://img.youtube.com/vi/{{ $videoId }}/mqdefault.jpg"
                    alt="YouTube video"
                    class="w-full h-full object-cover"
                >
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-play text-white ml-1"></i>
                    </div>
                </div>
            @else
                <div class="text-center text-gray-500">
                    <i class="fa-brands fa-youtube text-3xl mb-1"></i>
                    <p class="text-xs">YouTube Video</p>
                </div>
            @endif
        </div>
        @break

    @case('spotify')
        @php
            $uri = $settings['uri'] ?? '';
        @endphp
        <div class="bg-[#1db954] rounded-lg p-3 flex items-center gap-3">
            <div class="w-10 h-10 bg-black/20 rounded flex items-center justify-center">
                <i class="fa-brands fa-spotify text-white text-xl"></i>
            </div>
            <div class="flex-1 text-white">
                <p class="text-sm font-medium">Spotify</p>
                <p class="text-xs opacity-80">{{ $uri ? 'Embedded player' : 'Add Spotify URI' }}</p>
            </div>
        </div>
        @break

    @case('image')
        @php
            $imageUrl = $settings['image'] ?? null;
            if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
                $imageUrl = Storage::url($imageUrl);
            }
            $alt = $settings['alt'] ?? 'Image';
        @endphp
        <div class="rounded-lg overflow-hidden bg-gray-100">
            @if($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $alt }}" class="w-full h-auto">
            @else
                <div class="aspect-video flex items-center justify-center">
                    <i class="fa-solid fa-image text-3xl text-gray-400"></i>
                </div>
            @endif
        </div>
        @break

    @case('big_link')
        @php
            $title = $settings['title'] ?? 'Big Link';
            $description = $settings['description'] ?? '';
            $bgColor = $settings['background_color'] ?? $themeButton['background_color'] ?? '#ffffff';
            $textColor = $settings['text_color'] ?? $themeButton['text_color'] ?? '#000000';
        @endphp
        <div
            class="rounded-lg p-4 border"
            style="background: {{ $bgColor }}; color: {{ $textColor }}; border-color: {{ $textColor }}20;"
        >
            <p class="font-semibold">{{ $title }}</p>
            @if($description)
                <p class="text-sm opacity-80 mt-1">{{ Str::limit($description, 60) }}</p>
            @endif
        </div>
        @break

    @case('cta')
        @php
            $title = $settings['title'] ?? 'Call to Action';
            $buttonText = $settings['button_text'] ?? 'Click Here';
            $bgColor = $settings['background_color'] ?? $themeButton['background_color'] ?? '#7c3aed';
            $textColor = $settings['text_color'] ?? $themeButton['text_color'] ?? '#ffffff';
        @endphp
        <div class="rounded-lg p-4 text-center" style="background: {{ $bgColor }}; color: {{ $textColor }};">
            <p class="font-semibold mb-2">{{ $title }}</p>
            <span class="inline-block px-4 py-2 bg-white/20 rounded-full text-sm font-medium">
                {{ $buttonText }}
            </span>
        </div>
        @break

    @case('countdown')
        @php
            $title = $settings['title'] ?? 'Coming Soon';
        @endphp
        <div class="text-center py-3">
            <p class="text-sm text-gray-600 mb-2">{{ $title }}</p>
            <div class="flex justify-center gap-2">
                @foreach(['00', '00', '00', '00'] as $unit)
                    <div class="w-10 h-10 bg-gray-900 text-white rounded flex items-center justify-center font-mono font-bold">
                        {{ $unit }}
                    </div>
                @endforeach
            </div>
        </div>
        @break

    @case('email_collector')
    @case('contact_collector')
        @php
            $title = $settings['title'] ?? 'Stay in touch';
            $buttonText = $settings['button_text'] ?? 'Subscribe';
        @endphp
        <div class="bg-white rounded-lg p-4 border border-gray-200">
            <p class="font-medium text-gray-900 mb-2">{{ $title }}</p>
            <div class="flex gap-2">
                <div class="flex-1 h-9 bg-gray-100 rounded border border-gray-200"></div>
                <div class="px-4 h-9 bg-gray-900 text-white rounded flex items-center text-sm font-medium">
                    {{ $buttonText }}
                </div>
            </div>
        </div>
        @break

    @case('faq')
        @php
            $items = $settings['items'] ?? [];
        @endphp
        <div class="space-y-2">
            @forelse(array_slice($items, 0, 2) as $item)
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-gray-900 text-sm">{{ $item['question'] ?? 'Question?' }}</span>
                        <i class="fa-solid fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-gray-900 text-sm">FAQ Item</span>
                        <i class="fa-solid fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                </div>
            @endforelse
        </div>
        @break

    @case('text_reveal')
        @php
            $buttonText = $settings['button_text'] ?? 'Reveal';
        @endphp
        <div class="text-center">
            <div class="inline-block px-6 py-2 bg-gray-900 text-white rounded-full text-sm font-medium">
                <i class="fa-solid fa-eye mr-2"></i>{{ $buttonText }}
            </div>
        </div>
        @break

    @case('alert')
        @php
            $type = $settings['type'] ?? 'info';
            $message = $settings['message'] ?? 'Alert message';
            $bgColor = match($type) {
                'success' => 'bg-green-100 text-green-800 border-green-200',
                'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                'error' => 'bg-red-100 text-red-800 border-red-200',
                default => 'bg-blue-100 text-blue-800 border-blue-200',
            };
            $icon = match($type) {
                'success' => 'fa-check-circle',
                'warning' => 'fa-exclamation-triangle',
                'error' => 'fa-times-circle',
                default => 'fa-info-circle',
            };
        @endphp
        <div class="rounded-lg p-3 border {{ $bgColor }}">
            <div class="flex items-center gap-2">
                <i class="fa-solid {{ $icon }}"></i>
                <span class="text-sm">{{ Str::limit($message, 50) }}</span>
            </div>
        </div>
        @break

    @default
        {{-- Generic block preview for unknown types --}}
        @php
            $blockConfig = config("bio.block_types.{$block->type}", []);
            $icon = $blockConfig['icon'] ?? 'fas fa-cube';
            $name = $blockConfig['name'] ?? ucfirst($block->type);
        @endphp
        <div class="bg-white rounded-lg p-4 border border-gray-200 text-center">
            <div class="w-10 h-10 mx-auto mb-2 bg-gray-100 rounded-lg flex items-center justify-center">
                <i class="{{ $icon }} text-gray-500"></i>
            </div>
            <p class="text-sm font-medium text-gray-700">{{ $name }}</p>
        </div>
@endswitch
