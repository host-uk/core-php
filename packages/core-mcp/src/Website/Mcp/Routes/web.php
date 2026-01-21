<?php

use Core\Website\Mcp\Controllers\McpRegistryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MCP Portal Routes (mcp.host.uk.com)
|--------------------------------------------------------------------------
|
| Public routes for the MCP server registry and documentation portal.
| These routes serve both human-readable docs and machine-readable JSON.
|
*/

$mcpDomain = config('mcp.domain', 'mcp.host.uk.com');

Route::domain($mcpDomain)->name('mcp.')->group(function () {
    // Agent discovery endpoint (always JSON)
    Route::get('.well-known/mcp-servers.json', [McpRegistryController::class, 'registry'])
        ->name('registry');

    // Landing page
    Route::get('/', [McpRegistryController::class, 'landing'])
        ->name('landing');

    // Server list (HTML/JSON based on Accept header)
    Route::get('servers', [McpRegistryController::class, 'index'])
        ->name('servers.index');

    // Server detail (supports .json extension)
    Route::get('servers/{id}', [McpRegistryController::class, 'show'])
        ->name('servers.show')
        ->where('id', '[a-z0-9-]+(?:\.json)?');

    // Connection config page
    Route::get('connect', [McpRegistryController::class, 'connect'])
        ->name('connect');

    // OpenAPI spec
    Route::get('openapi.json', [McpRegistryController::class, 'openapi'])->name('openapi.json');
    Route::get('openapi.yaml', [McpRegistryController::class, 'openapi'])->name('openapi.yaml');
});
