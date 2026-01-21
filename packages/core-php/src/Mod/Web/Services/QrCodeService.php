<?php

namespace Core\Mod\Web\Services;

use Core\Mod\Web\Models\Page;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Facades\Storage;

/**
 * QR code generation service for bio.
 *
 * Generates customisable QR codes with support for:
 * - Foreground/background colours
 * - Logo embedding
 * - Multiple error correction levels
 * - Various sizes
 * - PNG and SVG output formats
 */
class QrCodeService
{
    /**
     * Available module (dot) styles.
     */
    public const array MODULE_STYLES = [
        'square' => 'Square (default)',
        'rounded' => 'Rounded corners',
        'dots' => 'Circular dots',
    ];

    /**
     * Error correction levels with descriptions.
     */
    public const array ERROR_CORRECTION_LEVELS = [
        'L' => 'Low (7% recovery)',
        'M' => 'Medium (15% recovery)',
        'Q' => 'Quartile (25% recovery)',
        'H' => 'High (30% recovery)',
    ];

    /**
     * Available size presets in pixels.
     */
    public const array SIZE_PRESETS = [
        200 => 'Small (200px)',
        400 => 'Medium (400px)',
        600 => 'Large (600px)',
        800 => 'Extra large (800px)',
        1000 => 'Print quality (1000px)',
    ];

    /**
     * Generate a QR code for a bio.
     */
    public function generate(Page $biolink, array $options = []): string
    {
        $url = $biolink->full_url;

        return $this->generateForUrl($url, $options);
    }

    /**
     * Generate a QR code for any URL.
     */
    public function generateForUrl(string $url, array $options = []): string
    {
        $qrOptions = $this->buildOptions($options);
        $qrCode = new QRCode($qrOptions);

        $output = $qrCode->render($url);

        // Handle logo embedding for PNG output
        if ($this->shouldEmbedLogo($options)) {
            $output = $this->embedLogo($output, $options);
        }

        return $output;
    }

    /**
     * Generate QR code and return as base64 data URI.
     */
    public function generateDataUri(Page $biolink, array $options = []): string
    {
        $options['return_base64'] = true;
        $output = $this->generate($biolink, $options);
        $format = $options['format'] ?? 'png';

        $mimeType = $format === 'svg' ? 'image/svg+xml' : 'image/png';

        return "data:{$mimeType};base64,{$output}";
    }

    /**
     * Generate a preview QR code (smaller, optimised for display).
     */
    public function generatePreview(Page $biolink, array $options = []): string
    {
        $options['size'] = min($options['size'] ?? 300, 400);
        $options['return_base64'] = true;

        return $this->generateDataUri($biolink, $options);
    }

    /**
     * Save QR code to storage.
     */
    public function saveToStorage(
        Page $biolink,
        array $options = [],
        string $disk = 'public'
    ): string {
        $output = $this->generate($biolink, $options);
        $format = $options['format'] ?? 'png';
        $filename = "qr-codes/{$biolink->id}_{$biolink->url}.{$format}";

        // Decode base64 if necessary
        if (($options['return_base64'] ?? false) || str_starts_with($output, 'data:')) {
            $output = $this->decodeBase64Output($output);
        }

        Storage::disk($disk)->put($filename, $output);

        return $filename;
    }

    /**
     * Build QR code options from array.
     */
    protected function buildOptions(array $options): QROptions
    {
        $format = $options['format'] ?? 'png';
        $size = $options['size'] ?? 400;
        $eccLevel = $options['ecc_level'] ?? 'M';
        $fgColour = $options['foreground_colour'] ?? '#000000';
        $bgColour = $options['background_colour'] ?? '#ffffff';
        $moduleStyle = $options['module_style'] ?? 'square';
        $returnBase64 = $options['return_base64'] ?? false;

        // Convert hex colours to RGB arrays
        $fgRgb = $this->hexToRgb($fgColour);
        $bgRgb = $this->hexToRgb($bgColour);

        // Calculate scale from desired size (modules are typically 10px base)
        $scale = max(1, (int) round($size / 40));

        $qrOptions = new QROptions([
            'version' => QRCode::VERSION_AUTO,
            'eccLevel' => $this->mapEccLevel($eccLevel),
            'scale' => $scale,
            'imageBase64' => $returnBase64,
            'outputType' => $format === 'svg' ? QROutputInterface::MARKUP_SVG : QROutputInterface::GDIMAGE_PNG,
            'imageTransparent' => false,

            // Colours
            'moduleValues' => [
                QRMatrix::M_FINDER_DARK => $fgRgb,
                QRMatrix::M_FINDER_DOT => $fgRgb,
                QRMatrix::M_FINDER => $bgRgb,
                QRMatrix::M_ALIGNMENT_DARK => $fgRgb,
                QRMatrix::M_ALIGNMENT => $bgRgb,
                QRMatrix::M_TIMING_DARK => $fgRgb,
                QRMatrix::M_TIMING => $bgRgb,
                QRMatrix::M_FORMAT_DARK => $fgRgb,
                QRMatrix::M_FORMAT => $bgRgb,
                QRMatrix::M_VERSION_DARK => $fgRgb,
                QRMatrix::M_VERSION => $bgRgb,
                QRMatrix::M_DATA_DARK => $fgRgb,
                QRMatrix::M_DATA => $bgRgb,
                QRMatrix::M_DARKMODULE => $fgRgb,
                QRMatrix::M_QUIETZONE => $bgRgb,
                QRMatrix::M_SEPARATOR => $bgRgb,
            ],

            // Quiet zone (margin)
            'quietzoneSize' => 4,
        ]);

        // Apply module style modifications
        if ($moduleStyle === 'rounded' && $format === 'svg') {
            $qrOptions->svgUseFillAttributes = true;
            $qrOptions->drawCircularModules = false;
            $qrOptions->circleRadius = 0.4;
        } elseif ($moduleStyle === 'dots') {
            $qrOptions->drawCircularModules = true;
            $qrOptions->circleRadius = 0.45;
        }

        return $qrOptions;
    }

    /**
     * Check if we should embed a logo.
     */
    protected function shouldEmbedLogo(array $options): bool
    {
        if (empty($options['logo_path'])) {
            return false;
        }

        // Only PNG format supports logo embedding in this implementation
        $format = $options['format'] ?? 'png';

        return $format === 'png';
    }

    /**
     * Embed a logo into the centre of the QR code.
     */
    protected function embedLogo(string $qrOutput, array $options): string
    {
        $logoPath = $options['logo_path'];
        $logoSize = $options['logo_size'] ?? 20; // Percentage of QR code size

        // Decode QR output if base64
        $isBase64 = $options['return_base64'] ?? false;
        if ($isBase64) {
            $qrOutput = base64_decode($qrOutput);
        }

        // Create image from QR code
        $qrImage = imagecreatefromstring($qrOutput);
        if ($qrImage === false) {
            return $isBase64 ? base64_encode($qrOutput) : $qrOutput;
        }

        // Load logo image
        $logoImage = $this->loadLogoImage($logoPath);
        if ($logoImage === false) {
            imagedestroy($qrImage);

            return $isBase64 ? base64_encode($qrOutput) : $qrOutput;
        }

        // Calculate sizes
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $logoWidth = imagesx($logoImage);
        $logoHeight = imagesy($logoImage);

        // Calculate target logo size (percentage of QR code)
        $targetLogoWidth = (int) ($qrWidth * ($logoSize / 100));
        $targetLogoHeight = (int) ($qrHeight * ($logoSize / 100));

        // Maintain aspect ratio
        $ratio = min($targetLogoWidth / $logoWidth, $targetLogoHeight / $logoHeight);
        $newLogoWidth = (int) ($logoWidth * $ratio);
        $newLogoHeight = (int) ($logoHeight * $ratio);

        // Calculate centre position
        $destX = (int) (($qrWidth - $newLogoWidth) / 2);
        $destY = (int) (($qrHeight - $newLogoHeight) / 2);

        // Add white background padding for logo
        $padding = 5;
        $bgColour = imagecolorallocate($qrImage, 255, 255, 255);
        imagefilledrectangle(
            $qrImage,
            $destX - $padding,
            $destY - $padding,
            $destX + $newLogoWidth + $padding,
            $destY + $newLogoHeight + $padding,
            $bgColour
        );

        // Resize and copy logo onto QR code
        imagecopyresampled(
            $qrImage,
            $logoImage,
            $destX,
            $destY,
            0,
            0,
            $newLogoWidth,
            $newLogoHeight,
            $logoWidth,
            $logoHeight
        );

        // Output to string
        ob_start();
        imagepng($qrImage);
        $output = ob_get_clean();

        // Cleanup
        imagedestroy($qrImage);
        imagedestroy($logoImage);

        return $isBase64 ? base64_encode($output) : $output;
    }

    /**
     * Load logo image from path.
     */
    protected function loadLogoImage(string $path): \GdImage|false
    {
        // Handle storage paths
        if (str_starts_with($path, 'storage://')) {
            $disk = 'public';
            $path = str_replace('storage://', '', $path);
            $fullPath = Storage::disk($disk)->path($path);
        } elseif (file_exists($path)) {
            $fullPath = $path;
        } else {
            return false;
        }

        if (! file_exists($fullPath)) {
            return false;
        }

        $imageInfo = getimagesize($fullPath);
        if ($imageInfo === false) {
            return false;
        }

        return match ($imageInfo[2]) {
            IMAGETYPE_PNG => imagecreatefrompng($fullPath),
            IMAGETYPE_JPEG => imagecreatefromjpeg($fullPath),
            IMAGETYPE_GIF => imagecreatefromgif($fullPath),
            IMAGETYPE_WEBP => imagecreatefromwebp($fullPath),
            default => false,
        };
    }

    /**
     * Map string ECC level to integer constant.
     */
    protected function mapEccLevel(string $level): int
    {
        return match (strtoupper($level)) {
            'L' => EccLevel::L,
            'M' => EccLevel::M,
            'Q' => EccLevel::Q,
            'H' => EccLevel::H,
            default => EccLevel::M,
        };
    }

    /**
     * Convert hex colour to RGB array.
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Decode base64 output, handling data URIs.
     */
    protected function decodeBase64Output(string $output): string
    {
        if (str_starts_with($output, 'data:')) {
            $parts = explode(',', $output, 2);

            return base64_decode($parts[1] ?? $output);
        }

        return base64_decode($output);
    }

    /**
     * Get default QR settings for storage in bio.
     */
    public static function getDefaultSettings(): array
    {
        return [
            'foreground_colour' => '#000000',
            'background_colour' => '#ffffff',
            'size' => 400,
            'ecc_level' => 'M',
            'module_style' => 'square',
            'logo_path' => null,
            'logo_size' => 20,
        ];
    }

    /**
     * Validate QR settings.
     */
    public static function validateSettings(array $settings): array
    {
        $errors = [];

        // Validate colours
        if (isset($settings['foreground_colour']) && ! preg_match('/^#[0-9A-Fa-f]{6}$/', $settings['foreground_colour'])) {
            $errors['foreground_colour'] = 'Invalid foreground colour format';
        }

        if (isset($settings['background_colour']) && ! preg_match('/^#[0-9A-Fa-f]{6}$/', $settings['background_colour'])) {
            $errors['background_colour'] = 'Invalid background colour format';
        }

        // Validate size
        if (isset($settings['size'])) {
            $size = (int) $settings['size'];
            if ($size < 100 || $size > 1000) {
                $errors['size'] = 'Size must be between 100 and 1000 pixels';
            }
        }

        // Validate ECC level
        if (isset($settings['ecc_level']) && ! in_array($settings['ecc_level'], ['L', 'M', 'Q', 'H'])) {
            $errors['ecc_level'] = 'Invalid error correction level';
        }

        // Validate module style
        if (isset($settings['module_style']) && ! array_key_exists($settings['module_style'], self::MODULE_STYLES)) {
            $errors['module_style'] = 'Invalid module style';
        }

        // Validate logo size
        if (isset($settings['logo_size'])) {
            $logoSize = (int) $settings['logo_size'];
            if ($logoSize < 10 || $logoSize > 30) {
                $errors['logo_size'] = 'Logo size must be between 10% and 30%';
            }
        }

        return $errors;
    }
}
