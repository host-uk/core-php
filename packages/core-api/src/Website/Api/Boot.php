<?php

declare(strict_types=1);

namespace Core\Website\Api;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class Boot extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerViews();
        $this->registerRoutes();
    }

    protected function registerViews(): void
    {
        View::addNamespace('api', __DIR__.'/View/Blade');
    }

    protected function registerRoutes(): void
    {
        Route::middleware('web')
            ->domain(request()->getHost())
            ->group(__DIR__.'/Routes/web.php');
    }
}
