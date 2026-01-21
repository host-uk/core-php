<header class="sticky top-0 before:absolute before:inset-0 before:backdrop-blur-md max-sm:before:bg-white/90 dark:max-sm:before:bg-gray-800/90 before:-z-10 z-30 before:bg-white after:absolute after:h-px after:inset-x-0 after:top-full after:bg-gray-200 dark:after:bg-gray-700/60 after:-z-10 dark:before:bg-gray-800">
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            <!-- Header: Left side -->
            <div class="flex items-center gap-4">

                <!-- Hamburger button -->
                <button
                    class="text-gray-500 hover:text-gray-600 dark:hover:text-gray-400 sm:hidden"
                    @click.stop="sidebarOpen = !sidebarOpen"
                    aria-controls="sidebar"
                    :aria-expanded="sidebarOpen"
                >
                    <span class="sr-only">Open sidebar</span>
                    <svg class="w-6 h-6 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <rect x="4" y="5" width="16" height="2" />
                        <rect x="4" y="11" width="16" height="2" />
                        <rect x="4" y="17" width="16" height="2" />
                    </svg>
                </button>

                <!-- Workspace Switcher -->
                <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('hub.admin.workspace-switcher', []);

$key = null;

$key ??= \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::generateKey('lw-337131364-0', null);

$__html = app('livewire')->mount($__name, $__params, $key);

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>

            </div>

            <!-- Header: Right side -->
            <div class="flex items-center space-x-1">

                <!-- Search button -->
                <button class="flex items-center justify-center w-11 h-11 hover:bg-gray-100 lg:hover:bg-gray-200 dark:hover:bg-gray-700/50 dark:lg:hover:bg-gray-800 rounded-full transition-colors">
                    <span class="sr-only">Search</span>
                    <?php if (isset($component)) { $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '8def1252668913628243c4d363bee1ef::icon','data' => ['name' => 'magnifying-glass','size' => 'fa-lg','class' => 'text-gray-500 dark:text-gray-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('core::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'magnifying-glass','size' => 'fa-lg','class' => 'text-gray-500 dark:text-gray-400']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $attributes = $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $component = $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
                </button>

                <!-- Notifications button -->
                <div class="relative inline-flex" x-data="{ open: false }">
                    <button
                        class="relative flex items-center justify-center w-11 h-11 hover:bg-gray-100 lg:hover:bg-gray-200 dark:hover:bg-gray-700/50 dark:lg:hover:bg-gray-800 rounded-full transition-colors"
                        :class="{ 'bg-gray-200 dark:bg-gray-700': open }"
                        aria-haspopup="true"
                        @click.prevent="open = !open"
                        :aria-expanded="open"
                    >
                        <span class="sr-only">Notifications</span>
                        <?php if (isset($component)) { $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '8def1252668913628243c4d363bee1ef::icon','data' => ['name' => 'bell','size' => 'fa-lg','class' => 'text-gray-500 dark:text-gray-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('core::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bell','size' => 'fa-lg','class' => 'text-gray-500 dark:text-gray-400']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $attributes = $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $component = $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal4cc377eda9b63b796b6668ee7832d023 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4cc377eda9b63b796b6668ee7832d023 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::badge.index','data' => ['color' => 'red','size' => 'sm','class' => 'absolute -top-0.5 -right-0.5 min-w-5 h-5 flex items-center justify-center']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['color' => 'red','size' => 'sm','class' => 'absolute -top-0.5 -right-0.5 min-w-5 h-5 flex items-center justify-center']); ?>2 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $attributes = $__attributesOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__attributesOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4cc377eda9b63b796b6668ee7832d023)): ?>
<?php $component = $__componentOriginal4cc377eda9b63b796b6668ee7832d023; ?>
<?php unset($__componentOriginal4cc377eda9b63b796b6668ee7832d023); ?>
<?php endif; ?>
                    </button>
                    <div
                        class="origin-top-right z-10 absolute top-full -mr-48 sm:mr-0 min-w-80 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1 right-0"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                        x-show="open"
                        x-transition:enter="transition ease-out duration-200 transform"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-out duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        x-cloak
                    >
                        <div class="flex items-center justify-between pt-1.5 pb-2 px-4">
                            <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Notifications</span>
                            <button class="text-xs text-violet-500 hover:text-violet-600 dark:hover:text-violet-400">Mark all read</button>
                        </div>
                        <ul>
                            <li class="border-b border-gray-200 dark:border-gray-700/60 last:border-0">
                                <a class="block py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-700/20" href="<?php echo e(route('hub.deployments')); ?>" @click="open = false">
                                    <span class="block text-sm mb-2">New deployment completed for <span class="font-medium text-gray-800 dark:text-gray-100">Bio</span></span>
                                    <span class="block text-xs font-medium text-gray-400 dark:text-gray-500">2 hours ago</span>
                                </a>
                            </li>
                            <li class="border-b border-gray-200 dark:border-gray-700/60 last:border-0">
                                <a class="block py-2 px-4 hover:bg-gray-50 dark:hover:bg-gray-700/20" href="<?php echo e(route('hub.databases')); ?>" @click="open = false">
                                    <span class="block text-sm mb-2">Database backup successful for <span class="font-medium text-gray-800 dark:text-gray-100">Social</span></span>
                                    <span class="block text-xs font-medium text-gray-400 dark:text-gray-500">5 hours ago</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Dark mode toggle -->
                <button
                    class="flex items-center justify-center w-11 h-11 hover:bg-gray-100 lg:hover:bg-gray-200 dark:hover:bg-gray-700/50 dark:lg:hover:bg-gray-800 rounded-full transition-colors"
                    x-data="{ isDark: document.documentElement.classList.contains('dark') }"
                    @click="
                        isDark = !isDark;
                        document.documentElement.classList.toggle('dark', isDark);
                        document.documentElement.style.colorScheme = isDark ? 'dark' : 'light';
                        localStorage.setItem('dark-mode', isDark);
                        localStorage.setItem('flux.appearance', isDark ? 'dark' : 'light');
                        document.cookie = 'dark-mode=' + isDark + '; path=/; SameSite=Lax';
                    "
                >
                    <?php if (isset($component)) { $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '8def1252668913628243c4d363bee1ef::icon','data' => ['name' => 'sun-bright','size' => 'fa-lg','class' => 'text-gray-500','xShow' => '!isDark']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('core::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'sun-bright','size' => 'fa-lg','class' => 'text-gray-500','x-show' => '!isDark']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $attributes = $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $component = $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '8def1252668913628243c4d363bee1ef::icon','data' => ['name' => 'moon-stars','size' => 'fa-lg','class' => 'text-gray-400','xShow' => 'isDark','xCloak' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('core::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'moon-stars','size' => 'fa-lg','class' => 'text-gray-400','x-show' => 'isDark','x-cloak' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $attributes = $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $component = $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
                    <span class="sr-only">Toggle dark mode</span>
                </button>

                <!-- Divider -->
                <hr class="w-px h-6 bg-gray-200 dark:bg-gray-700/60 border-none" />

                <!-- User button -->
                <?php
                    $user = auth()->user();
                    $userName = $user?->name ?? 'Guest';
                    $userEmail = $user?->email ?? '';
                    $userTier = ($user && method_exists($user, 'getTier')) ? ($user->getTier()?->label() ?? 'Free') : 'Free';
                    $userInitials = collect(explode(' ', $userName))->map(fn($n) => strtoupper(substr($n, 0, 1)))->take(2)->join('');
                ?>
                <div class="relative inline-flex" x-data="{ open: false }">
                    <button
                        class="inline-flex justify-center items-center group"
                        aria-haspopup="true"
                        @click.prevent="open = !open"
                        :aria-expanded="open"
                    >
                        <div class="w-8 h-8 rounded-full bg-violet-500 flex items-center justify-center text-white text-xs font-semibold">
                            <?php echo e($userInitials); ?>

                        </div>
                        <div class="flex items-center truncate">
                            <span class="truncate ml-2 text-sm font-medium text-gray-600 dark:text-gray-100 group-hover:text-gray-800 dark:group-hover:text-white"><?php echo e($userName); ?></span>
                            <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500" viewBox="0 0 12 12">
                                <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                            </svg>
                        </div>
                    </button>
                    <div
                        class="origin-top-right z-10 absolute top-full min-w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/60 py-1.5 rounded-lg shadow-lg overflow-hidden mt-1 right-0"
                        @click.outside="open = false"
                        @keydown.escape.window="open = false"
                        x-show="open"
                        x-transition:enter="transition ease-out duration-200 transform"
                        x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-out duration-200"
                        x-transition:leave-start="opacity-100"
                        x-transition:leave-end="opacity-0"
                        x-cloak
                    >
                        <div class="pt-0.5 pb-2 px-3 mb-1 border-b border-gray-200 dark:border-gray-700/60">
                            <div class="font-medium text-gray-800 dark:text-gray-100"><?php echo e($userName); ?></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo e($userEmail); ?></div>
                        </div>
                        <ul>
                            <li>
                                <a class="font-medium text-sm text-violet-500 hover:text-violet-600 dark:hover:text-violet-400 flex items-center py-1.5 px-3" href="<?php echo e(route('hub.account')); ?>" @click="open = false">
                                    <?php if (isset($component)) { $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '8def1252668913628243c4d363bee1ef::icon','data' => ['name' => 'user','class' => 'w-5 mr-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('core::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user','class' => 'w-5 mr-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $attributes = $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $component = $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?> Profile
                                </a>
                            </li>
                            <li>
                                <a class="font-medium text-sm text-violet-500 hover:text-violet-600 dark:hover:text-violet-400 flex items-center py-1.5 px-3" href="<?php echo e(route('hub.account.settings')); ?>" @click="open = false">
                                    <?php if (isset($component)) { $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '8def1252668913628243c4d363bee1ef::icon','data' => ['name' => 'gear','class' => 'w-5 mr-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('core::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'gear','class' => 'w-5 mr-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $attributes = $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $component = $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?> Settings
                                </a>
                            </li>
                            <li>
                                <a class="font-medium text-sm text-violet-500 hover:text-violet-600 dark:hover:text-violet-400 flex items-center py-1.5 px-3" href="/" @click="open = false">
                                    <?php if (isset($component)) { $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '8def1252668913628243c4d363bee1ef::icon','data' => ['name' => 'arrow-left','class' => 'w-5 mr-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('core::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-left','class' => 'w-5 mr-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $attributes = $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $component = $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?> Back to Site
                                </a>
                            </li>
                            <li class="border-t border-gray-200 dark:border-gray-700/60 mt-1 pt-1">
                                <a class="font-medium text-sm text-violet-500 hover:text-violet-600 dark:hover:text-violet-400 flex items-center py-1.5 px-3" href="/logout">
                                    <?php if (isset($component)) { $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '8def1252668913628243c4d363bee1ef::icon','data' => ['name' => 'right-from-bracket','class' => 'w-5 mr-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('core::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'right-from-bracket','class' => 'w-5 mr-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $attributes = $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac)): ?>
<?php $component = $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac; ?>
<?php unset($__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac); ?>
<?php endif; ?> Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>
    </div>
</header>
<?php /**PATH /Users/snider/Code/lab/core-php/packages/core-admin/src/Website/Hub/View/Blade/admin/components/header.blade.php ENDPATH**/ ?>