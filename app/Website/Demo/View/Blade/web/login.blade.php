<div class="flex min-h-[60vh] items-center justify-center">
    <div class="w-full max-w-md">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white mb-2">Sign in to {{ config('app.name', 'Core PHP') }}</h1>
            <p class="text-zinc-400">Enter your credentials to continue</p>
        </div>

        {{-- Login Form --}}
        <form wire:submit="login" class="bg-zinc-800/50 rounded-xl p-6 space-y-6">
            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-zinc-300 mb-2">Email address</label>
                <input
                    wire:model="email"
                    type="email"
                    id="email"
                    autocomplete="email"
                    class="w-full px-4 py-3 bg-zinc-900 border border-zinc-700 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                    placeholder="you@example.com"
                >
                @error('email')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-zinc-300 mb-2">Password</label>
                <input
                    wire:model="password"
                    type="password"
                    id="password"
                    autocomplete="current-password"
                    class="w-full px-4 py-3 bg-zinc-900 border border-zinc-700 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                    placeholder="Enter your password"
                >
                @error('password')
                    <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember Me --}}
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input
                        wire:model="remember"
                        type="checkbox"
                        class="w-4 h-4 rounded border-zinc-600 bg-zinc-900 text-violet-600 focus:ring-violet-500 focus:ring-offset-zinc-900"
                    >
                    <span class="text-sm text-zinc-400">Remember me</span>
                </label>
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                class="w-full px-6 py-3 bg-violet-600 hover:bg-violet-500 text-white rounded-lg font-medium transition flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:loading.attr="disabled"
            >
                <span wire:loading.remove>Sign in</span>
                <span wire:loading class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Signing in...
                </span>
            </button>
        </form>

        {{-- Back to home --}}
        <p class="text-center mt-6 text-zinc-500">
            <a href="/" class="text-violet-400 hover:text-violet-300 transition">
                &larr; Back to home
            </a>
        </p>
    </div>
</div>
