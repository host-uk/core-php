<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('hub.bio.index') }}" wire:navigate class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Create Short Link</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create a shortened URL that redirects to any destination</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-2xl">
        <form wire:submit="create" class="space-y-6">
            {{-- Destination URL Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Destination</h2>

                <core:field>
                    <core:label badge="Required">Destination URL</core:label>
                    <core:input
                        type="url"
                        wire:model="destinationUrl"
                        placeholder="https://example.com/your-long-url"
                    />
                    <core:description>The URL visitors will be redirected to when they click your short link.</core:description>
                    <core:error name="destinationUrl" />
                </core:field>
            </div>

            {{-- Short URL Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Short URL</h2>

                <div class="space-y-4">
                    <core:field>
                        <core:label>Custom slug</core:label>
                        <core:input.group>
                            <core:input.group.prefix>{{ parse_url(config('bio.default_domain'), PHP_URL_HOST) }}/</core:input.group.prefix>
                            <core:input wire:model.live.debounce.300ms="url" placeholder="your-slug" />
                            <core:button type="button" wire:click="regenerateSlug" icon="arrow-path" variant="ghost" title="Generate random slug" />
                        </core:input.group>
                        <core:description>Leave as generated or enter your own custom slug.</core:description>
                        <core:error name="url" />
                    </core:field>

                    {{-- URL Preview --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Your short link will be:</p>
                        <div class="flex items-center gap-2">
                            <code class="text-sm text-violet-600 dark:text-violet-400 break-all">{{ $this->fullUrlPreview }}</code>
                            <core:button
                                type="button"
                                variant="ghost"
                                size="sm"
                                icon="clipboard"
                                x-data="{ copied: false }"
                                x-on:click="
                                    navigator.clipboard.writeText('{{ $this->fullUrlPreview }}');
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                ::icon="copied ? 'check' : 'clipboard'"
                                ::class="copied && 'text-green-500'"
                            />
                        </div>
                        @if($this->vanityUrlPreview)
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Also available at: <code class="text-violet-600 dark:text-violet-400">{{ $this->vanityUrlPreview }}</code>
                            </p>
                        @endif
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
                        <core:switch
                            wire:model="isEnabled"
                            label="Enable link"
                            description="When disabled, the link will return a 404 error."
                        />

                        {{-- Schedule --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <core:field>
                                <core:label>Start date (optional)</core:label>
                                <core:input type="datetime-local" wire:model="startDate" />
                                <core:description>Link will only work after this date.</core:description>
                                <core:error name="startDate" />
                            </core:field>
                            <core:field>
                                <core:label>End date (optional)</core:label>
                                <core:input type="datetime-local" wire:model="endDate" />
                                <core:description>Link will stop working after this date.</core:description>
                                <core:error name="endDate" />
                            </core:field>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Submit --}}
            <div class="flex items-center justify-end gap-3">
                <core:button href="{{ route('hub.bio.index') }}" wire:navigate variant="ghost">
                    Cancel
                </core:button>
                <core:button type="submit" variant="primary" icon="link">
                    Create Short Link
                </core:button>
            </div>
        </form>
    </div>
</div>
