<?php

namespace Core\Mod\Web\Services;

use Core\Mod\Web\Models\Theme;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

/**
 * Theme Preview Generator
 *
 * Generates screenshot previews of biolink themes for the gallery.
 */
class ThemePreviewGenerator
{
    protected ImageManager $imageManager;

    protected int $width = 800;

    protected int $height = 1200;

    public function __construct()
    {
        $this->imageManager = new ImageManager(['driver' => 'gd']);
    }

    /**
     * Generate a preview image for a theme.
     *
     * Creates a simple preview showing the theme's colour scheme and buttons.
     * Returns the storage path to the generated image.
     */
    public function generate(Theme $theme): string
    {
        // Create blank canvas
        $image = $this->imageManager->canvas($this->width, $this->height);

        // Get theme settings
        $background = $theme->getBackground();
        $button = $theme->getButton();
        $textColor = $theme->getTextColor();

        // Apply background
        $this->applyBackground($image, $background);

        // Draw preview elements (title, buttons, text samples)
        $this->drawPreviewElements($image, $theme);

        // Encode and save
        $filename = "theme-previews/{$theme->slug}.jpg";
        $encoded = (string) $image->encode('jpg', 85);

        Storage::disk('public')->put($filename, $encoded);

        return Storage::disk('public')->url($filename);
    }

    /**
     * Generate previews for all gallery themes.
     *
     * Returns count of generated previews.
     */
    public function generateAll(): int
    {
        $themes = Theme::gallery()->active()->get();
        $count = 0;

        foreach ($themes as $theme) {
            try {
                $url = $this->generate($theme);
                $theme->update(['preview_image' => $url]);
                $count++;
            } catch (\Exception $e) {
                // Log error but continue
                logger()->error("Failed to generate preview for theme {$theme->slug}: {$e->getMessage()}");
            }
        }

        return $count;
    }

    /**
     * Apply background to the image.
     */
    protected function applyBackground($image, array $background): void
    {
        $type = $background['type'] ?? 'color';

        if ($type === 'gradient') {
            // For gradients, we'll use a simple top-to-bottom fill
            // Intervention Image v2 doesn't support gradients natively on canvas easily without plugins
            // So we fallback to start color
            $startColor = $background['gradient_start'] ?? '#ffffff';
            $image->fill($startColor);
        } else {
            // Solid colour
            $color = $background['color'] ?? '#ffffff';
            $image->fill($color);
        }
    }

    /**
     * Draw preview elements on the canvas.
     */
    protected function drawPreviewElements($image, Theme $theme): void
    {
        $button = $theme->getButton();
        $textColor = $theme->getTextColor();

        // Draw theme name at top
        $image->text($theme->name, 400, 100, function ($font) use ($textColor) {
            $font->file(public_path('fonts/Inter-Bold.ttf'));
            $font->size(48);
            $font->color($textColor);
            $font->align('center');
            $font->valign('top');
        });

        // Draw sample buttons
        $buttonY = 300;
        $buttonSpacing = 120;

        for ($i = 0; $i < 4; $i++) {
            $y = $buttonY + ($i * $buttonSpacing);
            $this->drawButton($image, 400, $y, $button, 'Sample Button '.($i + 1));
        }

        // Draw bio text sample
        $image->text('This is a sample bio description', 400, 800, function ($font) use ($textColor) {
            $font->file(public_path('fonts/Inter-Regular.ttf'));
            $font->size(18);
            $font->color($textColor);
            $font->align('center');
            $font->valign('top');
        });
    }

    /**
     * Draw a button on the canvas.
     */
    protected function drawButton($image, int $x, int $y, array $buttonConfig, string $text): void
    {
        $width = 600;
        $height = 80;
        $bgColor = $buttonConfig['background_color'] ?? '#000000';
        $textColor = $buttonConfig['text_color'] ?? '#ffffff';
        $borderRadius = (int) str_replace('px', '', $buttonConfig['border_radius'] ?? '8');
        $borderWidth = (int) str_replace('px', '', $buttonConfig['border_width'] ?? '0');
        $borderColor = $buttonConfig['border_color'] ?? $bgColor;

        // Draw button rectangle (centred at x, y)
        $x1 = intval($x - ($width / 2));
        $y1 = intval($y - ($height / 2));
        $x2 = intval($x1 + $width);
        $y2 = intval($y1 + $height);

        // Draw background
        $image->rectangle($x1, $y1, $x2, $y2, function ($draw) use ($bgColor) {
            $draw->background($bgColor);
        });

        // Draw border if needed
        if ($borderWidth > 0) {
            $image->rectangle($x1, $y1, $x2, $y2, function ($draw) use ($borderColor, $borderWidth) {
                $draw->border($borderWidth, $borderColor);
                $draw->background('rgba(0,0,0,0)'); // Transparent fill
            });
        }

        // Draw text
        $image->text($text, $x, $y, function ($font) use ($textColor) {
            $font->file(public_path('fonts/Inter-Medium.ttf'));
            $font->size(24);
            $font->color($textColor);
            $font->align('center');
            $font->valign('middle');
        });
    }

    /**
     * Delete preview image for a theme.
     */
    public function delete(Theme $theme): bool
    {
        if (! $theme->preview_image) {
            return true;
        }

        $filename = "theme-previews/{$theme->slug}.jpg";

        return Storage::disk('public')->delete($filename);
    }

    /**
     * Check if preview exists for a theme.
     */
    public function exists(Theme $theme): bool
    {
        if (! $theme->preview_image) {
            return false;
        }

        $filename = "theme-previews/{$theme->slug}.jpg";

        return Storage::disk('public')->exists($filename);
    }
}
