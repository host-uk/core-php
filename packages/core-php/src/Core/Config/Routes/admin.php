<?php

declare(strict_types=1);

use Core\Config\View\Modal\Admin\ConfigPanel;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/config', ConfigPanel::class)->name('admin.config');
    });
