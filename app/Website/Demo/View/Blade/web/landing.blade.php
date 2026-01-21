<div class="text-center">
    {{-- Hero Section --}}
    <div class="py-12 md:py-20">
        <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
            {{ config('app.name', 'Core PHP') }}
        </h1>
        <p class="text-xl text-zinc-400 max-w-2xl mx-auto mb-8">
            A modular monolith framework for Laravel.
            Build SaaS applications with event-driven architecture.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="https://github.com/host-uk/core-php" 
               class="inline-flex items-center gap-2 px-6 py-3 bg-violet-600 hover:bg-violet-500 text-white rounded-lg font-medium transition">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
                View on GitHub
            </a>
            <a href="/hub" 
               class="inline-flex items-center gap-2 px-6 py-3 bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg font-medium transition">
                Open Dashboard
            </a>
        </div>
    </div>

    {{-- Features Grid --}}
    <div class="grid md:grid-cols-3 gap-8 py-12">
        <div class="p-6 bg-zinc-800/50 rounded-xl">
            <div class="w-12 h-12 bg-violet-600/20 rounded-lg flex items-center justify-center mb-4 mx-auto">
                <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">Modular Architecture</h3>
            <p class="text-zinc-400">Event-driven modules that load lazily. Only what you need, when you need it.</p>
        </div>

        <div class="p-6 bg-zinc-800/50 rounded-xl">
            <div class="w-12 h-12 bg-violet-600/20 rounded-lg flex items-center justify-center mb-4 mx-auto">
                <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">Multi-Website</h3>
            <p class="text-zinc-400">Domain-scoped website modules. Each site isolated, all in one codebase.</p>
        </div>

        <div class="p-6 bg-zinc-800/50 rounded-xl">
            <div class="w-12 h-12 bg-violet-600/20 rounded-lg flex items-center justify-center mb-4 mx-auto">
                <svg class="w-6 h-6 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-white mb-2">Flux UI Ready</h3>
            <p class="text-zinc-400">Built for Livewire 4 and Flux UI. Modern, composable components.</p>
        </div>
    </div>
</div>
