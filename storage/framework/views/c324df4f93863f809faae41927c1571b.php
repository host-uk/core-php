<?php
    $darkMode = request()->cookie('dark-mode') === 'true';
?>
    <!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="overscroll-none <?php echo e($darkMode ? 'dark' : ''); ?>"
      style="color-scheme: <?php echo e($darkMode ? 'dark' : 'light'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e($title ?? 'Admin'); ?> - <?php echo e(config('app.name', 'Host Hub')); ?></title>

    
    <style>
        html {
            background-color: #f3f4f6;
        }

        html.dark {
            background-color: #111827;
        }
    </style>

    <script>
        
        (function () {
            // Dark mode - sync our key with Flux's key
            var darkMode = localStorage.getItem('dark-mode');
            if (darkMode === 'true') {
                // Sync to Flux's appearance key so the Flux directive doesn't override
                localStorage.setItem('flux.appearance', 'dark');
            } else if (darkMode === 'false') {
                localStorage.setItem('flux.appearance', 'light');
            }
            // Set cookie for PHP
            document.cookie = 'dark-mode=' + (darkMode || 'false') + '; path=/; SameSite=Lax';

            // Icon settings
            var iconStyle = localStorage.getItem('icon-style') || 'fa-notdog fa-solid';
            var iconSize = localStorage.getItem('icon-size') || 'fa-lg';
            document.cookie = 'icon-style=' + iconStyle + '; path=/; SameSite=Lax';
            document.cookie = 'icon-size=' + iconSize + '; path=/; SameSite=Lax';
        })();
    </script>

    <!-- Fonts -->
    <?php echo $__env->make('layouts::partials.fonts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Font Awesome -->
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(file_exists(public_path('vendor/fontawesome/css/all.min.css'))): ?>
        <link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css?v=<?php echo e(filemtime(public_path('vendor/fontawesome/css/all.min.css'))); ?>">
    <?php else: ?>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <!-- Scripts -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/admin.css', 'resources/js/app.js']); ?>

    <!-- Flux -->
    <?php echo app('flux')->fluxAppearance(); ?>

</head>
<body
    class="font-inter antialiased bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 overscroll-none"
    x-data="{ sidebarOpen: false }"
    @open-sidebar.window="sidebarOpen = true"
>


<!-- Page wrapper -->
<div class="flex h-[100dvh] overflow-hidden overscroll-none">

    <?php echo $__env->make('hub::admin.components.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <!-- Content area (offset for fixed sidebar) -->
    <div
        class="relative flex flex-col flex-1 overflow-y-auto overflow-x-hidden overscroll-none ml-0 sm:ml-20 lg:ml-64"
        x-ref="contentarea">

        <?php echo $__env->make('hub::admin.components.header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <main class="grow px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <?php echo e($slot); ?>

        </main>

    </div>

</div>

<!-- Toast Notifications -->
<?php app("livewire")->forceAssetInjection(); ?><div x-persist="<?php echo e('toast'); ?>">
    <?php if (isset($component)) { $__componentOriginal6e0689304ed9fe6f1f826bea0820c41b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6e0689304ed9fe6f1f826bea0820c41b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'e60dd9d2c3a62d619c9acb38f20d5aa5::toast.index','data' => ['position' => 'bottom end']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flux::toast'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['position' => 'bottom end']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6e0689304ed9fe6f1f826bea0820c41b)): ?>
<?php $attributes = $__attributesOriginal6e0689304ed9fe6f1f826bea0820c41b; ?>
<?php unset($__attributesOriginal6e0689304ed9fe6f1f826bea0820c41b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6e0689304ed9fe6f1f826bea0820c41b)): ?>
<?php $component = $__componentOriginal6e0689304ed9fe6f1f826bea0820c41b; ?>
<?php unset($__componentOriginal6e0689304ed9fe6f1f826bea0820c41b); ?>
<?php endif; ?>
</div>

<!-- Developer Bar (Hades accounts only) -->
<?php echo $__env->make('hub::admin.components.developer-bar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

<!-- Flux Scripts -->
<?php app('livewire')->forceAssetInjection(); ?>
<?php echo app('flux')->scripts(); ?>


<?php echo $__env->yieldPushContent('scripts'); ?>

<script>
    // Light/Dark mode toggle (guarded for Livewire navigation)
    (function() {
        if (window.__lightSwitchInitialized) return;
        window.__lightSwitchInitialized = true;

        const lightSwitch = document.querySelector('.light-switch');
        if (lightSwitch) {
            lightSwitch.addEventListener('change', () => {
                const {checked} = lightSwitch;
                document.documentElement.classList.toggle('dark', checked);
                document.documentElement.style.colorScheme = checked ? 'dark' : 'light';
                localStorage.setItem('dark-mode', checked);
            });
        }
    })();
</script>
</body>
</html>
<?php /**PATH /Users/snider/Code/lab/core-php/packages/core-admin/src/Website/Hub/View/Blade/admin/layouts/app.blade.php ENDPATH**/ ?>