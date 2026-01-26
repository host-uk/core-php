<?php

declare(strict_types=1);

/**
 * Tenant Module API Routes
 *
 * REST API for workspace management.
 * Supports both session auth and API key auth.
 */

use Illuminate\Support\Facades\Route;
use Core\Mod\Api\Controllers\WorkspaceController;

/*
|--------------------------------------------------------------------------
| Workspaces API (Auth Required)
|--------------------------------------------------------------------------
|
| Workspace CRUD and switching.
| Session-based authentication.
|
*/

Route::middleware('auth')->prefix('workspaces')->name('api.workspaces.')->group(function () {
    Route::get('/', [WorkspaceController::class, 'index'])
        ->name('index');
    Route::get('/current', [WorkspaceController::class, 'current'])
        ->name('current');
    Route::post('/', [WorkspaceController::class, 'store'])
        ->name('store');
    Route::get('/{workspace}', [WorkspaceController::class, 'show'])
        ->name('show');
    Route::put('/{workspace}', [WorkspaceController::class, 'update'])
        ->name('update');
    Route::delete('/{workspace}', [WorkspaceController::class, 'destroy'])
        ->name('destroy');
    Route::post('/{workspace}/switch', [WorkspaceController::class, 'switch'])
        ->name('switch');
});

/*
|--------------------------------------------------------------------------
| Workspaces API (API Key Auth)
|--------------------------------------------------------------------------
|
| Read-only workspace access via API key.
| Use Authorization: Bearer hk_xxx header.
|
*/

Route::middleware(['api.auth', 'api.scope.enforce'])->prefix('workspaces')->name('api.key.workspaces.')->group(function () {
    // Scope enforcement: GET=read (all routes here are read-only)
    Route::get('/', [WorkspaceController::class, 'index'])->name('index');
    Route::get('/current', [WorkspaceController::class, 'current'])->name('current');
    Route::get('/{workspace}', [WorkspaceController::class, 'show'])->name('show');
});
