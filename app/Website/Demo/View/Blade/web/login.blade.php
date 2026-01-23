<div class="flex min-h-[60vh] items-center justify-center">
    <div class="w-full max-w-md">
        {{-- Header --}}
        <div class="text-center mb-8">
            <flux:heading size="xl">Sign in to {{ config('app.name', 'Core PHP') }}</flux:heading>
            <flux:subheading>Enter your credentials to continue</flux:subheading>
        </div>

        {{-- Login Form --}}
        <form wire:submit="login" class="bg-zinc-800/50 rounded-xl p-6 space-y-6">
            {{-- Email --}}
            <flux:input
                wire:model="email"
                type="email"
                label="Email address"
                placeholder="you@example.com"
                autocomplete="email"
            />

            {{-- Password --}}
            <flux:input
                wire:model="password"
                type="password"
                label="Password"
                placeholder="Enter your password"
                autocomplete="current-password"
            />

            {{-- Remember Me --}}
            <flux:checkbox wire:model="remember" label="Remember me" />

            {{-- Submit --}}
            <flux:button type="submit" variant="primary" class="w-full">
                Sign in
            </flux:button>
        </form>

        {{-- Back to home --}}
        <flux:link href="/" class="block text-center mt-6">
            &larr; Back to home
        </flux:link>
    </div>
</div>
