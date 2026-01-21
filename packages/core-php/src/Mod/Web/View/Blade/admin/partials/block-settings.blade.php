{{-- Block-specific settings forms - Using Flux UI components --}}
@switch($editingBlockType)
    @case('link')
        <flux:input
            wire:model="editingBlockSettings.name"
            label="Button Text"
            placeholder="My Link"
        />
        <flux:input
            type="url"
            wire:model="editingBlockUrl"
            label="URL"
            placeholder="https://example.com"
        />
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Background</flux:label>
                <flux:input type="color" wire:model="editingBlockSettings.background_color" />
            </flux:field>
            <flux:field>
                <flux:label>Text Colour</flux:label>
                <flux:input type="color" wire:model="editingBlockSettings.text_color" />
            </flux:field>
        </div>
        <flux:select wire:model="editingBlockSettings.border_radius" label="Border Radius">
            <flux:select.option value="none">Square</flux:select.option>
            <flux:select.option value="small">Small</flux:select.option>
            <flux:select.option value="rounded">Rounded</flux:select.option>
            <flux:select.option value="round">Pill</flux:select.option>
        </flux:select>
        <flux:field>
            <flux:checkbox wire:model="editingBlockSettings.open_in_new_tab" />
            <flux:label>Open in new tab</flux:label>
        </flux:field>
        @break

    @case('header')
        <flux:input
            wire:model="editingBlockSettings.name"
            label="Name"
            placeholder="Your Name"
        />
        <flux:textarea
            wire:model="editingBlockSettings.bio"
            label="Bio/Description"
            rows="2"
            placeholder="A short bio..."
        />
        <flux:input
            type="url"
            wire:model="editingBlockSettings.avatar"
            label="Avatar URL"
            placeholder="https://..."
        />
        <flux:select wire:model="editingBlockSettings.avatar_shape" label="Avatar Shape">
            <flux:select.option value="round">Circle</flux:select.option>
            <flux:select.option value="rounded">Rounded</flux:select.option>
            <flux:select.option value="square">Square</flux:select.option>
        </flux:select>
        @break

    @case('heading')
        <flux:input
            wire:model="editingBlockSettings.text"
            label="Text"
        />
        <flux:select wire:model="editingBlockSettings.size" label="Size">
            <flux:select.option value="h1">Large (H1)</flux:select.option>
            <flux:select.option value="h2">Medium (H2)</flux:select.option>
            <flux:select.option value="h3">Small (H3)</flux:select.option>
        </flux:select>
        <flux:field>
            <flux:label>Alignment</flux:label>
            <flux:button.group>
                @foreach(['left' => 'fa-align-left', 'center' => 'fa-align-center', 'right' => 'fa-align-right'] as $align => $icon)
                    <flux:button
                        type="button"
                        wire:click="$set('editingBlockSettings.alignment', '{{ $align }}')"
                        :variant="($editingBlockSettings['alignment'] ?? 'center') === $align ? 'primary' : 'outline'"
                        size="sm"
                    >
                        <i class="fa-solid {{ $icon }}"></i>
                    </flux:button>
                @endforeach
            </flux:button.group>
        </flux:field>
        <flux:field>
            <flux:label>Text Colour</flux:label>
            <flux:input type="color" wire:model="editingBlockSettings.color" />
        </flux:field>
        @break

    @case('text')
    @case('paragraph')
        <flux:textarea
            wire:model="editingBlockSettings.text"
            label="Text"
            rows="4"
        />
        <flux:field>
            <flux:label>Alignment</flux:label>
            <flux:button.group>
                @foreach(['left' => 'fa-align-left', 'center' => 'fa-align-center', 'right' => 'fa-align-right'] as $align => $icon)
                    <flux:button
                        type="button"
                        wire:click="$set('editingBlockSettings.alignment', '{{ $align }}')"
                        :variant="($editingBlockSettings['alignment'] ?? 'center') === $align ? 'primary' : 'outline'"
                        size="sm"
                    >
                        <i class="fa-solid {{ $icon }}"></i>
                    </flux:button>
                @endforeach
            </flux:button.group>
        </flux:field>
        <flux:field>
            <flux:label>Text Colour</flux:label>
            <flux:input type="color" wire:model="editingBlockSettings.color" />
        </flux:field>
        @break

    @case('avatar')
        <flux:input
            type="url"
            wire:model="editingBlockSettings.image"
            label="Image URL"
            placeholder="https://..."
        />
        <flux:input
            wire:model="editingBlockSettings.image_alt"
            label="Alt Text"
            placeholder="Profile photo"
        />
        <flux:input
            type="number"
            wire:model="editingBlockSettings.size"
            label="Size (px)"
            min="50"
            max="300"
        />
        <flux:select wire:model="editingBlockSettings.border_radius" label="Shape">
            <flux:select.option value="round">Circle</flux:select.option>
            <flux:select.option value="rounded">Rounded</flux:select.option>
            <flux:select.option value="square">Square</flux:select.option>
        </flux:select>
        @break

    @case('image')
        <flux:input
            type="url"
            wire:model="editingBlockSettings.image"
            label="Image URL"
            placeholder="https://..."
        />
        <flux:input
            wire:model="editingBlockSettings.alt"
            label="Alt Text"
        />
        <flux:input
            type="url"
            wire:model="editingBlockUrl"
            label="Link URL (optional)"
            placeholder="https://..."
        />
        @break

    @case('divider')
        <flux:select wire:model="editingBlockSettings.style" label="Style">
            <flux:select.option value="solid">Solid</flux:select.option>
            <flux:select.option value="dashed">Dashed</flux:select.option>
            <flux:select.option value="dotted">Dotted</flux:select.option>
        </flux:select>
        <flux:field>
            <flux:label>Colour</flux:label>
            <flux:input type="color" wire:model="editingBlockSettings.color" />
        </flux:field>
        <flux:input
            type="number"
            wire:model="editingBlockSettings.margin"
            label="Margin (px)"
            min="0"
            max="100"
        />
        @break

    @case('youtube')
        <flux:input
            wire:model="editingBlockSettings.video_id"
            label="YouTube Video ID or URL"
            placeholder="dQw4w9WgXcQ or https://youtube.com/watch?v=..."
            description="Enter the video ID or full YouTube URL"
        />
        @break

    @case('spotify')
        <flux:input
            wire:model="editingBlockSettings.uri"
            label="Spotify URI or URL"
            placeholder="spotify:track:... or https://open.spotify.com/..."
            description="Supports tracks, albums, playlists, and artists"
        />
        @break

    @case('socials')
        @php
            $allPlatforms = config('webpage.social_platforms', []);
            $currentPlatforms = $editingBlockSettings['platforms'] ?? [];
            $activePlatformKeys = array_keys(array_filter($currentPlatforms, fn($v) => $v !== null));
            $availablePlatforms = array_diff_key($allPlatforms, array_flip($activePlatformKeys));
        @endphp

        <flux:field>
            <flux:label>Add Platform</flux:label>
            <div class="flex gap-2">
                <div class="flex-1">
                    <flux:select
                        wire:model="socialPlatformToAdd"
                        variant="listbox"
                        searchable
                        placeholder="Search platforms..."
                        size="sm"
                    >
                        @foreach($availablePlatforms as $key => $platform)
                            <flux:select.option value="{{ $key }}">{{ $platform['name'] }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
                <flux:button
                    type="button"
                    wire:click="addSocialPlatform"
                    variant="primary"
                    size="sm"
                    icon="plus"
                />
            </div>
        </flux:field>

        {{-- Active platforms with URL inputs --}}
        <div class="space-y-2">
            @forelse($activePlatformKeys as $key)
                @php $platform = $allPlatforms[$key] ?? null; @endphp
                @if($platform)
                    <div class="flex items-center gap-2 group">
                        <div
                            class="w-8 h-8 rounded-md flex items-center justify-center shrink-0"
                            style="background-color: {{ $platform['color'] }}20"
                        >
                            <i class="{{ $platform['icon'] }}" style="color: {{ $platform['color'] }}"></i>
                        </div>
                        <flux:input
                            type="url"
                            wire:model="editingBlockSettings.platforms.{{ $key }}"
                            placeholder="{{ $platform['url'] ?? $platform['name'] . ' URL' }}"
                            size="sm"
                            class="flex-1"
                        />
                        <flux:button
                            type="button"
                            wire:click="removeSocialPlatform('{{ $key }}')"
                            variant="ghost"
                            size="sm"
                            class="opacity-0 group-hover:opacity-100 text-red-500 hover:text-red-600"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </flux:button>
                    </div>
                @endif
            @empty
                <flux:text class="text-zinc-400 italic py-2">No platforms added yet</flux:text>
            @endforelse
        </div>

        <div class="grid grid-cols-2 gap-3 mt-4">
            <flux:select wire:model="editingBlockSettings.style" label="Style" size="sm">
                <flux:select.option value="colored">Coloured</flux:select.option>
                <flux:select.option value="light">Light</flux:select.option>
                <flux:select.option value="dark">Dark</flux:select.option>
            </flux:select>
            <flux:select wire:model="editingBlockSettings.size" label="Size" size="sm">
                <flux:select.option value="small">Small</flux:select.option>
                <flux:select.option value="medium">Medium</flux:select.option>
                <flux:select.option value="large">Large</flux:select.option>
            </flux:select>
        </div>
        @break

    @case('email_collector')
        <flux:input
            wire:model="editingBlockSettings.heading"
            label="Heading"
            placeholder="Join my newsletter"
        />
        <flux:input
            wire:model="editingBlockSettings.button_text"
            label="Button Text"
            placeholder="Subscribe"
        />
        <flux:input
            wire:model="editingBlockSettings.success_message"
            label="Success Message"
            placeholder="Thanks for subscribing!"
        />
        @break

    @default
        <div class="text-center py-4">
            <flux:text class="text-zinc-500">No settings available for this block type.</flux:text>
            <flux:text size="sm" class="text-zinc-400 mt-1">Block type: {{ $editingBlockType }}</flux:text>
        </div>
@endswitch
