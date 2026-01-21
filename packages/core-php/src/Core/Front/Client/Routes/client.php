<?php

declare(strict_types=1);

use Core\Front\Client\View\Dashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client Routes
|--------------------------------------------------------------------------
|
| Routes for namespace owners managing their personal workspace.
| Uses 'client' middleware group (authenticated, namespace owner).
|
*/

Route::middleware('client')->group(function () {
    // Dashboard
    Route::get('/dashboard', Dashboard::class)->name('client.dashboard');

    // TODO: Bio editor routes
    // TODO: Analytics routes
    // TODO: Settings routes
    // TODO: Boost purchase routes
});
