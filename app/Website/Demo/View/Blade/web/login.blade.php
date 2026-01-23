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
            <flux:button type="submit" variant="primary" class="w-full">
                Sign in
            </flux:button>
        </form>

        {{-- Back to home --}}
        <p class="text-center mt-6 text-zinc-500">
            <a href="/" class="text-violet-400 hover:text-violet-300 transition">
                &larr; Back to home
            </a>
        </p>
    </div>
</div>
