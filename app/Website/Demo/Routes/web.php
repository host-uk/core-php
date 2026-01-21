<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Website\Demo\Middleware\EnsureInstalled;
use Website\Demo\View\Modal\Install;
use Website\Demo\View\Modal\Landing;
use Website\Demo\View\Modal\Login;

/*
|--------------------------------------------------------------------------
| Demo Mod Routes
|--------------------------------------------------------------------------
*/

// Install wizard (always accessible)
Route::get('/install', Install::class)->name('install');

// Routes that require installation
Route::middleware(EnsureInstalled::class)->group(function () {
    Route::get('/', Landing::class)->name('home');

    // Authentication routes
    Route::get('/login', Login::class)
        ->middleware('guest')
        ->name('login');

    Route::match(['get', 'post'], '/logout', function () {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->middleware('auth')->name('logout');
});
