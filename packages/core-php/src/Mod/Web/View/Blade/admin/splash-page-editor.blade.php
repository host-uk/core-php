<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900">Splash page settings</h2>
        <core:switch wire:model.live="enabled" label="Enable splash page" />
    </div>

    @if($enabled)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Settings Panel --}}
        <div class="space-y-4">
            <core:input
                wire:model="title"
                label="Page title"
                placeholder="Welcome!"
                required
            />

            <core:textarea
                wire:model="description"
                label="Description"
                placeholder="You're being redirected..."
                rows="3"
            />

            <core:input
                wire:model="buttonText"
                label="Button text"
                placeholder="Continue"
                required
            />

            <div class="grid grid-cols-2 gap-4">
                <core:input
                    wire:model="backgroundColor"
                    type="color"
                    label="Background colour"
                />

                <core:input
                    wire:model="textColor"
                    type="color"
                    label="Text colour"
                />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <core:input
                    wire:model="buttonColor"
                    type="color"
                    label="Button colour"
                />

                <core:input
                    wire:model="buttonTextColor"
                    type="color"
                    label="Button text colour"
                />
            </div>

            <div>
                <core:label>Logo (optional)</core:label>
                @if($logoUrl)
                    <div class="mt-2 flex items-center gap-3">
                        <img src="{{ $logoUrl }}" alt="Logo" class="h-12 w-auto rounded">
                        <core:button wire:click="removeLogo" variant="danger" size="sm">Remove</core:button>
                    </div>
                @else
                    <core:input
                        wire:model="logoFile"
                        type="file"
                        accept="image/*"
                        class="mt-2"
                    />
                @endif
            </div>

            <core:input
                wire:model.number="autoRedirectDelay"
                type="number"
                label="Auto-redirect delay (seconds)"
                min="0"
                max="30"
                helper="Set to 0 to disable auto-redirect"
            />

            <core:switch
                wire:model.live="showTimer"
                label="Show countdown timer"
            />

            <div class="flex gap-3 pt-4">
                <core:button wire:click="save" variant="primary">Save settings</core:button>
            </div>
        </div>

        {{-- Preview Panel --}}
        <div class="lg:sticky lg:top-6 lg:self-start">
            <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
                    <p class="text-sm font-medium text-gray-700">Preview</p>
                </div>
                <div
                    class="aspect-[9/16] flex flex-col items-center justify-center p-8 text-center"
                    style="background-color: {{ $backgroundColor }}; color: {{ $textColor }};"
                >
                    @if($logoUrl)
                        <img src="{{ $logoUrl }}" alt="Logo" class="h-16 w-auto mb-6">
                    @endif

                    <h1 class="text-2xl font-bold mb-3">
                        {{ $title ?: 'Your Title Here' }}
                    </h1>

                    @if($description)
                        <p class="text-base mb-6 opacity-80">
                            {{ $description }}
                        </p>
                    @endif

                    <button
                        type="button"
                        class="px-6 py-3 rounded-lg font-medium"
                        style="background-color: {{ $buttonColor }}; color: {{ $buttonTextColor }};"
                    >
                        {{ $buttonText ?: 'Continue' }}
                    </button>

                    @if($autoRedirectDelay > 0 && $showTimer)
                        <p class="text-sm mt-6 opacity-60">
                            Redirecting in {{ $autoRedirectDelay }} seconds...
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @else
    <core:card>
        <p class="text-sm text-gray-600">
            Enable splash pages to show a branded interstitial before redirecting users.
            This is useful for affiliate links, sponsored content, or adding context before redirects.
        </p>
    </core:card>
    @endif
</div>
