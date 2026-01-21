<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'title',
    'icon' => null,
    'active' => false,
    'color' => 'gray',
    'expanded' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'title',
    'icon' => null,
    'active' => false,
    'color' => 'gray',
    'expanded' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $isExpanded = $expanded ?? $active;
?>

<li class="mb-0.5" x-data="{ expanded: <?php echo e($isExpanded ? 'true' : 'false'); ?> }">
    <a class="block text-gray-800 dark:text-gray-100 truncate transition hover:text-gray-900 dark:hover:text-white pl-4 pr-3 py-2 rounded-lg <?php if($active): ?> bg-linear-to-r from-violet-500/[0.12] dark:from-violet-500/[0.24] to-violet-500/[0.04] <?php endif; ?>"
       href="#"
       @click.prevent="if (window.innerWidth >= 640 && window.innerWidth < 1024 && !sidebarOpen) { $dispatch('open-sidebar'); } else { expanded = !expanded; }">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($icon): ?>
                <?php if (isset($component)) { $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '8def1252668913628243c4d363bee1ef::icon','data' => ['name' => $icon,'class' => 'shrink-0 '.e($active ? 'text-violet-500' : 'text-' . $color . '-500').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('core::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'class' => 'shrink-0 '.e($active ? 'text-violet-500' : 'text-' . $color . '-500').'']); ?>
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
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <span class="text-sm font-medium <?php if($icon): ?> ml-4 <?php endif; ?> duration-200"
                      :class="{ 'sm:opacity-0 lg:opacity-100': !sidebarOpen, 'opacity-100': sidebarOpen }"><?php echo e($title); ?></span>
            </div>
            <div class="flex shrink-0 ml-2 duration-200"
                 :class="{ 'sm:opacity-0 lg:opacity-100': !sidebarOpen, 'opacity-100': sidebarOpen }">
                <svg class="w-3 h-3 shrink-0 fill-current text-gray-400 dark:text-gray-500 transition-transform duration-200" :class="{ 'rotate-180': expanded }" viewBox="0 0 12 12">
                    <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z" />
                </svg>
            </div>
        </div>
    </a>
    <div :class="{ 'sm:hidden lg:block': !sidebarOpen, 'block': sidebarOpen }" x-show="expanded" x-cloak>
        <ul class="pl-10 mt-1 space-y-1">
            <?php echo e($slot); ?>

        </ul>
    </div>
</li>
<?php /**PATH /Users/snider/Code/lab/core-php/packages/core-php/src/Core/Front/Admin/Blade/components/nav-menu.blade.php ENDPATH**/ ?>