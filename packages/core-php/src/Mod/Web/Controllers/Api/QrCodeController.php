<?php

declare(strict_types=1);

namespace Core\Mod\Web\Controllers\Api;

use Core\Front\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Core\Mod\Api\Controllers\Concerns\HasApiResponses;
use Core\Mod\Api\Controllers\Concerns\ResolvesWorkspace;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Resources\QrCodeResource;
use Core\Mod\Web\Services\QrCodeService;

/**
 * QR Code API controller.
 *
 * Generate QR codes for biolinks and arbitrary URLs.
 */
class QrCodeController extends Controller
{
    use HasApiResponses;
    use ResolvesWorkspace;

    public function __construct(
        protected QrCodeService $qrService
    ) {}

    /**
     * Generate QR code for a biolink.
     *
     * GET /api/v1/bio/{biolink}/qr
     */
    public function show(Request $request, Page $biolink): QrCodeResource|JsonResponse
    {
        $workspace = $this->resolveWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        $options = $this->parseQrOptions($request);
        $image = $this->qrService->generateDataUri($biolink, $options);

        return new QrCodeResource([
            'url' => $biolink->full_url,
            'format' => $options['format'],
            'size' => $options['size'],
            'image' => $image,
            'settings' => $options,
            'biolink_id' => $biolink->id,
            'biolink_url' => $biolink->url,
        ]);
    }

    /**
     * Download QR code for a biolink as a file.
     *
     * GET /api/v1/bio/{biolink}/qr/download
     */
    public function download(Request $request, Page $biolink): Response|JsonResponse
    {
        $workspace = $this->resolveWorkspace($request);

        if (! $workspace) {
            return $this->noWorkspaceResponse();
        }

        // Verify biolink belongs to workspace
        if ($biolink->workspace_id !== $workspace->id) {
            return $this->notFoundResponse('Biolink');
        }

        $options = $this->parseQrOptions($request);
        $options['return_base64'] = false;
        $format = $options['format'];

        $imageData = $this->qrService->generate($biolink, $options);
        $filename = "qr-{$biolink->url}.{$format}";
        $mimeType = $format === 'svg' ? 'image/svg+xml' : 'image/png';

        return response($imageData)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Generate QR code for an arbitrary URL.
     *
     * POST /api/v1/qr/generate
     */
    public function generate(Request $request): QrCodeResource|JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'format' => 'nullable|in:png,svg',
            'size' => 'nullable|integer|min:100|max:1000',
            'foreground_colour' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_colour' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ecc_level' => 'nullable|in:L,M,Q,H',
            'module_style' => 'nullable|in:square,rounded,dots',
            'logo_size' => 'nullable|integer|min:10|max:30',
        ]);

        $options = $this->parseQrOptions($request);
        $options['return_base64'] = true;

        $image = $this->qrService->generateForUrl($validated['url'], $options);
        $format = $options['format'];
        $mimeType = $format === 'svg' ? 'image/svg+xml' : 'image/png';
        $dataUri = "data:{$mimeType};base64,{$image}";

        return new QrCodeResource([
            'url' => $validated['url'],
            'format' => $format,
            'size' => $options['size'],
            'image' => $dataUri,
            'settings' => $options,
        ]);
    }

    /**
     * Download QR code for an arbitrary URL.
     *
     * POST /api/v1/qr/download
     */
    public function generateDownload(Request $request): Response|JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url|max:2048',
            'filename' => 'nullable|string|max:100',
            'format' => 'nullable|in:png,svg',
            'size' => 'nullable|integer|min:100|max:1000',
            'foreground_colour' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_colour' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ecc_level' => 'nullable|in:L,M,Q,H',
            'module_style' => 'nullable|in:square,rounded,dots',
        ]);

        $options = $this->parseQrOptions($request);
        $options['return_base64'] = false;
        $format = $options['format'];

        $imageData = $this->qrService->generateForUrl($validated['url'], $options);

        // Generate filename from URL if not provided
        $filename = $validated['filename']
            ?? 'qr-'.substr(md5($validated['url']), 0, 8).".{$format}";

        $mimeType = $format === 'svg' ? 'image/svg+xml' : 'image/png';

        return response($imageData)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Get available QR code options and presets.
     *
     * GET /api/v1/qr/options
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'formats' => ['png', 'svg'],
            'sizes' => QrCodeService::SIZE_PRESETS,
            'ecc_levels' => QrCodeService::ERROR_CORRECTION_LEVELS,
            'module_styles' => QrCodeService::MODULE_STYLES,
            'defaults' => QrCodeService::getDefaultSettings(),
        ]);
    }

    /**
     * Parse QR code options from request.
     */
    protected function parseQrOptions(Request $request): array
    {
        return [
            'format' => $request->input('format', 'png'),
            'size' => (int) $request->input('size', 400),
            'foreground_colour' => $request->input('foreground_colour', '#000000'),
            'background_colour' => $request->input('background_colour', '#ffffff'),
            'ecc_level' => $request->input('ecc_level', 'M'),
            'module_style' => $request->input('module_style', 'square'),
            'logo_path' => $request->input('logo_path'),
            'logo_size' => (int) $request->input('logo_size', 20),
            'return_base64' => true,
        ];
    }
}
