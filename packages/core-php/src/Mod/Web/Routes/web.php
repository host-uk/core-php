<?php

declare(strict_types=1);

/**
 * Bio Module Web Routes
 *
 * Routes for BioHost bio pages, management, and public rendering.
 */

use Core\Mod\Web\View\Modal\Admin\Analytics as BioAnalytics;
use Core\Mod\Web\View\Modal\Admin\AnalyticsOverview as BioAnalyticsOverview;
use Core\Mod\Web\View\Modal\Admin\CreateShortLink as BioCreateShortLink;
use Core\Mod\Web\View\Modal\Admin\CreateStaticPage as BioCreateStaticPage;
use Core\Mod\Web\View\Modal\Admin\DomainManager as BioDomainManager;
use Core\Mod\Web\View\Modal\Admin\Editor as BioEditor;
use Core\Mod\Web\View\Modal\Admin\EditStaticPage as BioEditStaticPage;
use Core\Mod\Web\View\Modal\Admin\ImageOptimisationStats;
use Core\Mod\Web\View\Modal\Admin\Index as BioIndex;
use Core\Mod\Web\View\Modal\Admin\NotificationHandlerManager as BioNotificationHandlerManager;
use Core\Mod\Web\View\Modal\Admin\PixelManager as BioPixelManager;
use Core\Mod\Web\View\Modal\Admin\ProjectManager as BioProjectManager;
use Core\Mod\Web\View\Modal\Admin\QrCodeEditor as BioQrCodeEditor;
use Core\Mod\Web\View\Modal\Admin\ShortLinkIndex as BioShortLinkIndex;
use Core\Mod\Web\View\Modal\Admin\TemplateManager;
use Core\Mod\Web\View\Modal\Admin\ThemeGallery as BioThemeGallery;
use Core\Mod\Web\View\Modal\Admin\ThemeGalleryManager;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Theme Gallery (No Auth Required)
|--------------------------------------------------------------------------
|
| Browse BioHost themes without login. Accessible from main marketing site.
|
*/

Route::get('/themes', BioThemeGallery::class)->name('themes.gallery');

/*
|--------------------------------------------------------------------------
| Hub Routes - Bio Management (Auth Required)
|--------------------------------------------------------------------------
|
| These routes are for authenticated users managing their bio pages.
| Prefix: /hub/bio
| Middleware: web, admin.domain, auth
|
*/

Route::prefix('hub')->middleware(['web', 'admin.domain', 'auth'])->group(function () {
    // Admin routes (Hades tier only)
    Route::prefix('admin/bio')->name('hub.admin.bio.')->group(function () {
        Route::get('/templates', TemplateManager::class)->name('templates');
        Route::get('/themes', ThemeGalleryManager::class)->name('themes');
        Route::get('/image-optimisation', ImageOptimisationStats::class)->name('image-optimisation');
    });

    // Legacy redirect
    Route::get('/bio/templates/manage', function () {
        return redirect()->route('hub.admin.bio.templates');
    })->name('hub.bio.templates.manage');

    // Bio management routes
    Route::name('hub.')->group(function () {
        Route::get('/bio', BioIndex::class)->name('bio.index');
        Route::get('/bio/shortlinks', BioShortLinkIndex::class)->name('bio.shortlinks');
        Route::get('/bio/projects', BioProjectManager::class)->name('bio.projects');
        Route::get('/bio/pixels', BioPixelManager::class)->name('bio.pixels');
        Route::get('/bio/domains', BioDomainManager::class)->name('bio.domains');
        Route::get('/bio/themes', BioThemeGallery::class)->name('bio.themes');
        Route::get('/bio/templates', \Core\Mod\Web\View\Modal\Admin\TemplateGallery::class)->name('bio.templates');
        Route::get('/bio/analytics', BioAnalyticsOverview::class)->name('bio.analytics.overview');

        // Create routes
        Route::get('/bio/short-link/create', BioCreateShortLink::class)->name('bio.shortlink.create');
        Route::get('/bio/static/create', BioCreateStaticPage::class)->name('bio.static.create');
        Route::get('/bio/file/create', \Core\Mod\Web\View\Modal\Admin\CreateFileLink::class)->name('bio.file.create');
        Route::get('/bio/vcard/create', \Core\Mod\Web\View\Modal\Admin\CreateVcard::class)->name('bio.vcard.create');
        Route::get('/bio/event/create', \Core\Mod\Web\View\Modal\Admin\CreateEvent::class)->name('bio.event.create');

        // Edit routes (require ID)
        Route::get('/bio/{id}/edit', BioEditor::class)->name('bio.edit')
            ->where('id', '[0-9]+');
        Route::get('/bio/{id}/edit-static', BioEditStaticPage::class)->name('bio.static.edit')
            ->where('id', '[0-9]+');
        Route::get('/bio/{id}/analytics', BioAnalytics::class)->name('bio.analytics')
            ->where('id', '[0-9]+');
        Route::get('/bio/{id}/submissions', \Core\Mod\Web\View\Modal\Admin\SubmissionsManager::class)->name('bio.submissions')
            ->where('id', '[0-9]+');
        Route::get('/bio/{id}/notifications', BioNotificationHandlerManager::class)->name('bio.notifications')
            ->where('id', '[0-9]+');
        Route::get('/bio/{id}/qr', BioQrCodeEditor::class)->name('bio.qr')
            ->where('id', '[0-9]+');
        Route::get('/bio/{id}/qr/download', [\Core\Mod\Web\Controllers\Web\QrCodeController::class, 'download'])
            ->name('bio.qr.download')
            ->where('id', '[0-9]+');
        Route::get('/bio/{id}/qr/preview', [\Core\Mod\Web\Controllers\Web\QrCodeController::class, 'preview'])
            ->name('bio.qr.preview')
            ->where('id', '[0-9]+');
        Route::get('/bio/{id}/pwa', \Core\Mod\Web\View\Modal\Admin\PwaEditor::class)->name('bio.pwa')
            ->where('id', '[0-9]+');
        Route::get('/bio/{id}/ai-assistant', \Core\Mod\Web\View\Modal\Admin\AiAssistant::class)->name('bio.ai-assistant')
            ->where('id', '[0-9]+');
    });
});

/*
|--------------------------------------------------------------------------
| Public Bio Routes
|--------------------------------------------------------------------------
|
| Bio catch-all routes (/{url}) are registered in the lt.hn Mod module
| to prevent them from capturing routes on the main marketing site.
|
| See: app/Mod/LtHn/Routes/web.php
|
*/
