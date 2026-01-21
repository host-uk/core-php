<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">PWA Configuration</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                Turn your biolink into an installable app for your fans
            </p>
        </div>
        <a href="{{ route('bio.edit', $biolinkId) }}" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
            ← Back to biolink
        </a>
    </div>

    {{-- Success/Error Messages --}}
    @if($successMessage)
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <p class="text-green-800 dark:text-green-200 text-sm">{{ $successMessage }}</p>
        </div>
    @endif

    @if($errorMessage)
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <p class="text-red-800 dark:text-red-200 text-sm">{{ $errorMessage }}</p>
        </div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Basic Information --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Basic Information</h2>

            <div>
                <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    App Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    wire:model="name"
                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="My Amazing Page"
                    maxlength="128"
                >
                @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="shortName" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Short Name (for home screen)
                </label>
                <input
                    type="text"
                    id="shortName"
                    wire:model="shortName"
                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="My Page"
                    maxlength="32"
                >
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Displayed under the app icon (keep it short)</p>
                @error('shortName') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Description
                </label>
                <textarea
                    id="description"
                    wire:model="description"
                    rows="3"
                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                    placeholder="A brief description of your app"
                    maxlength="256"
                ></textarea>
                @error('description') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Appearance --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Appearance</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="themeColor" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Theme Colour <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input
                            type="color"
                            id="themeColor"
                            wire:model.live="themeColor"
                            class="h-10 w-16 rounded border-zinc-300 dark:border-zinc-600"
                        >
                        <input
                            type="text"
                            wire:model="themeColor"
                            class="flex-1 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="#6366f1"
                        >
                    </div>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Toolbar and address bar colour</p>
                    @error('themeColor') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="backgroundColor" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Background Colour <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input
                            type="color"
                            id="backgroundColor"
                            wire:model.live="backgroundColor"
                            class="h-10 w-16 rounded border-zinc-300 dark:border-zinc-600"
                        >
                        <input
                            type="text"
                            wire:model="backgroundColor"
                            class="flex-1 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                            placeholder="#ffffff"
                        >
                    </div>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Splash screen background</p>
                    @error('backgroundColor') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="display" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Display Mode
                    </label>
                    <select
                        id="display"
                        wire:model="display"
                        class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                    >
                        @foreach($this->displayModes as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('display') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="orientation" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        Orientation
                    </label>
                    <select
                        id="orientation"
                        wire:model="orientation"
                        class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                    >
                        @foreach($this->orientations as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('orientation') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Icons --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Icons</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Upload icons for the home screen. Recommended: 512x512px PNG.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Main Icon (512x512)
                    </label>
                    <input
                        type="file"
                        wire:model="iconUpload"
                        accept="image/*"
                        class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-lg file:border-0
                            file:text-sm file:font-medium
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100
                            dark:file:bg-blue-900/20 dark:file:text-blue-400"
                    >
                    @error('iconUpload') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">
                        Maskable Icon (512x512, optional)
                    </label>
                    <input
                        type="file"
                        wire:model="iconMaskableUpload"
                        accept="image/*"
                        class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-lg file:border-0
                            file:text-sm file:font-medium
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100
                            dark:file:bg-blue-900/20 dark:file:text-blue-400"
                    >
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">For Android adaptive icons</p>
                    @error('iconMaskableUpload') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Screenshots --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Screenshots</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Add screenshots for the install prompt (up to 6).</p>

            @if(count($screenshots) > 0)
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach($screenshots as $index => $screenshot)
                        <div class="relative group">
                            <img src="{{ $screenshot['url'] }}" alt="Screenshot {{ $index + 1 }}" class="w-full h-40 object-cover rounded-lg">
                            <button
                                type="button"
                                wire:click="removeScreenshot({{ $index }})"
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full p-1 opacity-0 group-hover:opacity-100 transition"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($screenshots) < 6)
                <div>
                    <input
                        type="file"
                        wire:model="newScreenshot"
                        accept="image/*"
                        class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-lg file:border-0
                            file:text-sm file:font-medium
                            file:bg-blue-50 file:text-blue-700
                            hover:file:bg-blue-100
                            dark:file:bg-blue-900/20 dark:file:text-blue-400"
                    >
                    @error('newScreenshot') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    <button
                        type="button"
                        wire:click="addScreenshot"
                        class="mt-2 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm"
                        @if(!$newScreenshot) disabled @endif
                    >
                        Add Screenshot
                    </button>
                </div>
            @endif
        </div>

        {{-- Shortcuts --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Shortcuts</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Quick actions from the home screen (up to 4).</p>

            @if(count($shortcuts) > 0)
                <div class="space-y-2">
                    @foreach($shortcuts as $index => $shortcut)
                        <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $shortcut['name'] }}</p>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $shortcut['url'] }}</p>
                                @if(!empty($shortcut['description']))
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500">{{ $shortcut['description'] }}</p>
                                @endif
                            </div>
                            <button
                                type="button"
                                wire:click="removeShortcut({{ $index }})"
                                class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                            >
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            @if(count($shortcuts) < 4)
                @if($addingShortcut)
                    <div class="border border-zinc-300 dark:border-zinc-600 rounded-lg p-4 space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Name</label>
                            <input
                                type="text"
                                wire:model="newShortcutName"
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Shop"
                            >
                            @error('newShortcutName') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">URL</label>
                            <input
                                type="url"
                                wire:model="newShortcutUrl"
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="https://shop.example.com"
                            >
                            @error('newShortcutUrl') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Description (optional)</label>
                            <input
                                type="text"
                                wire:model="newShortcutDescription"
                                class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Visit my shop"
                            >
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                wire:click="addShortcut"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm"
                            >
                                Add
                            </button>
                            <button
                                type="button"
                                wire:click="cancelAddShortcut"
                                class="px-4 py-2 bg-zinc-200 text-zinc-700 rounded-lg hover:bg-zinc-300 text-sm dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                @else
                    <button
                        type="button"
                        wire:click="showAddShortcut"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm"
                    >
                        + Add Shortcut
                    </button>
                @endif
            @endif
        </div>

        {{-- Install Prompt --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 space-y-4">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Install Prompt</h2>

            <div>
                <label for="installPromptDelay" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    Prompt Delay (seconds)
                </label>
                <input
                    type="number"
                    id="installPromptDelay"
                    wire:model="installPromptDelay"
                    min="0"
                    max="300"
                    class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                >
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">How long to wait before showing the install prompt</p>
                @error('installPromptDelay') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-between">
            <div>
                @if($pwaId && $isEnabled)
                    <button
                        type="button"
                        wire:click="disable"
                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600"
                    >
                        Disable PWA
                    </button>
                @elseif($pwaId && !$isEnabled)
                    <button
                        type="button"
                        wire:click="enable"
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600"
                    >
                        Enable PWA
                    </button>
                @endif
            </div>

            <div class="flex gap-3">
                <a
                    href="{{ route('bio.edit', $biolinkId) }}"
                    class="px-4 py-2 bg-zinc-200 text-zinc-700 rounded-lg hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600"
                >
                    Save Configuration
                </button>
            </div>
        </div>
    </form>
</div>
