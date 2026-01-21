<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('hub.bio.edit', $this->biolinkId) }}"
                    wire:navigate
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">QR Code</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Customise and download QR code for
                        <span class="font-medium text-violet-600 dark:text-violet-400">/{{ $this->biolink?->url }}</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">
            <button
                wire:click="resetToDefaults"
                class="btn border-gray-300 dark:border-gray-600 hover:border-gray-400 text-gray-700 dark:text-gray-300"
            >
                <i class="fa-solid fa-rotate-left mr-2"></i>
                Reset
            </button>
            <button
                wire:click="save"
                class="btn bg-violet-500 hover:bg-violet-600 text-white"
            >
                <i class="fa-solid fa-check mr-2"></i>
                Save settings
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Settings panel --}}
        <div class="space-y-6">
            {{-- Colours --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    <i class="fa-solid fa-palette mr-2 text-violet-500"></i>
                    Colours
                </h3>

                <div class="space-y-4">
                    {{-- Foreground colour --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Foreground (modules)
                        </label>
                        <div class="flex items-center gap-3">
                            <input
                                type="color"
                                wire:model.live="foregroundColour"
                                class="h-10 w-14 rounded border-gray-300 dark:border-gray-600 cursor-pointer"
                            >
                            <input
                                type="text"
                                wire:model.live="foregroundColour"
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-mono"
                                placeholder="#000000"
                            >
                        </div>
                        @error('foreground_colour') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Background colour --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Background
                        </label>
                        <div class="flex items-center gap-3">
                            <input
                                type="color"
                                wire:model.live="backgroundColour"
                                class="h-10 w-14 rounded border-gray-300 dark:border-gray-600 cursor-pointer"
                            >
                            <input
                                type="text"
                                wire:model.live="backgroundColour"
                                class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm font-mono"
                                placeholder="#ffffff"
                            >
                            <button
                                wire:click="swapColours"
                                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                title="Swap colours"
                            >
                                <i class="fa-solid fa-arrows-rotate"></i>
                            </button>
                        </div>
                        @error('background_colour') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Colour presets --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Quick presets
                        </label>
                        <div class="flex flex-wrap gap-2">
                            @php
                                $presets = [
                                    'classic' => ['#000000', '#ffffff', 'Classic'],
                                    'dark' => ['#ffffff', '#1a1a2e', 'Dark'],
                                    'brand-violet' => ['#8b5cf6', '#ffffff', 'Violet'],
                                    'brand-violet-dark' => ['#ffffff', '#8b5cf6', 'Violet Dark'],
                                    'forest' => ['#1b4332', '#d8f3dc', 'Forest'],
                                    'ocean' => ['#023e8a', '#caf0f8', 'Ocean'],
                                ];
                            @endphp
                            @foreach($presets as $key => [$fg, $bg, $label])
                                <button
                                    wire:click="applyPreset('{{ $key }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border border-gray-200 dark:border-gray-600 hover:border-violet-500 transition-colors"
                                    title="{{ $label }}"
                                >
                                    <span class="w-3 h-3 rounded-full border border-gray-300" style="background: {{ $fg }}"></span>
                                    <span class="w-3 h-3 rounded-full border border-gray-300" style="background: {{ $bg }}"></span>
                                    <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Size and quality --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    <i class="fa-solid fa-sliders mr-2 text-violet-500"></i>
                    Size and quality
                </h3>

                <div class="space-y-4">
                    {{-- Size --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Download size
                        </label>
                        <select
                            wire:model.live="size"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            @foreach($this->sizePresets as $px => $label)
                                <option value="{{ $px }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('size') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Error correction level --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Error correction
                            <span class="text-xs text-gray-400 ml-1">(higher = more resilient but denser)</span>
                        </label>
                        <select
                            wire:model.live="eccLevel"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        >
                            @foreach($this->eccLevels as $level => $description)
                                <option value="{{ $level }}">{{ $level }} - {{ $description }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Use "H" (High) when embedding a logo for best scan reliability.
                        </p>
                        @error('ecc_level') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Module style --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Module style
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($this->moduleStyles as $style => $label)
                                <button
                                    wire:click="$set('moduleStyle', '{{ $style }}')"
                                    class="p-3 rounded-lg border-2 text-center text-sm transition-colors
                                        {{ $moduleStyle === $style
                                            ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20 text-violet-700 dark:text-violet-300'
                                            : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 text-gray-600 dark:text-gray-400'
                                        }}"
                                >
                                    @if($style === 'square')
                                        <i class="fa-solid fa-square text-lg mb-1"></i>
                                    @elseif($style === 'rounded')
                                        <i class="fa-solid fa-square text-lg mb-1" style="border-radius: 4px;"></i>
                                    @else
                                        <i class="fa-solid fa-circle text-lg mb-1"></i>
                                    @endif
                                    <div>{{ explode(' ', $label)[0] }}</div>
                                </button>
                            @endforeach
                        </div>
                        @error('module_style') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Logo embedding --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    <i class="fa-solid fa-image mr-2 text-violet-500"></i>
                    Logo (optional)
                </h3>

                <div class="space-y-4">
                    @if($logoPath)
                        <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="w-12 h-12 bg-white dark:bg-gray-600 rounded-lg flex items-center justify-center overflow-hidden">
                                <i class="fa-solid fa-image text-gray-400"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">Logo uploaded</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Will be centred in QR code</p>
                            </div>
                            <button
                                wire:click="removeLogo"
                                class="text-red-500 hover:text-red-600 p-2"
                                title="Remove logo"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>

                        {{-- Logo size slider --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Logo size: {{ $logoSize }}%
                            </label>
                            <input
                                type="range"
                                wire:model.live="logoSize"
                                min="10"
                                max="30"
                                step="1"
                                class="w-full accent-violet-500"
                            >
                            <div class="flex justify-between text-xs text-gray-400 mt-1">
                                <span>10%</span>
                                <span>30%</span>
                            </div>
                        </div>
                    @else
                        <div>
                            <label
                                for="logo-upload"
                                class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700/50 hover:bg-gray-100 dark:hover:bg-gray-700"
                            >
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <i class="fa-solid fa-cloud-arrow-up text-2xl text-gray-400 mb-2"></i>
                                    <p class="mb-1 text-sm text-gray-500 dark:text-gray-400">
                                        <span class="font-medium">Click to upload</span> a logo
                                    </p>
                                    <p class="text-xs text-gray-400">PNG, JPG, GIF up to 1MB</p>
                                </div>
                                <input
                                    id="logo-upload"
                                    type="file"
                                    wire:model="logoUpload"
                                    accept="image/*"
                                    class="hidden"
                                >
                            </label>
                            @error('logoUpload') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="flex items-start gap-2 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-sm">
                        <i class="fa-solid fa-lightbulb text-amber-500 mt-0.5"></i>
                        <div class="text-amber-800 dark:text-amber-200">
                            <strong>Tip:</strong> When using a logo, set error correction to "H" (High) to ensure the QR code remains scannable.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Preview and download panel --}}
        <div class="space-y-6">
            {{-- QR Preview --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    <i class="fa-solid fa-eye mr-2 text-violet-500"></i>
                    Preview
                </h3>

                <div
                    class="flex items-center justify-center p-8 rounded-lg"
                    style="background: repeating-conic-gradient(#e5e7eb 0% 25%, #f3f4f6 0% 50%) 50% / 20px 20px"
                >
                    @if($this->previewQrCode)
                        <img
                            src="{{ $this->previewQrCode }}"
                            alt="QR Code Preview"
                            class="max-w-full h-auto rounded shadow-lg"
                            style="max-height: 300px;"
                        >
                    @else
                        <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <i class="fa-solid fa-qrcode text-4xl mb-3 opacity-50"></i>
                            <p>Unable to generate preview</p>
                        </div>
                    @endif
                </div>

                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Encodes: <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">{{ $this->encodedUrl }}</code>
                    </p>
                </div>
            </div>

            {{-- Download options --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    <i class="fa-solid fa-download mr-2 text-violet-500"></i>
                    Download
                </h3>

                <div class="grid grid-cols-2 gap-4">
                    <a
                        href="{{ route('hub.bio.qr.download', ['id' => $this->biolinkId, 'format' => 'png']) }}"
                        class="flex flex-col items-center gap-2 p-4 rounded-lg border-2 border-gray-200 dark:border-gray-600 hover:border-violet-500 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors group"
                    >
                        <i class="fa-solid fa-file-image text-3xl text-gray-400 group-hover:text-violet-500"></i>
                        <span class="font-medium text-gray-700 dark:text-gray-300 group-hover:text-violet-600 dark:group-hover:text-violet-400">PNG</span>
                        <span class="text-xs text-gray-400">Raster image</span>
                    </a>

                    <a
                        href="{{ route('hub.bio.qr.download', ['id' => $this->biolinkId, 'format' => 'svg']) }}"
                        class="flex flex-col items-center gap-2 p-4 rounded-lg border-2 border-gray-200 dark:border-gray-600 hover:border-violet-500 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors group"
                    >
                        <i class="fa-solid fa-bezier-curve text-3xl text-gray-400 group-hover:text-violet-500"></i>
                        <span class="font-medium text-gray-700 dark:text-gray-300 group-hover:text-violet-600 dark:group-hover:text-violet-400">SVG</span>
                        <span class="text-xs text-gray-400">Vector (scalable)</span>
                    </a>
                </div>

                <p class="mt-4 text-xs text-gray-500 dark:text-gray-400 text-center">
                    Downloads use your saved settings. Click "Save settings" first to apply changes.
                </p>
            </div>

            {{-- Help text --}}
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 text-sm text-gray-600 dark:text-gray-400">
                <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-2">About QR codes</h4>
                <ul class="space-y-1.5">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-green-500 mt-0.5"></i>
                        <span>Ensure high contrast between foreground and background for best scanning</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-green-500 mt-0.5"></i>
                        <span>PNG format is best for web use, SVG for print materials</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-green-500 mt-0.5"></i>
                        <span>Test your QR code with multiple devices before printing</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
