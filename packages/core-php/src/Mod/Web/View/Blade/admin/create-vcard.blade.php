<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('hub.bio.index') }}" wire:navigate class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Create vCard</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create a digital business card that visitors can download</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-3xl">
        <form wire:submit="create" class="space-y-6">
            {{-- Contact Information Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Contact information</h2>

                <div class="space-y-4">
                    {{-- Photo Upload --}}
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            @if($photoPreview)
                                <div class="relative">
                                    <img src="{{ $photoPreview }}" class="w-20 h-20 rounded-full object-cover">
                                    <button
                                        type="button"
                                        wire:click="removePhoto"
                                        class="absolute -top-1 -right-1 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600"
                                    >
                                        <i class="fa-solid fa-times text-xs"></i>
                                    </button>
                                </div>
                            @else
                                <label class="w-20 h-20 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                    <i class="fa-solid fa-camera text-gray-400 text-xl"></i>
                                    <input type="file" wire:model="photo" accept="image/*" class="hidden">
                                </label>
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Profile photo</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Optional. Will be embedded in the vCard file. Max 2MB.
                            </p>
                            @error('photo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Name --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                First name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="firstName"
                                wire:model="firstName"
                                placeholder="John"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                            @error('firstName')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Last name <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="lastName"
                                wire:model="lastName"
                                placeholder="Doe"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                            @error('lastName')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Email and Phone --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Email
                            </label>
                            <input
                                type="email"
                                id="email"
                                wire:model="email"
                                placeholder="john@example.com"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Phone (mobile)
                            </label>
                            <input
                                type="tel"
                                id="phone"
                                wire:model="phone"
                                placeholder="+44 7123 456789"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Company and Job Title --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Company
                            </label>
                            <input
                                type="text"
                                id="company"
                                wire:model="company"
                                placeholder="Acme Ltd"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                        </div>
                        <div>
                            <label for="jobTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Job title
                            </label>
                            <input
                                type="text"
                                id="jobTitle"
                                wire:model="jobTitle"
                                placeholder="Marketing Director"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                        </div>
                    </div>

                    {{-- Work Phone and Mod --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="phoneWork" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Phone (work)
                            </label>
                            <input
                                type="tel"
                                id="phoneWork"
                                wire:model="phoneWork"
                                placeholder="+44 20 1234 5678"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                        </div>
                        <div>
                            <label for="website" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Website
                            </label>
                            <input
                                type="url"
                                id="website"
                                wire:model="website"
                                placeholder="https://example.com"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                            @error('website')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Address Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Address</h2>

                <div class="space-y-4">
                    <div>
                        <label for="addressStreet" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Street address
                        </label>
                        <input
                            type="text"
                            id="addressStreet"
                            wire:model="addressStreet"
                            placeholder="123 High Street"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>

                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label for="addressCity" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                City
                            </label>
                            <input
                                type="text"
                                id="addressCity"
                                wire:model="addressCity"
                                placeholder="London"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                        </div>
                        <div>
                            <label for="addressRegion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                County/Region
                            </label>
                            <input
                                type="text"
                                id="addressRegion"
                                wire:model="addressRegion"
                                placeholder="Greater London"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                        </div>
                        <div>
                            <label for="addressPostcode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Postcode
                            </label>
                            <input
                                type="text"
                                id="addressPostcode"
                                wire:model="addressPostcode"
                                placeholder="SW1A 1AA"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                        </div>
                        <div>
                            <label for="addressCountry" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Country
                            </label>
                            <input
                                type="text"
                                id="addressCountry"
                                wire:model="addressCountry"
                                placeholder="United Kingdom"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                        </div>
                    </div>
                </div>
            </div>

            {{-- Social Links Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Social links</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="linkedin" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fa-brands fa-linkedin text-blue-600 mr-1"></i> LinkedIn
                        </label>
                        <input
                            type="url"
                            id="linkedin"
                            wire:model="linkedin"
                            placeholder="https://linkedin.com/in/username"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>
                    <div>
                        <label for="twitter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fa-brands fa-x-twitter mr-1"></i> X (Twitter)
                        </label>
                        <input
                            type="text"
                            id="twitter"
                            wire:model="twitter"
                            placeholder="@username"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>
                    <div>
                        <label for="facebook" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fa-brands fa-facebook text-blue-500 mr-1"></i> Facebook
                        </label>
                        <input
                            type="url"
                            id="facebook"
                            wire:model="facebook"
                            placeholder="https://facebook.com/username"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>
                    <div>
                        <label for="instagram" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <i class="fa-brands fa-instagram text-pink-500 mr-1"></i> Instagram
                        </label>
                        <input
                            type="text"
                            id="instagram"
                            wire:model="instagram"
                            placeholder="@username"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>
                </div>
            </div>

            {{-- Notes Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Notes</h2>

                <div>
                    <textarea
                        id="notes"
                        wire:model="notes"
                        rows="3"
                        placeholder="Additional notes (will be included in the vCard)"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                    ></textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
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
                        @error('url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- URL Preview --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Your vCard link will be:</p>
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
                        <i class="fa-solid fa-address-card mr-2"></i>
                        Create vCard
                    </span>
                    <span wire:loading wire:target="create">
                        <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                        Creating...
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
                                {{ $entitlementError ?? 'You have reached your vCard limit.' }}
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
