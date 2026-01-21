<div class="flex min-h-[70vh] items-center justify-center">
    <div class="w-full max-w-lg">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-white mb-2">Install {{ config('app.name', 'Core PHP') }}</h1>
            <p class="text-zinc-400">Let's get your application set up</p>
        </div>

        {{-- Progress Steps --}}
        <div class="flex items-center justify-center mb-8">
            @foreach ([1 => 'Requirements', 2 => 'Admin User', 3 => 'Complete'] as $num => $label)
                <div class="flex items-center">
                    <div class="flex flex-col items-center">
                        <div @class([
                            'w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium transition',
                            'bg-violet-600 text-white' => $step >= $num,
                            'bg-zinc-700 text-zinc-400' => $step < $num,
                        ])>
                            @if ($step > $num)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $num }}
                            @endif
                        </div>
                        <span class="text-xs mt-1 {{ $step >= $num ? 'text-zinc-300' : 'text-zinc-500' }}">{{ $label }}</span>
                    </div>
                    @if ($num < 3)
                        <div @class([
                            'w-16 h-0.5 mx-2 mb-5',
                            'bg-violet-600' => $step > $num,
                            'bg-zinc-700' => $step <= $num,
                        ])></div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Error/Success Messages --}}
        @if ($error)
            <div class="mb-4 p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
                {{ $error }}
            </div>
        @endif

        @if ($success)
            <div class="mb-4 p-4 bg-green-500/10 border border-green-500/20 rounded-lg text-green-400 text-sm">
                {{ $success }}
            </div>
        @endif

        {{-- Step 1: Requirements --}}
        @if ($step === 1)
            <div class="bg-zinc-800/50 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-white mb-4">System Requirements</h2>

                <div class="space-y-3">
                    @foreach ($checks as $key => $check)
                        <div class="flex items-center justify-between p-3 bg-zinc-900/50 rounded-lg">
                            <div class="flex items-center gap-3">
                                @if ($check['passed'])
                                    <div class="w-6 h-6 rounded-full bg-green-500/20 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                @else
                                    <div class="w-6 h-6 rounded-full bg-red-500/20 flex items-center justify-center">
                                        <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-white">{{ $check['label'] }}</div>
                                    <div class="text-xs text-zinc-500">{{ $check['description'] }}</div>
                                </div>
                            </div>
                            <span class="text-sm {{ $check['passed'] ? 'text-green-400' : 'text-red-400' }}">
                                {{ $check['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                @if (!$checks['migrations']['passed'])
                    <button
                        wire:click="runMigrations"
                        wire:loading.attr="disabled"
                        class="mt-4 w-full px-4 py-2 bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg text-sm font-medium transition disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="runMigrations">Run Migrations</span>
                        <span wire:loading wire:target="runMigrations">Running migrations...</span>
                    </button>
                @endif

                <div class="mt-6 flex justify-end">
                    <button
                        wire:click="nextStep"
                        @disabled(!collect($checks)->every(fn ($c) => $c['passed']))
                        class="px-6 py-2 bg-violet-600 hover:bg-violet-500 disabled:bg-zinc-700 disabled:text-zinc-500 text-white rounded-lg font-medium transition disabled:cursor-not-allowed"
                    >
                        Continue
                    </button>
                </div>
            </div>
        @endif

        {{-- Step 2: Create Admin User --}}
        @if ($step === 2)
            <form wire:submit="createUser" class="bg-zinc-800/50 rounded-xl p-6 space-y-5">
                <h2 class="text-lg font-semibold text-white mb-4">Create Admin Account</h2>

                <div>
                    <label for="name" class="block text-sm font-medium text-zinc-300 mb-2">Name</label>
                    <input
                        wire:model="name"
                        type="text"
                        id="name"
                        class="w-full px-4 py-3 bg-zinc-900 border border-zinc-700 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                        placeholder="Your name"
                    >
                    @error('name')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-zinc-300 mb-2">Email address</label>
                    <input
                        wire:model="email"
                        type="email"
                        id="email"
                        class="w-full px-4 py-3 bg-zinc-900 border border-zinc-700 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                        placeholder="admin@example.com"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-zinc-300 mb-2">Password</label>
                    <input
                        wire:model="password"
                        type="password"
                        id="password"
                        class="w-full px-4 py-3 bg-zinc-900 border border-zinc-700 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                        placeholder="Minimum 8 characters"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-zinc-300 mb-2">Confirm Password</label>
                    <input
                        wire:model="password_confirmation"
                        type="password"
                        id="password_confirmation"
                        class="w-full px-4 py-3 bg-zinc-900 border border-zinc-700 rounded-lg text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                        placeholder="Confirm your password"
                    >
                </div>

                <label class="flex items-center gap-3 cursor-pointer p-3 bg-zinc-900/50 rounded-lg">
                    <input
                        wire:model="createDemo"
                        type="checkbox"
                        class="w-4 h-4 rounded border-zinc-600 bg-zinc-900 text-violet-600 focus:ring-violet-500 focus:ring-offset-zinc-900"
                    >
                    <div>
                        <span class="text-sm text-zinc-300">Create demo user</span>
                        <p class="text-xs text-zinc-500">demo@example.com / password</p>
                    </div>
                </label>

                <div class="flex justify-between pt-2">
                    <button
                        type="button"
                        wire:click="previousStep"
                        class="px-6 py-2 bg-zinc-700 hover:bg-zinc-600 text-white rounded-lg font-medium transition"
                    >
                        Back
                    </button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="px-6 py-2 bg-violet-600 hover:bg-violet-500 text-white rounded-lg font-medium transition disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="createUser">Create Account</span>
                        <span wire:loading wire:target="createUser">Creating...</span>
                    </button>
                </div>
            </form>
        @endif

        {{-- Step 3: Complete --}}
        @if ($step === 3)
            <div class="bg-zinc-800/50 rounded-xl p-6 text-center">
                <div class="w-16 h-16 bg-green-500/20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h2 class="text-xl font-semibold text-white mb-2">Installation Complete!</h2>
                <p class="text-zinc-400 mb-6">
                    {{ config('app.name', 'Core PHP') }} is ready to use.
                </p>

                <div class="bg-zinc-900/50 rounded-lg p-4 mb-6 text-left">
                    <h3 class="text-sm font-medium text-zinc-300 mb-2">Your credentials:</h3>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Email:</span>
                            <span class="text-white">{{ $email }}</span>
                        </div>
                        @if ($createDemo)
                            <div class="border-t border-zinc-800 my-2 pt-2">
                                <span class="text-zinc-500 text-xs">Demo account:</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Email:</span>
                                <span class="text-white">demo@example.com</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Password:</span>
                                <span class="text-white">password</span>
                            </div>
                        @endif
                    </div>
                </div>

                <button
                    wire:click="finish"
                    class="w-full px-6 py-3 bg-violet-600 hover:bg-violet-500 text-white rounded-lg font-medium transition"
                >
                    Go to Dashboard
                </button>
            </div>
        @endif
    </div>
</div>
