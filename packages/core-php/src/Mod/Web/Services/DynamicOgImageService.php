<?php

namespace Core\Mod\Web\Services;

use Core\Mod\Web\Models\Page;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

/**
 * Dynamic OG Image Generator
 *
 * Generates Open Graph images for biolinks to improve social sharing.
 * Uses configurable templates with biolink branding.
 */
class DynamicOgImageService
{
    protected int $width = 1200;

    protected int $height = 630;

    /**
     * Generate an OG image for a bio.
     *
     * Creates a branded OG image showing the biolink title, profile image,
     * and background colour. Returns the storage path to the generated image.
     *
     * @param  BioLink  $biolink  The biolink to generate an image for
     * @param  string  $template  Template name (default, minimal, branded)
     * @return string Storage URL to the generated image
     */
    public function generate(Page $biolink, string $template = 'default'): string
    {
        // Get biolink settings
        $background = $biolink->getBackground();
        $textColor = $biolink->getTextColor();
        $title = $biolink->getSeoTitle() ?? $biolink->url;
        $description = $biolink->getSeoDescription();

        // Get background colour
        $bgColor = $this->getBackgroundColor($background);

        // Create blank canvas with background
        $image = Image::canvas($this->width, $this->height, $bgColor);

        // Generate based on template
        match ($template) {
            'minimal' => $this->renderMinimalTemplate($image, $biolink, $title, $description, $textColor),
            'branded' => $this->renderBrandedTemplate($image, $biolink, $title, $description, $textColor),
            default => $this->renderDefaultTemplate($image, $biolink, $title, $description, $textColor),
        };

        // Encode and save
        $filename = $this->getFilename($biolink);
        $quality = config('bio.og_images.quality', 85);
        $encoded = $image->encode('jpg', $quality);

        Storage::disk('public')->put($filename, $encoded);

        return Storage::disk('public')->url($filename);
    }

    /**
     * Generate OG image URL without actually generating it.
     *
     * Returns the URL where the image will be accessible once generated.
     */
    public function getUrl(Page $biolink): string
    {
        $filename = $this->getFilename($biolink);

        return Storage::disk('public')->url($filename);
    }

    /**
     * Check if an OG image exists for a bio.
     */
    public function exists(Page $biolink): bool
    {
        $filename = $this->getFilename($biolink);

        return Storage::disk('public')->exists($filename);
    }

    /**
     * Delete the OG image for a bio.
     */
    public function delete(Page $biolink): bool
    {
        if (! $this->exists($biolink)) {
            return true;
        }

        $filename = $this->getFilename($biolink);

        return Storage::disk('public')->delete($filename);
    }

    /**
     * Get the filename for a biolink's OG image.
     */
    protected function getFilename(Page $biolink): string
    {
        return "og-images/biolink-{$biolink->id}.jpg";
    }

    /**
     * Get background colour from background settings.
     */
    protected function getBackgroundColor(array $background): string
    {
        $type = $background['type'] ?? 'color';

        if ($type === 'gradient') {
            // For gradients, use start colour (v2 doesn't support gradients easily)
            return $background['gradient_start'] ?? '#ffffff';
        }

        return $background['color'] ?? config('bio.og_images.default_background', '#ffffff');
    }

    /**
     * Render the default template.
     *
     * Shows profile image, title, and description in a standard layout.
     */
    protected function renderDefaultTemplate($image, Page $biolink, string $title, ?string $description, string $textColor): void
    {
        // Add semi-transparent overlay for better text readability
        $image->rectangle(0, 0, $this->width, $this->height, function ($draw) {
            $draw->background('rgba(0, 0, 0, 0.3)');
        });

        // Get font path
        $fontBold = $this->getFontPath('Inter-Bold.ttf');
        $fontRegular = $this->getFontPath('Inter-Regular.ttf');

        // Draw profile image if available
        $profileImage = $biolink->getSetting('profile.image_url');
        if ($profileImage) {
            $this->drawProfileImage($image, $profileImage, 150, 240);
        }

        // Draw title (centred)
        $titleY = $profileImage ? 450 : 280;
        $wrappedTitle = $this->wrapText($title, 40);
        $image->text($wrappedTitle, 600, $titleY, function ($font) use ($fontBold) {
            if ($fontBold) {
                $font->file($fontBold);
            }
            $font->size(52);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });

        // Draw description if available
        if ($description) {
            $descY = $titleY + 80;
            $wrappedDesc = $this->wrapText($description, 60);
            $image->text($wrappedDesc, 600, $descY, function ($font) use ($fontRegular) {
                if ($fontRegular) {
                    $font->file($fontRegular);
                }
                $font->size(28);
                $font->color('#e5e7eb');
                $font->align('center');
                $font->valign('middle');
            });
        }

        // Draw domain badge at bottom
        $domain = parse_url($biolink->getFullUrlAttribute(), PHP_URL_HOST) ?? 'bio.host.uk.com';
        $image->text($domain, 600, 560, function ($font) use ($fontRegular) {
            if ($fontRegular) {
                $font->file($fontRegular);
            }
            $font->size(20);
            $font->color('#9ca3af');
            $font->align('center');
            $font->valign('middle');
        });
    }

    /**
     * Render the minimal template.
     *
     * Simple text-only layout with no profile image.
     */
    protected function renderMinimalTemplate($image, Page $biolink, string $title, ?string $description, string $textColor): void
    {
        $fontBold = $this->getFontPath('Inter-Bold.ttf');
        $fontRegular = $this->getFontPath('Inter-Regular.ttf');

        // Determine text colour (always white for good contrast)
        $displayColor = '#ffffff';

        // Draw title
        $wrappedTitle = $this->wrapText($title, 45);
        $image->text($wrappedTitle, 600, 280, function ($font) use ($fontBold, $displayColor) {
            if ($fontBold) {
                $font->file($fontBold);
            }
            $font->size(56);
            $font->color($displayColor);
            $font->align('center');
            $font->valign('middle');
        });

        // Draw description if available
        if ($description) {
            $wrappedDesc = $this->wrapText($description, 70);
            $image->text($wrappedDesc, 600, 380, function ($font) use ($fontRegular, $displayColor) {
                if ($fontRegular) {
                    $font->file($fontRegular);
                }
                $font->size(28);
                $font->color($displayColor);
                $font->align('center');
                $font->valign('middle');
            });
        }
    }

    /**
     * Render the branded template.
     *
     * Includes Host UK branding and biolink information.
     */
    protected function renderBrandedTemplate($image, Page $biolink, string $title, ?string $description, string $textColor): void
    {
        $fontBold = $this->getFontPath('Inter-Bold.ttf');
        $fontRegular = $this->getFontPath('Inter-Regular.ttf');
        $fontMedium = $this->getFontPath('Inter-Medium.ttf');

        // Add gradient overlay for brand consistency
        $image->rectangle(0, 0, $this->width, $this->height, function ($draw) {
            $draw->background('rgba(0, 0, 0, 0.5)');
        });

        // Draw profile image
        $profileImage = $biolink->getSetting('profile.image_url');
        if ($profileImage) {
            $this->drawProfileImage($image, $profileImage, 120, 120);
        }

        // Draw title
        $titleY = $profileImage ? 300 : 250;
        $wrappedTitle = $this->wrapText($title, 40);
        $image->text($wrappedTitle, 600, $titleY, function ($font) use ($fontBold) {
            if ($fontBold) {
                $font->file($fontBold);
            }
            $font->size(48);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
        });

        // Draw description
        if ($description) {
            $descY = $titleY + 70;
            $wrappedDesc = $this->wrapText($description, 60);
            $image->text($wrappedDesc, 600, $descY, function ($font) use ($fontRegular) {
                if ($fontRegular) {
                    $font->file($fontRegular);
                }
                $font->size(24);
                $font->color('#d1d5db');
                $font->align('center');
                $font->valign('middle');
            });
        }

        // Draw Host UK branding at bottom
        $image->text('Powered by Host UK', 600, 560, function ($font) use ($fontMedium) {
            if ($fontMedium) {
                $font->file($fontMedium);
            }
            $font->size(18);
            $font->color('#6b7280');
            $font->align('center');
            $font->valign('middle');
        });
    }

    /**
     * Draw a circular profile image.
     */
    protected function drawProfileImage($image, string $url, int $size, int $y): void
    {
        try {
            // Download and process profile image
            $profileData = @file_get_contents($url);
            if (! $profileData) {
                return;
            }

            $profile = Image::make($profileData);
            $profile->fit($size, $size);

            // Create circular mask
            $profile->circle($size, $size / 2, $size / 2, function ($draw) {
                $draw->background('#ffffff');
            });

            // Position image centred horizontally
            $x = (int) (($this->width - $size) / 2);

            // Composite the profile image
            $image->insert($profile, 'top-left', $x, $y);
        } catch (\Exception $e) {
            // If image loading fails, silently continue without profile image
            logger()->warning("Failed to load profile image for OG: {$url}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get font file path if it exists.
     */
    protected function getFontPath(string $filename): ?string
    {
        // Check in public/fonts
        $path = public_path("fonts/{$filename}");
        if (file_exists($path)) {
            return $path;
        }

        // Check in resources/fonts
        $path = resource_path("fonts/{$filename}");
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Wrap text to fit within a maximum character limit.
     */
    protected function wrapText(string $text, int $maxChars): string
    {
        if (strlen($text) <= $maxChars) {
            return $text;
        }

        return substr($text, 0, $maxChars - 3).'...';
    }

    /**
     * Check if OG image is stale and needs regeneration.
     */
    public function isStale(Page $biolink): bool
    {
        if (! $this->exists($biolink)) {
            return true;
        }

        $filename = $this->getFilename($biolink);
        $lastModified = Storage::disk('public')->lastModified($filename);
        $cacheDays = config('bio.og_images.cache_days', 10);

        return now()->timestamp - $lastModified > ($cacheDays * 86400);
    }
}
