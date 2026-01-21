<?php

declare(strict_types=1);

/**
 * Bio Module API Routes
 *
 * REST API for biolinks, blocks, short links, QR codes, and analytics.
 * Supports both session auth and API key auth.
 */

use Illuminate\Support\Facades\Route;
use Core\Mod\Web\Controllers\Api\AnalyticsController as BioAnalyticsController;
use Core\Mod\Web\Controllers\Api\BlockController as BioBlockController;
use Core\Mod\Web\Controllers\Api\PageController as BioPageController;
use Core\Mod\Web\Controllers\Api\QrCodeController;
use Core\Mod\Web\Controllers\Api\ShortLinkController;
use Core\Mod\Web\Controllers\Web\SubmissionController;
use Website\LtHn\Controllers\PublicBioPageController;

/*
|--------------------------------------------------------------------------
| Public Bio Endpoints (No Auth - Rate Limited)
|--------------------------------------------------------------------------
|
| Public endpoints for click tracking, form submissions, and PWA.
| CDN pass-through, rate limited to prevent abuse.
|
*/

Route::middleware('throttle:120,1')->prefix('bio')->group(function () {
    // Click tracking
    Route::post('/click', [PublicBioPageController::class, 'trackClick'])
        ->name('api.bio.click');

    // Form submissions (stricter rate limit - 10 per minute)
    Route::post('/submit', [SubmissionController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('api.bio.submit');

    // Push notification subscription
    Route::post('/push/subscribe', [PublicBioPageController::class, 'pushSubscribe'])
        ->name('api.bio.push.subscribe');
    Route::post('/push/unsubscribe', [PublicBioPageController::class, 'pushUnsubscribe'])
        ->name('api.bio.push.unsubscribe');

    // PWA install tracking
    Route::post('/pwa/install', [PublicBioPageController::class, 'trackPwaInstall'])
        ->name('api.bio.pwa.install');
});

/*
|--------------------------------------------------------------------------
| Bio API (Auth Required)
|--------------------------------------------------------------------------
|
| Full REST API for managing bio pages, blocks, and short links.
| Session-based authentication.
|
*/

Route::middleware('auth')->group(function () {
    // ─────────────────────────────────────────────────────────────────────
    // BioLinks API
    // ─────────────────────────────────────────────────────────────────────

    Route::prefix('bio')->name('api.bio.')->group(function () {
        // Bio page CRUD
        Route::get('/', [BioPageController::class, 'index'])
            ->name('index');
        Route::post('/', [BioPageController::class, 'store'])
            ->name('store');
        Route::get('/{biolink}', [BioPageController::class, 'show'])
            ->name('show');
        Route::put('/{biolink}', [BioPageController::class, 'update'])
            ->name('update');
        Route::delete('/{biolink}', [BioPageController::class, 'destroy'])
            ->name('destroy');

        // Block routes (nested under bio page)
        Route::get('/{biolink}/blocks', [BioBlockController::class, 'index'])
            ->name('blocks.index');
        Route::post('/{biolink}/blocks', [BioBlockController::class, 'store'])
            ->name('blocks.store');
        Route::post('/{biolink}/blocks/reorder', [BioBlockController::class, 'reorder'])
            ->name('blocks.reorder');
        Route::post('/{biolink}/blocks/{block}/duplicate', [BioBlockController::class, 'duplicate'])
            ->name('blocks.duplicate');

        // Analytics routes (nested under bio page)
        Route::get('/{biolink}/analytics', [BioAnalyticsController::class, 'summary'])
            ->name('analytics.summary');
        Route::get('/{biolink}/analytics/clicks', [BioAnalyticsController::class, 'clicks'])
            ->name('analytics.clicks');
        Route::get('/{biolink}/analytics/geo', [BioAnalyticsController::class, 'geo'])
            ->name('analytics.geo');
        Route::get('/{biolink}/analytics/devices', [BioAnalyticsController::class, 'devices'])
            ->name('analytics.devices');
        Route::get('/{biolink}/analytics/referrers', [BioAnalyticsController::class, 'referrers'])
            ->name('analytics.referrers');
        Route::get('/{biolink}/analytics/utm', [BioAnalyticsController::class, 'utm'])
            ->name('analytics.utm');
        Route::get('/{biolink}/analytics/blocks', [BioAnalyticsController::class, 'blocks'])
            ->name('analytics.blocks');
    });

    // Block routes (shallow - for operations on individual blocks)
    Route::prefix('blocks')->name('api.blocks.')->group(function () {
        Route::get('/{block}', [BioBlockController::class, 'show'])
            ->name('show');
        Route::put('/{block}', [BioBlockController::class, 'update'])
            ->name('update');
        Route::delete('/{block}', [BioBlockController::class, 'destroy'])
            ->name('destroy');
    });

    // ─────────────────────────────────────────────────────────────────────
    // Short Links API
    // ─────────────────────────────────────────────────────────────────────

    Route::prefix('shortlinks')->name('api.shortlinks.')->group(function () {
        Route::get('/', [ShortLinkController::class, 'index'])
            ->name('index');
        Route::post('/', [ShortLinkController::class, 'store'])
            ->name('store');
        Route::get('/{shortlink}', [ShortLinkController::class, 'show'])
            ->name('show');
        Route::put('/{shortlink}', [ShortLinkController::class, 'update'])
            ->name('update');
        Route::delete('/{shortlink}', [ShortLinkController::class, 'destroy'])
            ->name('destroy');
    });

    // ─────────────────────────────────────────────────────────────────────
    // QR Code API
    // ─────────────────────────────────────────────────────────────────────

    Route::prefix('qr')->name('api.qr.')->group(function () {
        // Get available options
        Route::get('/options', [QrCodeController::class, 'options'])
            ->name('options');

        // Generate QR for any URL
        Route::post('/generate', [QrCodeController::class, 'generate'])
            ->name('generate');
        Route::post('/download', [QrCodeController::class, 'generateDownload'])
            ->name('download');
    });

    // QR code for specific biolink (nested under bio)
    Route::prefix('bio')->name('api.bio.')->group(function () {
        Route::get('/{biolink}/qr', [QrCodeController::class, 'show'])
            ->name('qr');
        Route::get('/{biolink}/qr/download', [QrCodeController::class, 'download'])
            ->name('qr.download');
    });

});

/*
|--------------------------------------------------------------------------
| Bio API (API Key Auth)
|--------------------------------------------------------------------------
|
| Same endpoints as above but authenticated via API key.
| Use Authorization: Bearer hk_xxx header.
|
*/

Route::middleware('api.auth')->group(function () {
    // All routes here mirror the session auth routes
    // The controllers use ResolvesWorkspace trait to get workspace from either auth type

    Route::prefix('bio')->name('api.key.bio.')->group(function () {
        Route::get('/', [BioPageController::class, 'index'])->name('index');
        Route::post('/', [BioPageController::class, 'store'])->name('store');
        Route::get('/{biolink}', [BioPageController::class, 'show'])->name('show');
        Route::put('/{biolink}', [BioPageController::class, 'update'])->name('update');
        Route::delete('/{biolink}', [BioPageController::class, 'destroy'])->name('destroy');

        // Blocks
        Route::get('/{biolink}/blocks', [BioBlockController::class, 'index'])->name('blocks.index');
        Route::post('/{biolink}/blocks', [BioBlockController::class, 'store'])->name('blocks.store');
        Route::post('/{biolink}/blocks/reorder', [BioBlockController::class, 'reorder'])->name('blocks.reorder');

        // Analytics
        Route::get('/{biolink}/analytics', [BioAnalyticsController::class, 'summary'])->name('analytics.summary');

        // QR
        Route::get('/{biolink}/qr', [QrCodeController::class, 'show'])->name('qr');
        Route::get('/{biolink}/qr/download', [QrCodeController::class, 'download'])->name('qr.download');
    });

    Route::prefix('blocks')->name('api.key.blocks.')->group(function () {
        Route::get('/{block}', [BioBlockController::class, 'show'])->name('show');
        Route::put('/{block}', [BioBlockController::class, 'update'])->name('update');
        Route::delete('/{block}', [BioBlockController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('shortlinks')->name('api.key.shortlinks.')->group(function () {
        Route::get('/', [ShortLinkController::class, 'index'])->name('index');
        Route::post('/', [ShortLinkController::class, 'store'])->name('store');
        Route::get('/{shortlink}', [ShortLinkController::class, 'show'])->name('show');
        Route::put('/{shortlink}', [ShortLinkController::class, 'update'])->name('update');
        Route::delete('/{shortlink}', [ShortLinkController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('qr')->name('api.key.qr.')->group(function () {
        Route::get('/options', [QrCodeController::class, 'options'])->name('options');
        Route::post('/generate', [QrCodeController::class, 'generate'])->name('generate');
        Route::post('/download', [QrCodeController::class, 'generateDownload'])->name('download');
    });
});
