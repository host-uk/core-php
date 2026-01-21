<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Notification Handlers</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Get notified when events occur on
                @if($this->biolink)
                    <span class="font-medium">/{{ $this->biolink->url }}</span>
                @else
                    your biolink
                @endif
            </p>
        </div>
        <div>
            <button
                wire:click="openCreateModal"
                class="btn bg-violet-500 hover:bg-violet-600 text-white"
            >
                <i class="fa-solid fa-plus mr-2"></i>
                Add Handler
            </button>
        </div>
    </div>

    {{-- Handlers list --}}
    @if($this->handlers->count())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Handler</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Events</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Triggers</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->handlers as $handler)
                            <tr wire:key="handler-{{ $handler->id }}" @class(['bg-red-50 dark:bg-red-900/10' => $handler->isAutoDisabled()])>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                                            <i class="{{ $handler->getIconClass() }} text-violet-500"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $handler->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                Created {{ $handler->created_at->diffForHumans() }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <flux:badge color="zinc">{{ $handler->getTypeLabel() }}</flux:badge>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($handler->events ?? [] as $event)
                                            <flux:badge color="blue" size="sm">{{ $this->eventTypes[$event] ?? $event }}</flux:badge>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($handler->isAutoDisabled())
                                        <flux:badge color="red" icon="exclamation-triangle">Auto-disabled</flux:badge>
                                    @elseif($handler->is_enabled)
                                        <flux:badge color="green" icon="check-circle">Active</flux:badge>
                                    @else
                                        <flux:badge color="zinc" icon="pause-circle">Disabled</flux:badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    <div>{{ number_format($handler->trigger_count) }} total</div>
                                    @if($handler->last_triggered_at)
                                        <div class="text-xs">Last: {{ $handler->last_triggered_at->diffForHumans() }}</div>
                                    @endif
                                    @if($handler->consecutive_failures > 0)
                                        <div class="text-xs text-red-500">{{ $handler->consecutive_failures }} failure(s)</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Test button --}}
                                        <button
                                            wire:click="sendTest({{ $handler->id }})"
                                            wire:loading.attr="disabled"
                                            class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 disabled:opacity-50"
                                            title="Send test notification"
                                        >
                                            <i class="fa-solid fa-paper-plane"></i>
                                        </button>

                                        {{-- Edit button --}}
                                        <button
                                            wire:click="openEditModal({{ $handler->id }})"
                                            class="text-gray-600 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                            title="Edit handler"
                                        >
                                            <i class="fa-solid fa-edit"></i>
                                        </button>

                                        {{-- Reset button (for auto-disabled) --}}
                                        @if($handler->isAutoDisabled())
                                            <button
                                                wire:click="resetHandler({{ $handler->id }})"
                                                class="text-yellow-600 hover:text-yellow-700 dark:text-yellow-400 dark:hover:text-yellow-300"
                                                title="Reset and re-enable"
                                            >
                                                <i class="fa-solid fa-rotate"></i>
                                            </button>
                                        @else
                                            {{-- Toggle button --}}
                                            <button
                                                wire:click="toggleEnabled({{ $handler->id }})"
                                                class="{{ $handler->is_enabled ? 'text-green-600 hover:text-green-700 dark:text-green-400' : 'text-gray-400 hover:text-gray-500' }}"
                                                title="{{ $handler->is_enabled ? 'Disable' : 'Enable' }}"
                                            >
                                                <i class="fa-solid {{ $handler->is_enabled ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                            </button>
                                        @endif

                                        {{-- Delete button --}}
                                        <button
                                            wire:click="deleteHandler({{ $handler->id }})"
                                            wire:confirm="Are you sure you want to delete this notification handler?"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                            title="Delete handler"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-12 px-4">
            <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                <flux:icon name="bell" class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="text-center">No notification handlers</flux:heading>
            <flux:subheading class="text-center mt-1 max-w-sm">
                Set up webhooks, email alerts, or chat notifications to get notified when visitors interact with your bio.
            </flux:subheading>
            <flux:button wire:click="openCreateModal" icon="plus" variant="primary" class="mt-4">
                Add your first handler
            </flux:button>
        </div>
    @endif

    {{-- Create/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <form wire:submit="save">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-violet-100 dark:bg-violet-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fa-solid fa-bell text-violet-600 dark:text-violet-400"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        {{ $isEditing ? 'Edit Handler' : 'Add Notification Handler' }}
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        {{-- Name --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                                            <input
                                                type="text"
                                                wire:model="name"
                                                placeholder="e.g. My Webhook, Team Slack"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            >
                                            @error('name')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- Type --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                                            <select
                                                wire:model.live="type"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            >
                                                @foreach($this->handlerTypes as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- Events --}}
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Trigger on</label>
                                            <div class="space-y-2">
                                                @foreach($this->eventTypes as $value => $label)
                                                    <label class="flex items-center">
                                                        <input
                                                            type="checkbox"
                                                            wire:model="events"
                                                            value="{{ $value }}"
                                                            class="rounded border-gray-300 dark:border-gray-600 text-violet-600 focus:ring-violet-500"
                                                        >
                                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                            @error('events')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        {{-- Type-specific settings --}}
                                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                            @if($type === 'webhook')
                                                <div class="space-y-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Webhook URL</label>
                                                        <input
                                                            type="url"
                                                            wire:model="webhookUrl"
                                                            placeholder="https://your-server.com/webhook"
                                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                        >
                                                        @error('webhookUrl')
                                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Secret (optional)</label>
                                                        <input
                                                            type="text"
                                                            wire:model="webhookSecret"
                                                            placeholder="HMAC signing secret"
                                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                        >
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            Used to sign payloads with HMAC-SHA256
                                                        </p>
                                                    </div>
                                                </div>
                                            @elseif($type === 'email')
                                                <div class="space-y-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Recipients</label>
                                                        <input
                                                            type="text"
                                                            wire:model="emailRecipients"
                                                            placeholder="email1@example.com, email2@example.com"
                                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                        >
                                                        @error('emailRecipients')
                                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                        @enderror
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            Separate multiple addresses with commas
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Subject prefix</label>
                                                        <input
                                                            type="text"
                                                            wire:model="emailSubjectPrefix"
                                                            placeholder="BioHost"
                                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                        >
                                                    </div>
                                                </div>
                                            @elseif($type === 'slack')
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Slack Webhook URL</label>
                                                    <input
                                                        type="url"
                                                        wire:model="slackWebhookUrl"
                                                        placeholder="https://hooks.slack.com/services/..."
                                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                    >
                                                    @error('slackWebhookUrl')
                                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                    @enderror
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        <a href="https://api.slack.com/messaging/webhooks" target="_blank" class="text-violet-600 hover:underline">
                                                            How to create a Slack webhook
                                                        </a>
                                                    </p>
                                                </div>
                                            @elseif($type === 'discord')
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Discord Webhook URL</label>
                                                    <input
                                                        type="url"
                                                        wire:model="discordWebhookUrl"
                                                        placeholder="https://discord.com/api/webhooks/..."
                                                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                    >
                                                    @error('discordWebhookUrl')
                                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                    @enderror
                                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        Channel Settings → Integrations → Webhooks
                                                    </p>
                                                </div>
                                            @elseif($type === 'telegram')
                                                <div class="space-y-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bot Token</label>
                                                        <input
                                                            type="text"
                                                            wire:model="telegramBotToken"
                                                            placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz"
                                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                        >
                                                        @error('telegramBotToken')
                                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                        @enderror
                                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                            Get from <a href="https://t.me/BotFather" target="_blank" class="text-violet-600 hover:underline">@BotFather</a>
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chat ID</label>
                                                        <input
                                                            type="text"
                                                            wire:model="telegramChatId"
                                                            placeholder="-1001234567890 or @channelname"
                                                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                                        >
                                                        @error('telegramChatId')
                                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                                        @enderror
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Enabled toggle --}}
                                        <div class="flex items-center">
                                            <input
                                                type="checkbox"
                                                wire:model="isEnabled"
                                                id="handler-enabled"
                                                class="rounded border-gray-300 dark:border-gray-600 text-violet-600 focus:ring-violet-500"
                                            >
                                            <label for="handler-enabled" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                                Enable this handler
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-violet-600 text-base font-medium text-white hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ $isEditing ? 'Save Changes' : 'Add Handler' }}
                            </button>
                            <button
                                type="button"
                                wire:click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
