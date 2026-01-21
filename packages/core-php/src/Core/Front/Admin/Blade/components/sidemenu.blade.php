<ul class="space-y-1">
    @foreach($items as $item)
        @if(!empty($item['divider']))
            {{-- Divider --}}
            <li class="py-2">
                <hr class="border-gray-200 dark:border-gray-700" />
            </li>
        @elseif(!empty($item['children']))
            {{-- Dropdown menu with children --}}
            <li>
                <admin:nav-menu
                    :title="$item['label']"
                    :icon="$item['icon'] ?? null"
                    :active="$item['active'] ?? false"
                    :color="$item['color'] ?? 'gray'"
                >
                    @foreach($item['children'] as $child)
                        @if(!empty($child['section']))
                            {{-- Section header within dropdown --}}
                            @php
                                $sectionIconClass = match($child['color'] ?? 'gray') {
                                    'violet' => 'text-violet-500',
                                    'blue' => 'text-blue-500',
                                    'green' => 'text-green-500',
                                    'red' => 'text-red-500',
                                    'amber' => 'text-amber-500',
                                    'emerald' => 'text-emerald-500',
                                    'cyan' => 'text-cyan-500',
                                    'pink' => 'text-pink-500',
                                    default => 'text-gray-500',
                                };
                            @endphp
                            <li class="pt-3 pb-1 first:pt-1 flex items-center gap-2">
                                @if(!empty($child['icon']))
                                    <core:icon :name="$child['icon']" class="size-4 shrink-0 {{ $sectionIconClass }}" />
                                @endif
                                <span class="text-xs font-semibold uppercase tracking-wider {{ $sectionIconClass }}">
                                    {{ $child['section'] }}
                                </span>
                            </li>
                        @else
                            <admin:nav-link
                                :href="$child['href'] ?? '#'"
                                :active="$child['active'] ?? false"
                                :badge="$child['badge'] ?? null"
                                :icon="$child['icon'] ?? null"
                                :color="$child['color'] ?? null"
                            >{{ $child['label'] }}</admin:nav-link>
                        @endif
                    @endforeach
                </admin:nav-menu>
            </li>
        @else
            {{-- Single nav item --}}
            <li>
                <admin:nav-item
                    :href="$item['href'] ?? '#'"
                    :icon="$item['icon'] ?? null"
                    :active="$item['active'] ?? false"
                    :color="$item['color'] ?? 'gray'"
                    :badge="$item['badge'] ?? null"
                >{{ $item['label'] }}</admin:nav-item>
            </li>
        @endif
    @endforeach
</ul>
