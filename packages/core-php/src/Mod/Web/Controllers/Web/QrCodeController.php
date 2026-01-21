<?php

namespace Core\Mod\Web\Controllers\Web;

use Core\Front\Controller;
use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller for QR code generation and downloads.
 */
class QrCodeController extends Controller
{
    public function __construct(
        protected QrCodeService $qrCodeService
    ) {}

    /**
     * Download QR code in specified format.
     */
    public function download(Request $request, int $id): Response|StreamedResponse
    {
        $biolink = Page::where('user_id', Auth::id())->findOrFail($id);

        $format = $request->query('format', 'png');

        if (! in_array($format, ['png', 'svg'])) {
            abort(400, 'Invalid format. Supported formats: png, svg');
        }

        // Get QR settings from biolink or use defaults
        $qrSettings = $biolink->getSetting('qr_code', []);
        $defaults = QrCodeService::getDefaultSettings();

        $options = [
            'foreground_colour' => $qrSettings['foreground_colour'] ?? $defaults['foreground_colour'],
            'background_colour' => $qrSettings['background_colour'] ?? $defaults['background_colour'],
            'size' => $qrSettings['size'] ?? $defaults['size'],
            'ecc_level' => $qrSettings['ecc_level'] ?? $defaults['ecc_level'],
            'module_style' => $qrSettings['module_style'] ?? $defaults['module_style'],
            'logo_path' => $qrSettings['logo_path'] ?? null,
            'logo_size' => $qrSettings['logo_size'] ?? $defaults['logo_size'],
            'format' => $format,
            'return_base64' => false,
        ];

        $content = $this->qrCodeService->generate($biolink, $options);

        $filename = "qr-{$biolink->url}.{$format}";
        $mimeType = $format === 'svg' ? 'image/svg+xml' : 'image/png';

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Content-Length', strlen($content));
    }

    /**
     * Preview QR code in browser.
     */
    public function preview(Request $request, int $id): Response
    {
        $biolink = Page::where('user_id', Auth::id())->findOrFail($id);

        // Get QR settings from biolink or use defaults
        $qrSettings = $biolink->getSetting('qr_code', []);
        $defaults = QrCodeService::getDefaultSettings();

        $options = [
            'foreground_colour' => $qrSettings['foreground_colour'] ?? $defaults['foreground_colour'],
            'background_colour' => $qrSettings['background_colour'] ?? $defaults['background_colour'],
            'size' => min($qrSettings['size'] ?? 300, 400), // Limit preview size
            'ecc_level' => $qrSettings['ecc_level'] ?? $defaults['ecc_level'],
            'module_style' => $qrSettings['module_style'] ?? $defaults['module_style'],
            'logo_path' => $qrSettings['logo_path'] ?? null,
            'logo_size' => $qrSettings['logo_size'] ?? $defaults['logo_size'],
            'format' => 'png',
            'return_base64' => false,
        ];

        $content = $this->qrCodeService->generate($biolink, $options);

        return response($content)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /**
     * Generate QR code with custom options (AJAX endpoint).
     */
    public function generate(Request $request, int $id): Response
    {
        $biolink = Page::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'foreground_colour' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_colour' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'size' => ['nullable', 'integer', 'min:100', 'max:1000'],
            'ecc_level' => ['nullable', 'string', 'in:L,M,Q,H'],
            'module_style' => ['nullable', 'string', 'in:square,rounded,dots'],
            'format' => ['nullable', 'string', 'in:png,svg'],
        ]);

        $defaults = QrCodeService::getDefaultSettings();

        $options = [
            'foreground_colour' => $validated['foreground_colour'] ?? $defaults['foreground_colour'],
            'background_colour' => $validated['background_colour'] ?? $defaults['background_colour'],
            'size' => $validated['size'] ?? $defaults['size'],
            'ecc_level' => $validated['ecc_level'] ?? $defaults['ecc_level'],
            'module_style' => $validated['module_style'] ?? $defaults['module_style'],
            'format' => $validated['format'] ?? 'png',
            'return_base64' => false,
        ];

        $content = $this->qrCodeService->generate($biolink, $options);
        $mimeType = ($options['format'] === 'svg') ? 'image/svg+xml' : 'image/png';

        return response($content)
            ->header('Content-Type', $mimeType);
    }
}
