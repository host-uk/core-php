<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('hub.bio.index') }}" wire:navigate class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Create File Link</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Upload a file and share it with a short link</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-2xl">
        <form wire:submit="create" class="space-y-6">
            {{-- File Upload Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">File</h2>

                <div class="space-y-4">
                    {{-- File Upload Zone --}}
                    <div
                        x-data="{ isDragging: false }"
                        x-on:dragover.prevent="isDragging = true"
                        x-on:dragleave="isDragging = false"
                        x-on:drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))"
                        class="relative"
                    >
                        <label
                            :class="isDragging ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20' : 'border-gray-300 dark:border-gray-600'"
                            class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed rounded-lg cursor-pointer hover:border-violet-500 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors"
                        >
                            @if($file)
                                <div class="text-center">
                                    <i class="fa-solid fa-file text-4xl text-violet-500 mb-2"></i>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $filename }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ number_format($file->getSize() / 1024 / 1024, 2) }} MB
                                    </p>
                                    <p class="text-xs text-violet-600 dark:text-violet-400 mt-2">Click or drag to replace</p>
                                </div>
                            @else
                                <div class="text-center">
                                    <i class="fa-solid fa-cloud-arrow-up text-4xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <span class="font-medium text-violet-600 dark:text-violet-400">Click to upload</span> or drag and drop
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        Max file size: {{ $this->maxFileSizeFormatted }}
                                    </p>
                                </div>
                            @endif
                            <input
                                x-ref="fileInput"
                                type="file"
                                wire:model="file"
                                class="hidden"
                            >
                        </label>
                    </div>

                    @error('file')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    {{-- Display filename (optional override) --}}
                    @if($file)
                        <div>
                            <label for="filename" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Display name
                            </label>
                            <input
                                type="text"
                                id="filename"
                                wire:model="filename"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                The filename shown to visitors when they download.
                            </p>
                        </div>
                    @endif

                    {{-- Allowed file types --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium">Allowed file types:</span> {{ $this->allowedExtensionsFormatted }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Short URL Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Short URL</h2>

                <div class="space-y-4">
                    <div>
                        <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Custom slug
                        </label>
                        <div class="flex rounded-md shadow-sm">
                            <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm">
                                {{ parse_url(config('bio.default_domain'), PHP_URL_HOST) }}/
                            </span>
                            <input
                                type="text"
                                id="url"
                                wire:model.live.debounce.300ms="url"
                                class="flex-1 block w-full rounded-none border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                placeholder="your-slug"
                            >
                            <button
                                type="button"
                                wire:click="regenerateSlug"
                                class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400"
                                title="Generate random slug"
                            >
                                <i class="fa-solid fa-shuffle"></i>
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Leave as generated or enter your own custom slug.
                        </p>
                        @error('url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- URL Preview --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Your file link will be:</p>
                        <div class="flex items-center gap-2">
                            <code class="text-sm text-violet-600 dark:text-violet-400 break-all">{{ $this->fullUrlPreview }}</code>
                            <button
                                type="button"
                                x-data="{ copied: false }"
                                x-on:click="
                                    navigator.clipboard.writeText('{{ $this->fullUrlPreview }}');
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                title="Copy to clipboard"
                            >
                                <i x-show="!copied" class="fa-solid fa-copy"></i>
                                <i x-show="copied" x-cloak class="fa-solid fa-check text-green-500"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Advanced Options --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
                <button
                    type="button"
                    wire:click="toggleAdvanced"
                    class="w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                    <span class="font-medium text-gray-900 dark:text-gray-100">Advanced options</span>
                    <i class="fa-solid {{ $showAdvanced ? 'fa-chevron-up' : 'fa-chevron-down' }} text-gray-400"></i>
                </button>

                @if($showAdvanced)
                    <div class="border-t border-gray-200 dark:border-gray-700 p-6 space-y-4">
                        {{-- Enable/Disable Toggle --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable link</label>
                                <p class="text-sm text-gray-500 dark:text-gray-400">When disabled, the link will return a 404 error.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="isEnabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"></div>
                            </label>
                        </div>

                        {{-- Password Protection --}}
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Password protection</label>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Require a password to download the file.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="passwordEnabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"></div>
                            </label>
                        </div>

                        @if($passwordEnabled)
                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Password
                                </label>
                                <input
                                    type="password"
                                    id="password"
                                    wire:model="password"
                                    placeholder="Enter password"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                >
                                @error('password')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        {{-- Schedule --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Start date (optional)
                                </label>
                                <input
                                    type="datetime-local"
                                    id="startDate"
                                    wire:model="startDate"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                >
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Link will only work after this date.</p>
                                @error('startDate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    End date (optional)
                                </label>
                                <input
                                    type="datetime-local"
                                    id="endDate"
                                    wire:model="endDate"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                >
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Link will stop working after this date.</p>
                                @error('endDate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3">
                <a
                    href="{{ route('hub.bio.index') }}"
                    wire:navigate
                    class="btn border-gray-300 dark:border-gray-600 hover:border-gray-400 text-gray-700 dark:text-gray-300"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="btn bg-violet-500 hover:bg-violet-600 text-white"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50 cursor-not-allowed"
                    @if(!$canCreate) disabled @endif
                >
                    <span wire:loading.remove wire:target="create">
                        <i class="fa-solid fa-file mr-2"></i>
                        Create File Link
                    </span>
                    <span wire:loading wire:target="create">
                        <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                        Uploading...
                    </span>
                </button>
            </div>

            {{-- Entitlement Error --}}
            @if(!$canCreate)
                <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                    <div class="flex">
                        <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5"></i>
                        <div class="ml-3">
                            <p class="text-sm text-amber-700 dark:text-amber-300">
                                {{ $entitlementError ?? 'You have reached your file link limit.' }}
                            </p>
                            <a href="{{ route('hub.billing.index') }}" wire:navigate class="text-sm font-medium text-amber-700 dark:text-amber-300 underline mt-1 inline-block">
                                Upgrade your plan
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>
