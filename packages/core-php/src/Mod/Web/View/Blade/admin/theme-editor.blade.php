<div class="space-y-6">
    @if($showGallery)
        {{-- Theme Gallery --}}
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Choose a Theme</h3>
                <button
                    wire:click="startCustomising"
                    class="text-sm text-violet-600 dark:text-violet-400 hover:text-violet-800 dark:hover:text-violet-300"
                >
                    <i class="fa-solid fa-palette mr-1"></i> Create Custom
                </button>
            </div>

            {{-- System Themes --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @foreach($this->availableThemes as $theme)
                    @php
                        $bg = $theme->getBackground();
                        $btn = $theme->getButton();
                        $bgStyle = match($bg['type'] ?? 'color') {
                            'gradient' => "background: linear-gradient(135deg, " . ($bg['gradient_start'] ?? '#fff') . ", " . ($bg['gradient_end'] ?? '#000') . ")",
                            default => "background: " . ($bg['color'] ?? '#ffffff'),
                        };
                        $isLocked = $theme->is_locked ?? false;
                        $isSelected = $themeId === $theme->id;
                    @endphp
                    <button
                        wire:click="selectTheme({{ $theme->id }})"
                        class="relative rounded-xl overflow-hidden border-2 transition-all hover:shadow-lg {{ $isSelected ? 'border-violet-500 ring-2 ring-violet-500/20' : 'border-gray-200 dark:border-gray-700' }} {{ $isLocked ? 'opacity-75' : '' }}"
                        wire:key="theme-{{ $theme->id }}"
                    >
                        {{-- Theme Preview --}}
                        <div
                            class="aspect-[3/4] p-3 flex flex-col items-center justify-center"
                            style="{{ $bgStyle }}"
                        >
                            {{-- Avatar placeholder --}}
                            <div class="w-10 h-10 rounded-full bg-white/20 mb-2"></div>

                            {{-- Button previews --}}
                            <div class="w-full space-y-1.5">
                                @for($i = 0; $i < 3; $i++)
                                    <div
                                        class="h-6 w-full"
                                        style="background: {{ $btn['background_color'] ?? '#000' }}; border-radius: {{ $btn['border_radius'] ?? '8px' }};"
                                    ></div>
                                @endfor
                            </div>
                        </div>

                        {{-- Theme Name --}}
                        <div class="px-3 py-2 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $theme->name }}</span>
                                @if($theme->is_premium)
                                    <span class="text-xs text-amber-600 dark:text-amber-400">
                                        <i class="fa-solid fa-crown"></i>
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Locked Overlay --}}
                        @if($isLocked)
                            <div class="absolute inset-0 bg-gray-900/50 flex items-center justify-center">
                                <div class="text-white text-center">
                                    <i class="fa-solid fa-lock text-2xl mb-1"></i>
                                    <p class="text-xs">Pro</p>
                                </div>
                            </div>
                        @endif

                        {{-- Selected Check --}}
                        @if($isSelected)
                            <div class="absolute top-2 right-2 w-6 h-6 bg-violet-500 rounded-full flex items-center justify-center">
                                <i class="fa-solid fa-check text-white text-xs"></i>
                            </div>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @else
        {{-- Theme Editor --}}
        <div>
            <div class="flex items-center gap-3 mb-4">
                <button
                    wire:click="backToGallery"
                    class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                </button>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ $themeId && !$isEditing ? 'Theme: ' . $themeName : 'Customise Theme' }}
                </h3>
            </div>

            {{-- Live Preview --}}
            <div
                class="rounded-xl overflow-hidden mb-6 aspect-[3/4] max-h-64 flex flex-col items-center justify-center p-4"
                style="{{ $this->previewCss }}"
            >
                {{-- Avatar --}}
                <div class="w-12 h-12 rounded-full bg-white/20 mb-3"></div>

                {{-- Name placeholder --}}
                <div class="h-4 w-24 rounded bg-current opacity-80 mb-1"></div>
                <div class="h-3 w-32 rounded bg-current opacity-40 mb-4"></div>

                {{-- Button previews --}}
                <div class="w-full max-w-[200px] space-y-2">
                    @for($i = 0; $i < 3; $i++)
                        <div
                            class="h-8 w-full flex items-center justify-center text-xs font-medium"
                            style="{{ $this->buttonPreviewCss }}"
                        >
                            Link {{ $i + 1 }}
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Editor Tabs --}}
            <div class="border-b border-gray-200 dark:border-gray-700 mb-4">
                <nav class="-mb-px flex space-x-4">
                    @foreach(['background' => 'Background', 'buttons' => 'Buttons', 'typography' => 'Typography'] as $tab => $label)
                        <button
                            wire:click="$set('activeTab', '{{ $tab }}')"
                            class="py-2 px-1 border-b-2 text-sm font-medium transition-colors {{ $activeTab === $tab ? 'border-violet-500 text-violet-600 dark:text-violet-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- Tab Content --}}
            <div class="space-y-4">
                @if($activeTab === 'background')
                    {{-- Background Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Background Type</label>
                        <select
                            wire:model.live="backgroundType"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                        >
                            <option value="color">Solid Colour</option>
                            <option value="gradient">Gradient</option>
                        </select>
                    </div>

                    @if($backgroundType === 'color')
                        {{-- Solid Colour --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Background Colour</label>
                            <div class="flex items-center gap-3">
                                <input
                                    type="color"
                                    wire:model.live="backgroundColor"
                                    class="w-12 h-10 rounded border-gray-300 dark:border-gray-600 cursor-pointer"
                                >
                                <input
                                    type="text"
                                    wire:model.blur="backgroundColor"
                                    class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm font-mono"
                                    placeholder="#ffffff"
                                >
                            </div>
                        </div>
                    @else
                        {{-- Gradient Colours --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Start Colour</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="color"
                                        wire:model.live="gradientStart"
                                        class="w-10 h-8 rounded border-gray-300 dark:border-gray-600 cursor-pointer"
                                    >
                                    <input
                                        type="text"
                                        wire:model.blur="gradientStart"
                                        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 text-xs font-mono"
                                    >
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">End Colour</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="color"
                                        wire:model.live="gradientEnd"
                                        class="w-10 h-8 rounded border-gray-300 dark:border-gray-600 cursor-pointer"
                                    >
                                    <input
                                        type="text"
                                        wire:model.blur="gradientEnd"
                                        class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 text-xs font-mono"
                                    >
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Text Colour --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Text Colour</label>
                        <div class="flex items-center gap-3">
                            <input
                                type="color"
                                wire:model.live="textColor"
                                class="w-12 h-10 rounded border-gray-300 dark:border-gray-600 cursor-pointer"
                            >
                            <input
                                type="text"
                                wire:model.blur="textColor"
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm font-mono"
                                placeholder="#000000"
                            >
                        </div>
                    </div>

                @elseif($activeTab === 'buttons')
                    {{-- Button Background --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Button Background</label>
                        <div class="flex items-center gap-3">
                            <input
                                type="color"
                                wire:model.live="buttonBgColor"
                                class="w-12 h-10 rounded border-gray-300 dark:border-gray-600 cursor-pointer"
                            >
                            <input
                                type="text"
                                wire:model.blur="buttonBgColor"
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm font-mono"
                            >
                        </div>
                    </div>

                    {{-- Button Text --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Button Text</label>
                        <div class="flex items-center gap-3">
                            <input
                                type="color"
                                wire:model.live="buttonTextColor"
                                class="w-12 h-10 rounded border-gray-300 dark:border-gray-600 cursor-pointer"
                            >
                            <input
                                type="text"
                                wire:model.blur="buttonTextColor"
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm font-mono"
                            >
                        </div>
                    </div>

                    {{-- Border Radius --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Corner Style</label>
                        <select
                            wire:model.live="buttonBorderRadius"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                        >
                            @foreach($this->borderRadiusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Border Width --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Border Width</label>
                        <select
                            wire:model.live="buttonBorderWidth"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                        >
                            <option value="0">None</option>
                            <option value="1px">Thin (1px)</option>
                            <option value="2px">Medium (2px)</option>
                            <option value="3px">Thick (3px)</option>
                        </select>
                    </div>

                    @if($buttonBorderWidth !== '0')
                        {{-- Border Colour --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Border Colour</label>
                            <div class="flex items-center gap-3">
                                <input
                                    type="color"
                                    wire:model.live="buttonBorderColor"
                                    class="w-12 h-10 rounded border-gray-300 dark:border-gray-600 cursor-pointer"
                                >
                                <input
                                    type="text"
                                    wire:model.blur="buttonBorderColor"
                                    class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm font-mono"
                                >
                            </div>
                        </div>
                    @endif

                @elseif($activeTab === 'typography')
                    {{-- Font Family --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Font Family</label>
                        <select
                            wire:model.live="fontFamily"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                        >
                            @foreach($this->fontFamilies as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Font Preview --}}
                    <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <p
                            class="text-lg text-gray-900 dark:text-gray-100"
                            style="font-family: '{{ $fontFamily }}', sans-serif;"
                        >
                            The quick brown fox jumps over the lazy dog.
                        </p>
                        <p
                            class="text-sm text-gray-600 dark:text-gray-400 mt-2"
                            style="font-family: '{{ $fontFamily }}', sans-serif;"
                        >
                            ABCDEFGHIJKLMNOPQRSTUVWXYZ<br>
                            abcdefghijklmnopqrstuvwxyz<br>
                            0123456789
                        </p>
                    </div>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700 mt-6">
                @if($biolinkId)
                    <button
                        wire:click="applyToBiolink"
                        class="btn bg-violet-500 hover:bg-violet-600 text-white"
                    >
                        <i class="fa-solid fa-check mr-2"></i> Apply Theme
                    </button>
                @endif

                <div class="flex items-center gap-2">
                    @if($isEditing)
                        @if($themeId && !BioLinkTheme::find($themeId)?->is_system)
                            <button
                                wire:click="updateCustomTheme"
                                class="btn border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300"
                            >
                                <i class="fa-solid fa-save mr-2"></i> Update
                            </button>
                        @else
                            <div x-data="{ showSave: false }" class="relative">
                                <button
                                    @click="showSave = !showSave"
                                    class="btn border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300"
                                >
                                    <i class="fa-solid fa-save mr-2"></i> Save as Theme
                                </button>

                                {{-- Save Popover --}}
                                <div
                                    x-show="showSave"
                                    x-transition
                                    @click.away="showSave = false"
                                    class="absolute bottom-full right-0 mb-2 w-64 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 p-4"
                                >
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Theme Name</label>
                                    <input
                                        type="text"
                                        wire:model="themeName"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm mb-3"
                                        placeholder="My Custom Theme"
                                    >
                                    <button
                                        wire:click="saveAsCustomTheme"
                                        @click="showSave = false"
                                        class="w-full btn bg-violet-500 hover:bg-violet-600 text-white text-sm"
                                    >
                                        Save Theme
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
