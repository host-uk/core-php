<?php

use Core\Mod\Mcp\View\Modal\Admin\ApiKeyManager;
use Core\Mod\Mcp\View\Modal\Admin\Playground;
use Core\Mod\Mcp\View\Modal\Admin\RequestLog;
use Core\Website\Mcp\Controllers\McpRegistryController;
use Core\Website\Mcp\View\Modal\Dashboard;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MCP Admin Routes
|--------------------------------------------------------------------------
|
| Protected routes for MCP portal management.
| Requires authentication via admin middleware.
|
*/

Route::prefix('mcp')->name('mcp.')->group(function () {
    // Dashboard (workspace MCP usage overview)
    Route::get('dashboard', Dashboard::class)
        ->name('dashboard');

    // API key management
    Route::get('keys', ApiKeyManager::class)
        ->name('keys');

    // Interactive playground
    Route::get('playground', Playground::class)
        ->name('playground');

    // Request log for debugging
    Route::get('logs', RequestLog::class)
        ->name('logs');

    // Analytics endpoints
    Route::get('servers/{id}/analytics', [McpRegistryController::class, 'analytics'])
        ->name('servers.analytics');
});
