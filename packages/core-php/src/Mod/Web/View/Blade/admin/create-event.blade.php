<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <div class="flex items-center gap-3">
                <a href="{{ route('hub.bio.index') }}" wire:navigate class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Create Event Page</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Create an event page with calendar integration</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-3xl">
        <form wire:submit="create" class="space-y-6">
            {{-- Event Details Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Event details</h2>

                <div class="space-y-4">
                    <div>
                        <label for="eventName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Event name <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="eventName"
                            wire:model="eventName"
                            placeholder="Product Launch Party"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                        @error('eventName')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Description
                        </label>
                        <textarea
                            id="description"
                            wire:model="description"
                            rows="4"
                            placeholder="Tell people about your event..."
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        ></textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- Date and Time Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Date and time</h2>

                <div class="space-y-4">
                    {{-- All Day Toggle --}}
                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">All-day event</label>
                            <p class="text-sm text-gray-500 dark:text-gray-400">No specific start or end time.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.live="allDay" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"></div>
                        </label>
                    </div>

                    {{-- Start Date/Time --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="startDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Start date <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                id="startDate"
                                wire:model="startDate"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                            @error('startDate')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @unless($allDay)
                            <div>
                                <label for="startTime" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Start time <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="time"
                                    id="startTime"
                                    wire:model="startTime"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                                @error('startTime')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endunless
                    </div>

                    {{-- End Date/Time --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="endDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                End date <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                id="endDate"
                                wire:model="endDate"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                            @error('endDate')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @unless($allDay)
                            <div>
                                <label for="endTime" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    End time <span class="text-red-500">*</span>
                                </label>
                                <input
                                    type="time"
                                    id="endTime"
                                    wire:model="endTime"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                                @error('endTime')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endunless
                    </div>

                    {{-- Timezone --}}
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Timezone
                        </label>
                        <select
                            id="timezone"
                            wire:model="timezone"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                            @foreach($this->timezones as $region => $zones)
                                <optgroup label="{{ $region }}">
                                    @foreach($zones as $zone)
                                        <option value="{{ $zone }}" @selected($zone === $timezone)>
                                            {{ str_replace('_', ' ', $zone) }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Location Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Location</h2>

                <div class="space-y-4">
                    {{-- Location Type --}}
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="locationType" value="physical" class="text-violet-600 focus:ring-violet-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <i class="fa-solid fa-location-dot mr-1 text-gray-400"></i> In-person
                            </span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="locationType" value="online" class="text-violet-600 focus:ring-violet-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <i class="fa-solid fa-video mr-1 text-gray-400"></i> Online
                            </span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" wire:model.live="locationType" value="hybrid" class="text-violet-600 focus:ring-violet-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">
                                <i class="fa-solid fa-shuffle mr-1 text-gray-400"></i> Hybrid
                            </span>
                        </label>
                    </div>

                    {{-- Physical Location --}}
                    @if($locationType === 'physical' || $locationType === 'hybrid')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="locationName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Venue name
                                </label>
                                <input
                                    type="text"
                                    id="locationName"
                                    wire:model="locationName"
                                    placeholder="Conference Centre"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                            </div>
                            <div class="sm:col-span-2">
                                <label for="locationAddress" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Address
                                </label>
                                <input
                                    type="text"
                                    id="locationAddress"
                                    wire:model="locationAddress"
                                    placeholder="123 High Street, London, SW1A 1AA"
                                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                >
                            </div>
                        </div>
                    @endif

                    {{-- Online Location --}}
                    @if($locationType === 'online' || $locationType === 'hybrid')
                        <div>
                            <label for="onlineUrl" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Meeting link
                            </label>
                            <input
                                type="url"
                                id="onlineUrl"
                                wire:model="onlineUrl"
                                placeholder="https://zoom.us/j/123456789"
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                            >
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Zoom, Google Meet, Teams, or any video conferencing link.
                            </p>
                            @error('onlineUrl')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>

            {{-- Organiser Card --}}
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Organiser</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="organiserName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Name
                        </label>
                        <input
                            type="text"
                            id="organiserName"
                            wire:model="organiserName"
                            placeholder="Your name or organisation"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                    </div>
                    <div>
                        <label for="organiserEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Email
                        </label>
                        <input
                            type="email"
                            id="organiserEmail"
                            wire:model="organiserEmail"
                            placeholder="events@example.com"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                        >
                        @error('organiserEmail')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
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
                        @error('url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- URL Preview --}}
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-md p-4">
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Your event page will be:</p>
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
                                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable page</label>
                                <p class="text-sm text-gray-500 dark:text-gray-400">When disabled, the page will return a 404 error.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="isEnabled" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-violet-300 dark:peer-focus:ring-violet-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"></div>
                            </label>
                        </div>

                        {{-- Link Schedule (different from event date) --}}
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Page visibility schedule</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Control when the event page is publicly accessible (separate from the event date).</p>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="linkStartDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Page visible from
                                    </label>
                                    <input
                                        type="datetime-local"
                                        id="linkStartDate"
                                        wire:model="linkStartDate"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                    >
                                </div>
                                <div>
                                    <label for="linkEndDate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        Page visible until
                                    </label>
                                    <input
                                        type="datetime-local"
                                        id="linkEndDate"
                                        wire:model="linkEndDate"
                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500 sm:text-sm"
                                    >
                                </div>
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
                        <i class="fa-solid fa-calendar-plus mr-2"></i>
                        Create Event Page
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
                                {{ $entitlementError ?? 'You have reached your event page limit.' }}
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
