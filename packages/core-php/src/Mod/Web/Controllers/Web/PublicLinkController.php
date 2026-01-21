<?php

namespace Core\Mod\Web\Controllers\Web;

use Core\Front\Controller;
use Core\Mod\Web\Jobs\TrackBioLinkClick as TrackClick;
use Core\Mod\Web\Models\Domain;
use Core\Mod\Web\Models\Page;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicLinkController extends Controller
{
    /**
     * Render a public biolink page or redirect a short link.
     */
    public function show(Request $request, string $url): View|Response
    {
        // Check for custom domain
        $domain = Domain::where('host', $request->getHost())
            ->where('is_enabled', true)
            ->first();

        // Find the biolink
        $biolink = Page::query()
            ->where('url', $url)
            ->where('domain_id', $domain?->id)
            ->active()
            ->first();

        if (! $biolink) {
            // Check for custom 404 URL on domain
            if ($domain?->custom_not_found_url) {
                return redirect($domain->custom_not_found_url);
            }

            abort(404);
        }

        // Dispatch click tracking job
        TrackClick::dispatch($biolink->id, null, $request);

        // Handle short links (redirect)
        if ($biolink->type === 'link' && $biolink->location_url) {
            return redirect($biolink->location_url, 301);
        }

        // Render biolink page
        $biolink->load(['blocks' => fn ($q) => $q->active()->orderBy('order')]);

        return view('webpage::web.public.render', [
            'biolink' => $biolink,
            'settings' => $biolink->settings ?? new \ArrayObject,
            'blocks' => $biolink->blocks,
        ]);
    }

    /**
     * Handle custom domain index page.
     */
    public function index(Request $request): View|Response
    {
        $domain = Domain::where('host', $request->getHost())
            ->where('is_enabled', true)
            ->first();

        if (! $domain) {
            abort(404);
        }

        // If domain has exclusive biolink, show it
        if ($domain->isExclusive() && $domain->exclusiveLink) {
            return $this->show($request, $domain->exclusiveLink->url);
        }

        // Redirect to custom index URL
        if ($domain->custom_index_url) {
            return redirect($domain->custom_index_url);
        }

        abort(404);
    }

    /**
     * Track a block click via AJAX.
     */
    public function trackClick(Request $request, Page $biolink): Response
    {
        $request->validate([
            'block_id' => ['nullable', 'integer'],
        ]);

        $block = null;
        if ($request->block_id) {
            $block = $biolink->blocks()->find($request->block_id);
            $block?->recordClick();
        }

        // Record page click as well
        $biolink->recordClick();

        TrackClick::dispatch($biolink->id, $block?->id, $request);

        return response()->json(['ok' => true]);
    }
}
