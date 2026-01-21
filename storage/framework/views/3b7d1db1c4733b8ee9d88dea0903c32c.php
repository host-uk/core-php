<ul class="space-y-1">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($item['divider'])): ?>
            
            <li class="py-2">
                <hr class="border-gray-200 dark:border-gray-700" />
            </li>
        <?php elseif(!empty($item['children'])): ?>
            
            <li>
                <?php if (isset($component)) { $__componentOriginal682b96ac110668cc64da2afbea515f52 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal682b96ac110668cc64da2afbea515f52 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.nav-menu','data' => ['title' => $item['label'],'icon' => $item['icon'] ?? null,'active' => $item['active'] ?? false,'color' => $item['color'] ?? 'gray']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::nav-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['label']),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['icon'] ?? null),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['active'] ?? false),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['color'] ?? 'gray')]); ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $item['children']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($child['section'])): ?>
                            
                            <?php
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
                            ?>
                            <li class="pt-3 pb-1 first:pt-1 flex items-center gap-2">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!empty($child['icon'])): ?>
                                    <?php if (isset($component)) { $__componentOriginalddaaa69e63e341eb9a1697dbf04d7aac = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalddaaa69e63e341eb9a1697dbf04d7aac = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => '8def1252668913628243c4d363bee1ef::icon','data' => ['name' => $child['icon'],'class' => 'size-4 shrink-0 '.e($sectionIconClass).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('core::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($child['icon']),'class' => 'size-4 shrink-0 '.e($sectionIconClass).'']); ?>
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
                                <span class="text-xs font-semibold uppercase tracking-wider <?php echo e($sectionIconClass); ?>">
                                    <?php echo e($child['section']); ?>

                                </span>
                            </li>
                        <?php else: ?>
                            <?php if (isset($component)) { $__componentOriginalb4eba500ec155d6509aada51e9da8cc6 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb4eba500ec155d6509aada51e9da8cc6 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.nav-link','data' => ['href' => $child['href'] ?? '#','active' => $child['active'] ?? false,'badge' => $child['badge'] ?? null,'icon' => $child['icon'] ?? null,'color' => $child['color'] ?? null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::nav-link'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($child['href'] ?? '#'),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($child['active'] ?? false),'badge' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($child['badge'] ?? null),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($child['icon'] ?? null),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($child['color'] ?? null)]); ?><?php echo e($child['label']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb4eba500ec155d6509aada51e9da8cc6)): ?>
<?php $attributes = $__attributesOriginalb4eba500ec155d6509aada51e9da8cc6; ?>
<?php unset($__attributesOriginalb4eba500ec155d6509aada51e9da8cc6); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb4eba500ec155d6509aada51e9da8cc6)): ?>
<?php $component = $__componentOriginalb4eba500ec155d6509aada51e9da8cc6; ?>
<?php unset($__componentOriginalb4eba500ec155d6509aada51e9da8cc6); ?>
<?php endif; ?>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal682b96ac110668cc64da2afbea515f52)): ?>
<?php $attributes = $__attributesOriginal682b96ac110668cc64da2afbea515f52; ?>
<?php unset($__attributesOriginal682b96ac110668cc64da2afbea515f52); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal682b96ac110668cc64da2afbea515f52)): ?>
<?php $component = $__componentOriginal682b96ac110668cc64da2afbea515f52; ?>
<?php unset($__componentOriginal682b96ac110668cc64da2afbea515f52); ?>
<?php endif; ?>
            </li>
        <?php else: ?>
            
            <li>
                <?php if (isset($component)) { $__componentOriginalbc8510acb3a8c4c112b7b802c754914c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbc8510acb3a8c4c112b7b802c754914c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.nav-item','data' => ['href' => $item['href'] ?? '#','icon' => $item['icon'] ?? null,'active' => $item['active'] ?? false,'color' => $item['color'] ?? 'gray','badge' => $item['badge'] ?? null]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::nav-item'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['href'] ?? '#'),'icon' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['icon'] ?? null),'active' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['active'] ?? false),'color' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['color'] ?? 'gray'),'badge' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['badge'] ?? null)]); ?><?php echo e($item['label']); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbc8510acb3a8c4c112b7b802c754914c)): ?>
<?php $attributes = $__attributesOriginalbc8510acb3a8c4c112b7b802c754914c; ?>
<?php unset($__attributesOriginalbc8510acb3a8c4c112b7b802c754914c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbc8510acb3a8c4c112b7b802c754914c)): ?>
<?php $component = $__componentOriginalbc8510acb3a8c4c112b7b802c754914c; ?>
<?php unset($__componentOriginalbc8510acb3a8c4c112b7b802c754914c); ?>
<?php endif; ?>
            </li>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</ul>
<?php /**PATH /Users/snider/Code/lab/core-php/packages/core-php/src/Core/Front/Admin/Blade/components/sidemenu.blade.php ENDPATH**/ ?>