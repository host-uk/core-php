<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Core\Mod\Api\Controllers\EntitlementApiController;
use Core\Mod\Api\Controllers\McpApiController;
use Core\Mod\Api\Controllers\SeoReportController;
use Core\Mod\Api\Controllers\UnifiedPixelController;
use Core\Mod\Mcp\Middleware\McpApiKeyAuth;

/*
|--------------------------------------------------------------------------
| Core API Routes
|--------------------------------------------------------------------------
|
| Core API routes for cross-cutting concerns: SEO, unified pixel tracking,
| MCP HTTP bridge, and entitlements.
|
*/

// ─────────────────────────────────────────────────────────────────────────────
// SEO Report Endpoints (authenticated)
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('seo')->group(function () {
    Route::post('/report', [SeoReportController::class, 'receive'])
        ->name('api.seo.report');

    Route::get('/issues/{workspace}', [SeoReportController::class, 'issues'])
        ->name('api.seo.issues');

    Route::post('/task/generate', [SeoReportController::class, 'generateTask'])
        ->name('api.seo.generate-task');
});

// ─────────────────────────────────────────────────────────────────────────────
// Unified Pixel API (public - high rate limit for tracking)
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware('throttle:300,1')->prefix('pixel')->group(function () {
    Route::get('/config', [UnifiedPixelController::class, 'config'])
        ->name('api.pixel.config');
    Route::post('/track', [UnifiedPixelController::class, 'track'])
        ->name('api.pixel.track');
});

// ─────────────────────────────────────────────────────────────────────────────
// Entitlements API (authenticated)
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware('auth')->prefix('entitlements')->group(function () {
    // Check feature access (for external apps)
    Route::get('/check', [EntitlementApiController::class, 'check'])
        ->name('api.entitlements.check');

    // Record usage (for external apps)
    Route::post('/usage', [EntitlementApiController::class, 'recordUsage'])
        ->name('api.entitlements.usage');

    // Get usage summary for current user's workspace
    Route::get('/summary', [EntitlementApiController::class, 'mySummary'])
        ->name('api.entitlements.summary');

    // Get usage summary for a specific workspace (admin)
    Route::get('/summary/{workspace}', [EntitlementApiController::class, 'summary'])
        ->name('api.entitlements.summary.workspace');
});

// ─────────────────────────────────────────────────────────────────────────────
// MCP HTTP Bridge (API key auth)
// ─────────────────────────────────────────────────────────────────────────────

Route::middleware(['throttle:120,1', McpApiKeyAuth::class])
    ->prefix('mcp')
    ->name('api.mcp.')
    ->group(function () {
        // Server discovery
        Route::get('/servers', [McpApiController::class, 'servers'])
            ->name('servers');
        Route::get('/servers/{id}', [McpApiController::class, 'server'])
            ->name('servers.show');
        Route::get('/servers/{id}/tools', [McpApiController::class, 'tools'])
            ->name('servers.tools');

        // Tool execution
        Route::post('/tools/call', [McpApiController::class, 'callTool'])
            ->name('tools.call');

        // Resource access
        Route::get('/resources/{uri}', [McpApiController::class, 'resource'])
            ->where('uri', '.*')
            ->name('resources.show');
    });
