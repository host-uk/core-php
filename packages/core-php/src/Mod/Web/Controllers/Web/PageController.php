<?php

namespace Core\Mod\Web\Controllers\Web;

use Core\Front\Controller;
use Core\Mod\Web\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PageController extends Controller
{
    /**
     * Display a listing of the user's bio.
     */
    public function index(Request $request): JsonResponse
    {
        $biolinks = $request->user()
            ->biolinks()
            ->with('project', 'domain')
            ->withCount('blocks')
            ->latest()
            ->paginate(25);

        return response()->json($biolinks);
    }

    /**
     * Store a newly created bio.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:256', 'regex:/^[a-z0-9\-_]+$/i'],
            'type' => ['sometimes', 'string', 'in:biolink,link,file,vcard,event'],
            'project_id' => ['nullable', 'exists:biolink_projects,id'],
            'domain_id' => ['nullable', 'exists:biolink_domains,id'],
            'location_url' => ['nullable', 'url', 'max:2048'],
            'settings' => ['nullable', 'array'],
        ]);

        // Ensure unique URL for domain
        $exists = Page::where('url', $validated['url'])
            ->where('domain_id', $validated['domain_id'] ?? null)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This URL is already taken.',
                'errors' => ['url' => ['This URL is already taken.']],
            ], 422);
        }

        $biolink = $request->user()->biolinks()->create([
            'url' => Str::lower($validated['url']),
            'type' => $validated['type'] ?? 'biolink',
            'project_id' => $validated['project_id'] ?? null,
            'domain_id' => $validated['domain_id'] ?? null,
            'location_url' => $validated['location_url'] ?? null,
            'settings' => $validated['settings'] ?? [],
        ]);

        return response()->json($biolink, 201);
    }

    /**
     * Display the specified bio.
     */
    public function show(Request $request, Page $biolink): JsonResponse
    {
        $this->authorizeUser($request, $biolink);

        $biolink->load('blocks', 'project', 'domain');

        return response()->json([
            'biolink' => $biolink,
            'block_types' => config('bio.block_types'),
        ]);
    }

    /**
     * Update the specified bio.
     */
    public function update(Request $request, Page $biolink): JsonResponse
    {
        $this->authorizeUser($request, $biolink);

        $validated = $request->validate([
            'url' => ['sometimes', 'string', 'max:256', 'regex:/^[a-z0-9\-_]+$/i'],
            'project_id' => ['nullable', 'exists:biolink_projects,id'],
            'domain_id' => ['nullable', 'exists:biolink_domains,id'],
            'location_url' => ['nullable', 'url', 'max:2048'],
            'settings' => ['nullable', 'array'],
            'pixels' => ['nullable', 'array'],
            'is_enabled' => ['sometimes', 'boolean'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        // Check unique URL if changed
        if (isset($validated['url']) && $validated['url'] !== $biolink->url) {
            $domainId = $validated['domain_id'] ?? $biolink->domain_id;
            $exists = Page::where('url', $validated['url'])
                ->where('domain_id', $domainId)
                ->where('id', '!=', $biolink->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'This URL is already taken.',
                    'errors' => ['url' => ['This URL is already taken.']],
                ], 422);
            }

            $validated['url'] = Str::lower($validated['url']);
        }

        $biolink->update($validated);

        return response()->json($biolink);
    }

    /**
     * Remove the specified bio.
     */
    public function destroy(Request $request, Page $biolink): JsonResponse
    {
        $this->authorizeUser($request, $biolink);

        $biolink->delete();

        return response()->json(['message' => 'Biolink deleted']);
    }

    /**
     * Get analytics for a bio.
     */
    public function stats(Request $request, Page $biolink): JsonResponse
    {
        $this->authorizeUser($request, $biolink);

        $period = $request->query('period', '7d');
        $startDate = match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            default => now()->subDays(7),
        };

        return response()->json([
            'total_clicks' => $biolink->clicks,
            'period_clicks' => $biolink->clickRecords()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'unique_clicks' => $biolink->clickRecords()
                ->where('created_at', '>=', $startDate)
                ->where('is_unique', true)
                ->count(),
            'clicks_by_day' => $biolink->clickRecords()
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as clicks')
                ->groupByRaw('DATE(created_at)')
                ->orderBy('date')
                ->pluck('clicks', 'date'),
            'top_countries' => $biolink->clickRecords()
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('country_code')
                ->selectRaw('country_code, COUNT(*) as clicks')
                ->groupBy('country_code')
                ->orderByDesc('clicks')
                ->limit(10)
                ->pluck('clicks', 'country_code'),
            'devices' => $biolink->clickRecords()
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('device_type')
                ->selectRaw('device_type, COUNT(*) as clicks')
                ->groupBy('device_type')
                ->pluck('clicks', 'device_type'),
            'top_referrers' => $biolink->clickRecords()
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('referrer')
                ->selectRaw('referrer, COUNT(*) as clicks')
                ->groupBy('referrer')
                ->orderByDesc('clicks')
                ->limit(10)
                ->pluck('clicks', 'referrer'),
        ]);
    }

    /**
     * Ensure user owns the bio.
     */
    protected function authorizeUser(Request $request, Page $biolink): void
    {
        if ($biolink->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
