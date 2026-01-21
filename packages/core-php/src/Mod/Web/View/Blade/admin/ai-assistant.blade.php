<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">AI Assistant</h1>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1">
                Get AI-powered suggestions and improvements for your bio page
            </p>
        </div>
        <a href="{{ route('hub.bio.edit', $biolinkId) }}" class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
            ← Back to biolink
        </a>
    </div>

    {{-- AI Credits Status --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">AI Credits</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                    @if($this->aiCredits['unlimited'] ?? false)
                        Unlimited credits available
                    @else
                        {{ $this->aiCredits['remaining'] ?? 0 }} of {{ $this->aiCredits['limit'] ?? 0 }} credits remaining
                    @endif
                </p>
            </div>
            @if(!($this->aiCredits['unlimited'] ?? false))
                <div class="flex items-center gap-2">
                    <div class="w-32 h-2 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 transition-all duration-300"
                             style="width: {{ ($this->aiCredits['limit'] ?? 0) > 0 ? ((($this->aiCredits['used'] ?? 0) / $this->aiCredits['limit']) * 100) : 0 }}%">
                        </div>
                    </div>
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">
                        {{ number_format($this->aiCredits['used'] ?? 0) }}
                    </span>
                </div>
            @endif
        </div>
        @if(!($this->aiCredits['available'] ?? false))
            <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                <p class="text-sm text-amber-800 dark:text-amber-200">
                    You've used all your AI credits this month. Upgrade your plan to continue using AI features.
                </p>
            </div>
        @endif
    </div>

    {{-- Quick Actions --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            @foreach($quickActions as $action => $label)
                <button
                    wire:click="executeQuickAction('{{ $action }}')"
                    @disabled($isGenerating || !$this->aiCredits['available'])
                    class="flex items-center gap-3 p-4 rounded-lg border border-zinc-200 dark:border-zinc-700 hover:border-blue-500 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    @if($isGenerating && $currentAction === $action)
                        <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    @else
                        <svg class="h-5 w-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    @endif
                    <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $label }}</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Chat Interface --}}
    <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Chat with AI</h2>
                @if(count($chatHistory) > 0)
                    <button
                        wire:click="clearChat"
                        class="text-sm text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                    >
                        Clear chat
                    </button>
                @endif
            </div>
        </div>

        {{-- Chat Messages --}}
        <div class="h-96 overflow-y-auto p-4 space-y-4">
            @if(count($chatHistory) === 0)
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <svg class="h-12 w-12 text-zinc-300 dark:text-zinc-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            No messages yet. Try a quick action or send a message.
                        </p>
                    </div>
                </div>
            @else
                @foreach($chatHistory as $message)
                    <div class="flex gap-3 {{ $message['role'] === 'user' ? 'justify-end' : '' }}">
                        @if($message['role'] !== 'user')
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                    </svg>
                                </div>
                            </div>
                        @endif
                        <div class="flex-1 max-w-2xl">
                            <div class="rounded-lg p-3 {{ $message['role'] === 'user' ? 'bg-blue-500 text-white' : ($message['role'] === 'system' ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100') }}">
                                <p class="text-sm whitespace-pre-wrap">{{ $message['content'] }}</p>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 {{ $message['role'] === 'user' ? 'text-right' : '' }}">
                                {{ \Carbon\Carbon::parse($message['timestamp'])->diffForHumans() }}
                            </p>
                        </div>
                        @if($message['role'] === 'user')
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                    <svg class="h-5 w-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Chat Input --}}
        <div class="border-t border-zinc-200 dark:border-zinc-700 p-4">
            <form wire:submit="sendMessage" class="flex gap-2">
                <input
                    type="text"
                    wire:model="chatMessage"
                    placeholder="Ask the AI for help..."
                    class="flex-1 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 focus:border-blue-500 focus:ring-blue-500"
                    @disabled(!$this->aiCredits['available'])
                >
                <button
                    type="submit"
                    @disabled(!$this->aiCredits['available'] || empty(trim($chatMessage)))
                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Send
                </button>
            </form>
        </div>
    </div>

    {{-- Information Panel --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <h3 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-2">How to use the AI Assistant</h3>
        <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1 list-disc list-inside">
            <li>Use quick actions for common tasks like generating bio text or improving SEO</li>
            <li>Each AI generation uses 1 credit (some actions use more)</li>
            <li>Credits reset monthly based on your subscription plan</li>
            <li>Generated content is in UK English and follows our brand guidelines</li>
        </ul>
    </div>
</div>
