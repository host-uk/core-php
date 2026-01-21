<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('hub.bio.index') }}" wire:navigate class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Create static page</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Build custom HTML pages with full CSS and JavaScript support</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Entitlement warning --}}
    @if(!$canCreate)
        <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex items-start">
                <i class="fa-solid fa-exclamation-triangle text-red-600 dark:text-red-400 mt-0.5 mr-3"></i>
                <div>
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Limit reached</h3>
                    <p class="mt-1 text-sm text-red-700 dark:text-red-300">{{ $entitlementError }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="max-w-4xl">
        <form wire:submit="create" class="space-y-6">
            {{-- Basic Settings Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Basic settings</h2>

                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Page title <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="title"
                            wire:model="title"
                            placeholder="My custom page"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Custom URL slug <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="url"
                            wire:model.live="url"
                            placeholder="my-page"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Your page will be available at: <span class="font-mono text-violet-600 dark:text-violet-400">{{ $this->fullUrlPreview }}</span>
                        </p>
                        @error('url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- HTML Content Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">HTML content</h2>

                <div>
                    <label for="htmlContent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        HTML <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        id="htmlContent"
                        wire:model="htmlContent"
                        rows="12"
                        placeholder="<h1>Welcome to my page</h1>&#10;<p>This is custom HTML content.</p>"
                        class="w-full font-mono text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                    ></textarea>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Your HTML will be sanitised to prevent XSS attacks. Most common HTML tags are allowed.
                    </p>
                    @error('htmlContent')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- CSS Content Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Custom CSS (optional)</h2>
                    <button
                        type="button"
                        wire:click="toggleCssEditor"
                        class="text-sm text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300"
                    >
                        {{ $showCssEditor ? 'Hide editor' : 'Show editor' }}
                    </button>
                </div>

                @if($showCssEditor)
                    <div>
                        <label for="cssContent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            CSS
                        </label>
                        <textarea
                            id="cssContent"
                            wire:model="cssContent"
                            rows="8"
                            placeholder="h1 { color: #6366f1; }&#10;p { font-size: 16px; }"
                            class="w-full font-mono text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        ></textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Your CSS will be automatically scoped to prevent affecting the platform UI.
                        </p>
                        @error('cssContent')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            {{-- JavaScript Content Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Custom JavaScript (optional)</h2>
                    <button
                        type="button"
                        wire:click="toggleJsEditor"
                        class="text-sm text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300"
                    >
                        {{ $showJsEditor ? 'Hide editor' : 'Show editor' }}
                    </button>
                </div>

                @if($showJsEditor)
                    <div>
                        <label for="jsContent" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            JavaScript
                        </label>
                        <textarea
                            id="jsContent"
                            wire:model="jsContent"
                            rows="8"
                            placeholder="console.log('Hello from my static page');"
                            class="w-full font-mono text-sm rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        ></textarea>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            JavaScript will be sanitised to remove dangerous functions like eval().
                        </p>
                        @error('jsContent')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>

            {{-- Advanced Options Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Advanced options</h2>
                    <button
                        type="button"
                        wire:click="toggleAdvanced"
                        class="text-sm text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300"
                    >
                        {{ $showAdvanced ? 'Hide options' : 'Show options' }}
                    </button>
                </div>

                @if($showAdvanced)
                    <div class="space-y-4">
                        <div>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    wire:model="isEnabled"
                                    class="rounded border-gray-300 dark:border-gray-600 text-violet-600 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Page is enabled</span>
                            </label>
                            <p class="ml-6 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Disabled pages are not accessible to visitors.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Start date
                                </label>
                                <input
                                    type="datetime-local"
                                    id="startDate"
                                    wire:model="startDate"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Optional scheduling</p>
                                @error('startDate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    End date
                                </label>
                                <input
                                    type="datetime-local"
                                    id="endDate"
                                    wire:model="endDate"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                                @error('endDate')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Submit Buttons --}}
            <div class="flex items-center justify-end gap-3">
                <a
                    href="{{ route('hub.bio.index') }}"
                    wire:navigate
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-violet-600 border border-transparent rounded-md hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    @if(!$canCreate) disabled @endif
                >
                    Create static page
                </button>
            </div>
        </form>
    </div>
</div>
