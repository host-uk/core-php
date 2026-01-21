<?php

namespace Core\Mod\Web\Mcp\Tools;

use Core\Mod\Web\Models\Page;
use Core\Mod\Web\Services\QrCodeService;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class QrTools extends BaseBioTool
{
    protected string $name = 'qr_tools';

    protected string $description = 'Generate and customize QR codes for bio links';

    public function handle(Request $request): Response
    {
        $action = $request->get('action');

        return match ($action) {
            'generate' => $this->generateQrCode($request),
            default => $this->error('Invalid action', ['available' => ['generate']]),
        };
    }

    protected function generateQrCode(Request $request): Response
    {
        $biolinkId = $request->get('biolink_id');
        if (! $biolinkId) {
            return $this->error('biolink_id is required');
        }

        $biolink = Page::find($biolinkId);
        if (! $biolink) {
            return $this->error('Bio link not found');
        }

        $qrService = app(QrCodeService::class);

        $options = [
            'format' => $request->get('format', 'png'),
            'size' => (int) $request->get('size', 400),
            'foreground_colour' => $request->get('foreground_colour', '#000000'),
            'background_colour' => $request->get('background_colour', '#ffffff'),
            'ecc_level' => $request->get('ecc_level', 'M'),
            'module_style' => $request->get('module_style', 'square'),
            'return_base64' => true,
        ];

        // Validate options
        $errors = QrCodeService::validateSettings($options);
        if (! empty($errors)) {
            return $this->error('Invalid QR settings', ['details' => $errors]);
        }

        $dataUri = $qrService->generateDataUri($biolink, $options);

        return $this->json([
            'ok' => true,
            'biolink_id' => $biolink->id,
            'url' => $biolink->full_url,
            'format' => $options['format'],
            'size' => $options['size'],
            'data_uri' => $dataUri,
            'available_styles' => array_keys(QrCodeService::MODULE_STYLES),
            'available_ecc_levels' => array_keys(QrCodeService::ERROR_CORRECTION_LEVELS),
        ]);
    }
}
