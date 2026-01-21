<div>
    {{-- Page header --}}
    <div class="sm:flex sm:justify-between sm:items-center mb-8">
        <div class="mb-4 sm:mb-0">
            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">{{ __('web::web.domains.title') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('web::web.domains.subtitle') }}</p>
        </div>
        <div>
            <button
                wire:click="openAddModal"
                class="btn bg-violet-500 hover:bg-violet-600 text-white"
            >
                <i class="fa-solid fa-plus mr-2"></i>
                {{ __('web::web.domains.add_domain') }}
            </button>
        </div>
    </div>

    {{-- Domains table --}}
    <div wire:loading.class="opacity-50 pointer-events-none">
    @if($this->domains->count())
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('web::web.domains.table.domain') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('web::web.domains.table.status') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('web::web.domains.table.default_page') }}</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('web::web.domains.table.added') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('web::web.domains.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($this->domains as $domain)
                            <tr wire:key="domain-{{ $domain->id }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                                            <i class="fa-solid fa-globe text-violet-500"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                {{ $domain->host }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $domain->scheme }}://{{ $domain->host }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($domain->isVerified())
                                        @if($domain->is_enabled)
                                            <flux:badge color="green" icon="check-circle">{{ __('web::web.status.active') }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc" icon="pause-circle">{{ __('web::web.status.disabled') }}</flux:badge>
                                        @endif
                                    @elseif($domain->verification_status === 'failed')
                                        <flux:badge color="red" icon="x-circle">{{ __('web::web.status.failed') }}</flux:badge>
                                    @else
                                        <flux:badge color="yellow" icon="clock">{{ __('web::web.status.pending') }}</flux:badge>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    @if($domain->exclusiveLink)
                                        <a href="{{ route('hub.bio.edit', $domain->exclusiveLink->id) }}" wire:navigate class="text-violet-600 hover:text-violet-700 dark:text-violet-400">
                                            /{{ $domain->exclusiveLink->url }}
                                        </a>
                                    @elseif($domain->custom_index_url)
                                        <span class="text-gray-400">{{ __('web::web.domains.custom_url') }}</span>
                                    @else
                                        <span class="text-gray-400">{{ __('web::web.domains.page_not_set') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $domain->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <flux:button.group>
                                        @unless($domain->isVerified())
                                            <flux:button
                                                wire:click="openVerifyModal({{ $domain->id }})"
                                                variant="ghost"
                                                size="sm"
                                                icon="shield-check"
                                                :tooltip="__('web::web.tooltips.verify_domain')"
                                                square
                                            />
                                        @endunless
                                        <flux:button
                                            wire:click="openEditModal({{ $domain->id }})"
                                            variant="ghost"
                                            size="sm"
                                            icon="cog-6-tooth"
                                            :tooltip="__('web::web.tooltips.edit_settings')"
                                            square
                                        />
                                        <flux:button
                                            wire:click="toggleEnabled({{ $domain->id }})"
                                            variant="ghost"
                                            size="sm"
                                            :icon="$domain->is_enabled ? 'check-circle' : 'pause-circle'"
                                            :tooltip="$domain->is_enabled ? __('web::web.actions.disable') : __('web::web.actions.enable')"
                                            square
                                        />
                                        <flux:button
                                            wire:click="deleteDomain({{ $domain->id }})"
                                            :wire:confirm="__('web::web.domains.confirm_delete')"
                                            variant="danger"
                                            size="sm"
                                            icon="trash"
                                            :tooltip="__('web::web.tooltips.delete')"
                                            square
                                        />
                                    </flux:button.group>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($this->domains->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $this->domains->links() }}
                </div>
            @endif
        </div>
    </div>
    {{-- Loading indicator --}}
    <div wire:loading class="flex justify-center py-8">
        <flux:icon name="arrow-path" class="size-6 animate-spin text-violet-500" />
    </div>
    @else
    </div>
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-12 px-4">
            <div class="w-16 h-16 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
                <flux:icon name="globe-alt" class="size-8 text-zinc-400" />
            </div>
            <flux:heading size="lg" class="text-center">{{ __('web::web.domains.empty.title') }}</flux:heading>
            <flux:subheading class="text-center mt-1 max-w-sm">
                {{ __('web::web.domains.empty.message') }}
            </flux:subheading>
            <flux:button wire:click="openAddModal" icon="plus" variant="primary" class="mt-4">
                {{ __('web::web.domains.add_first') }}
            </flux:button>
        </div>
    @endif

    {{-- Add Domain Modal --}}
    @if($showAddModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeAddModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <form wire:submit="addDomain">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-violet-100 dark:bg-violet-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fa-solid fa-globe text-violet-600 dark:text-violet-400"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        {{ __('web::web.domains.modal.add_title') }}
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('web::web.domains.modal.domain_label') }}</label>
                                            <input
                                                type="text"
                                                wire:model="newHost"
                                                placeholder="{{ __('web::web.domains.modal.domain_placeholder') }}"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            >
                                            @error('newHost')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('web::web.domains.modal.domain_hint') }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('web::web.domains.modal.default_page') }}</label>
                                            <select
                                                wire:model="defaultBiolinkId"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            >
                                                <option value="">{{ __('web::web.domains.modal.no_default') }}</option>
                                                @foreach($this->biolinks as $biolink)
                                                    <option value="{{ $biolink->id }}">/{{ $biolink->url }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('web::web.domains.modal.default_hint') }}
                                            </p>
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
                                {{ __('web::web.domains.add_domain') }}
                            </button>
                            <button
                                type="button"
                                wire:click="closeAddModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ __('web::web.actions.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Verification Modal --}}
    @if($showVerifyModal && $selectedDomain)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeVerifyModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-2xl sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fa-solid fa-shield-check text-yellow-600 dark:text-yellow-400"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                    {{ __('web::web.domains.modal.verify_title', ['host' => $selectedDomain->host]) }}
                                </h3>
                                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('web::web.domains.verify.description') }}
                                </p>

                                <div class="mt-6 space-y-6">
                                    {{-- Option 1: CNAME Record --}}
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                            <i class="fa-solid fa-1 mr-2 text-violet-500"></i>
                                            {{ __('web::web.domains.verify.option_cname') }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                            {{ __('web::web.domains.verify.option_cname_hint') }}
                                        </p>
                                        <div class="grid grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400 block text-xs mb-1">{{ __('web::web.domains.verify.dns_type') }}</span>
                                                <code class="bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-gray-900 dark:text-gray-100">CNAME</code>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400 block text-xs mb-1">{{ __('web::web.domains.verify.dns_host') }}</span>
                                                <code class="bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-gray-900 dark:text-gray-100">{{ $selectedDomain->host }}</code>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400 block text-xs mb-1">{{ __('web::web.domains.verify.dns_target') }}</span>
                                                <code class="bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-gray-900 dark:text-gray-100">{{ $dnsInstructions['cname']['target'] ?? 'bio.host.uk.com' }}</code>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Option 2: TXT Record --}}
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                            <i class="fa-solid fa-2 mr-2 text-violet-500"></i>
                                            {{ __('web::web.domains.verify.option_txt') }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                            {{ __('web::web.domains.verify.option_txt_hint') }}
                                        </p>
                                        <div class="grid grid-cols-3 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400 block text-xs mb-1">{{ __('web::web.domains.verify.dns_type') }}</span>
                                                <code class="bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-gray-900 dark:text-gray-100">TXT</code>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400 block text-xs mb-1">{{ __('web::web.domains.verify.dns_host') }}</span>
                                                <code class="bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-gray-900 dark:text-gray-100 text-xs">{{ $dnsInstructions['txt']['host'] ?? '_biohost-verify.' . $selectedDomain->host }}</code>
                                            </div>
                                            <div>
                                                <span class="text-gray-500 dark:text-gray-400 block text-xs mb-1">{{ __('web::web.domains.verify.dns_value') }}</span>
                                                <div class="flex items-center gap-2">
                                                    <code class="bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-gray-900 dark:text-gray-100 text-xs truncate">{{ $dnsInstructions['txt']['value'] ?? $selectedDomain->getDnsVerificationRecord() }}</code>
                                                    <button
                                                        x-data
                                                        x-on:click="navigator.clipboard.writeText('{{ $dnsInstructions['txt']['value'] ?? $selectedDomain->getDnsVerificationRecord() }}'); $dispatch('notify', { message: '{{ __('web::web.actions.copied') }}', type: 'success' })"
                                                        class="text-violet-600 hover:text-violet-700 dark:text-violet-400"
                                                        title="{{ __('web::web.actions.copy') }}"
                                                    >
                                                        <i class="fa-solid fa-copy"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-3 flex items-center gap-2">
                                            <button
                                                wire:click="regenerateToken({{ $selectedDomain->id }})"
                                                class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                                            >
                                                <i class="fa-solid fa-rotate mr-1"></i> {{ __('web::web.actions.regenerate_token') }}
                                            </button>
                                        </div>
                                    </div>

                                    {{-- DNS Status Check --}}
                                    @if(!empty($dnsStatus))
                                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                                            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
                                                <i class="fa-solid fa-magnifying-glass mr-2 text-violet-500"></i>
                                                {{ __('web::web.domains.verify.dns_status') }}
                                            </h4>
                                            <dl class="text-sm space-y-2">
                                                <div class="flex items-center">
                                                    <dt class="text-gray-500 dark:text-gray-400 w-24">{{ __('web::web.domains.verify.resolves') }}</dt>
                                                    <dd>
                                                        @if($dnsStatus['resolves'] ?? false)
                                                            <span class="text-green-600 dark:text-green-400"><i class="fa-solid fa-check mr-1"></i> {{ __('web::web.domains.verify.resolves_yes') }}</span>
                                                        @else
                                                            <span class="text-red-600 dark:text-red-400"><i class="fa-solid fa-times mr-1"></i> {{ __('web::web.domains.verify.resolves_no') }}</span>
                                                        @endif
                                                    </dd>
                                                </div>
                                                @if($dnsStatus['cname'] ?? null)
                                                    <div class="flex items-center">
                                                        <dt class="text-gray-500 dark:text-gray-400 w-24">CNAME:</dt>
                                                        <dd class="text-gray-900 dark:text-gray-100">{{ $dnsStatus['cname'] }}</dd>
                                                    </div>
                                                @endif
                                                @if(!empty($dnsStatus['txt_records']))
                                                    <div class="flex items-start">
                                                        <dt class="text-gray-500 dark:text-gray-400 w-24">TXT:</dt>
                                                        <dd class="text-gray-900 dark:text-gray-100">
                                                            @foreach($dnsStatus['txt_records'] as $txt)
                                                                <div class="text-xs font-mono">{{ $txt }}</div>
                                                            @endforeach
                                                        </dd>
                                                    </div>
                                                @endif
                                            </dl>
                                        </div>
                                    @endif

                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <i class="fa-solid fa-info-circle mr-1"></i>
                                        {{ __('web::web.domains.verify.dns_propagation') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                        <button
                            wire:click="verifyDomain"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-violet-600 text-base font-medium text-white hover:bg-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:w-auto sm:text-sm"
                        >
                            <i class="fa-solid fa-check mr-2"></i>
                            {{ __('web::web.actions.verify_now') }}
                        </button>
                        <button
                            wire:click="checkDns"
                            class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:w-auto sm:text-sm"
                        >
                            <i class="fa-solid fa-magnifying-glass mr-2"></i>
                            {{ __('web::web.actions.check_dns') }}
                        </button>
                        <button
                            wire:click="closeVerifyModal"
                            class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:w-auto sm:text-sm"
                        >
                            {{ __('web::web.actions.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Domain Modal --}}
    @if($showEditModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" wire:click="closeEditModal"></div>

                <div class="relative z-10 inline-block bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                    <form wire:submit="saveDomain">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fa-solid fa-cog text-gray-600 dark:text-gray-400"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100" id="modal-title">
                                        {{ __('web::web.domains.modal.edit_title') }}
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('web::web.domains.modal.default_page_edit') }}</label>
                                            <select
                                                wire:model="editDefaultBiolinkId"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            >
                                                <option value="">{{ __('web::web.labels.none') }}</option>
                                                @foreach($this->biolinks as $biolink)
                                                    <option value="{{ $biolink->id }}">/{{ $biolink->url }}</option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('web::web.domains.modal.default_hint') }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('web::web.domains.modal.custom_index_url') }}</label>
                                            <input
                                                type="url"
                                                wire:model="editCustomIndexUrl"
                                                placeholder="{{ __('web::web.domains.modal.custom_index_placeholder') }}"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            >
                                            @error('editCustomIndexUrl')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('web::web.domains.modal.custom_index_hint') }}
                                            </p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('web::web.domains.modal.custom_404_url') }}</label>
                                            <input
                                                type="url"
                                                wire:model="editCustomNotFoundUrl"
                                                placeholder="{{ __('web::web.domains.modal.custom_404_placeholder') }}"
                                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-violet-500 focus:ring-violet-500"
                                            >
                                            @error('editCustomNotFoundUrl')
                                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                            @enderror
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                {{ __('web::web.domains.modal.custom_404_hint') }}
                                            </p>
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
                                {{ __('web::web.actions.save') }}
                            </button>
                            <button
                                type="button"
                                wire:click="closeEditModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-violet-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                {{ __('web::web.actions.cancel') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
