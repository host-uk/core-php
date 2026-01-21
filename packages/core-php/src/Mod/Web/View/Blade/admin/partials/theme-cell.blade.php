<div class="flex items-center gap-3">
    <div
        class="h-10 w-10 rounded-lg border border-zinc-200 dark:border-zinc-700"
        style="background: {{ $theme->settings['background_color'] ?? '#f3f4f6' }};"
    ></div>
    <div>
        <div class="font-medium text-gray-900 dark:text-gray-100">{{ $theme->name }}</div>
        @if($theme->description)
            <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($theme->description, 40) }}</div>
        @endif
    </div>
</div>
