<?php if (isset($component)) { $__componentOriginalef86c4657bdf6f09444c7ff0dcf7933f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalef86c4657bdf6f09444c7ff0dcf7933f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'admin::components.sidebar','data' => ['logo' => '/images/host-uk-raven.svg','logoText' => 'Host Hub','logoRoute' => route('hub.dashboard')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin::sidebar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['logo' => '/images/host-uk-raven.svg','logoText' => 'Host Hub','logoRoute' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('hub.dashboard'))]); ?>
    <?php if (isset($component)) { $__componentOriginaldf3c161788667d188981a4c6b1bfdb29 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldf3c161788667d188981a4c6b1bfdb29 = $attributes; } ?>
<?php $component = Core\Front\Admin\View\Components\Sidemenu::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('admin-sidemenu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Core\Front\Admin\View\Components\Sidemenu::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldf3c161788667d188981a4c6b1bfdb29)): ?>
<?php $attributes = $__attributesOriginaldf3c161788667d188981a4c6b1bfdb29; ?>
<?php unset($__attributesOriginaldf3c161788667d188981a4c6b1bfdb29); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldf3c161788667d188981a4c6b1bfdb29)): ?>
<?php $component = $__componentOriginaldf3c161788667d188981a4c6b1bfdb29; ?>
<?php unset($__componentOriginaldf3c161788667d188981a4c6b1bfdb29); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalef86c4657bdf6f09444c7ff0dcf7933f)): ?>
<?php $attributes = $__attributesOriginalef86c4657bdf6f09444c7ff0dcf7933f; ?>
<?php unset($__attributesOriginalef86c4657bdf6f09444c7ff0dcf7933f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalef86c4657bdf6f09444c7ff0dcf7933f)): ?>
<?php $component = $__componentOriginalef86c4657bdf6f09444c7ff0dcf7933f; ?>
<?php unset($__componentOriginalef86c4657bdf6f09444c7ff0dcf7933f); ?>
<?php endif; ?>

<?php /**PATH /Users/snider/Code/lab/core-php/packages/core-admin/src/Website/Hub/View/Blade/admin/components/sidebar.blade.php ENDPATH**/ ?>