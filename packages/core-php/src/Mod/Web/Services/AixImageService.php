<?php

declare(strict_types=1);

namespace Core\Mod\Web\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AI image generation service for BioHost.
 *
 * Provides AI-powered image generation for biolink assets including
 * profile images and background images using OpenAI DALL-E.
 */
class AixImageService
{
    protected ?string $openaiApiKey;
    protected string $model = 'dall-e-3';

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
    }

    /**
     * Check if the image generation service is available.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return ! empty($this->openaiApiKey);
    }

    /**
     * Generate a profile image based on description.
     *
     * @param string $description Description of the desired image
     * @return string|null URL of the generated image, or null if generation failed
     */
    public function generateProfileImage(string $description): ?string
    {
        if (! $this->isAvailable()) {
            Log::warning('OpenAI API key not configured for image generation');
            return null;
        }

        $prompt = $this->buildProfileImagePrompt($description);

        return $this->generateImage($prompt, 'profile');
    }

    /**
     * Generate a background image based on theme and style.
     *
     * @param string $theme Theme description (e.g., 'minimalist', 'nature', 'abstract')
     * @param string $style Style description (e.g., 'gradient', 'geometric', 'organic')
     * @return string|null URL of the generated image, or null if generation failed
     */
    public function generateBackgroundImage(string $theme, string $style): ?string
    {
        if (! $this->isAvailable()) {
            Log::warning('OpenAI API key not configured for image generation');
            return null;
        }

        $prompt = $this->buildBackgroundImagePrompt($theme, $style);

        return $this->generateImage($prompt, 'background');
    }

    /**
     * Generate an image using OpenAI DALL-E.
     *
     * @param string $prompt The prompt for image generation
     * @param string $type Type of image (profile or background)
     * @return string|null URL of the generated image, or null if generation failed
     */
    protected function generateImage(string $prompt, string $type = 'general'): ?string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/images/generations', [
                'model' => $this->model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => $type === 'profile' ? '1024x1024' : '1792x1024',
                'quality' => 'standard',
                'response_format' => 'url',
            ]);

            if (! $response->successful()) {
                Log::error('OpenAI image generation failed', [
                    'status' => $response->status(),
                    'error' => $response->json(),
                ]);
                return null;
            }

            $data = $response->json();
            $imageUrl = $data['data'][0]['url'] ?? null;

            if (! $imageUrl) {
                Log::error('No image URL in OpenAI response', ['response' => $data]);
                return null;
            }

            // Optionally download and store the image
            // For now, return the OpenAI URL directly (valid for 60 minutes)
            // In production, you might want to download and store in your storage
            return $imageUrl;

        } catch (\Exception $e) {
            Log::error('OpenAI image generation exception', [
                'message' => $e->getMessage(),
                'type' => $type,
            ]);
            return null;
        }
    }

    /**
     * Download an image from URL and store it in the application storage.
     *
     * @param string $url The image URL to download
     * @param string $disk The storage disk to use
     * @return string|null The stored file path, or null if download failed
     */
    public function downloadAndStoreImage(string $url, string $disk = 'public'): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                Log::error('Failed to download image', ['url' => $url]);
                return null;
            }

            $filename = 'biolink-ai/' . Str::uuid() . '.png';
            Storage::disk($disk)->put($filename, $response->body());

            return Storage::disk($disk)->url($filename);

        } catch (\Exception $e) {
            Log::error('Image download exception', [
                'message' => $e->getMessage(),
                'url' => $url,
            ]);
            return null;
        }
    }

    /**
     * Build a prompt for profile image generation.
     *
     * @param string $description User's description
     * @return string Complete prompt for DALL-E
     */
    protected function buildProfileImagePrompt(string $description): string
    {
        return <<<PROMPT
Create a professional profile image based on this description: {$description}

Style requirements:
- High quality, professional appearance
- Suitable for a bio page or social media profile
- Clean composition with good lighting
- Modern and appealing aesthetic
- No text or watermarks
PROMPT;
    }

    /**
     * Build a prompt for background image generation.
     *
     * @param string $theme Theme description
     * @param string $style Style description
     * @return string Complete prompt for DALL-E
     */
    protected function buildBackgroundImagePrompt(string $theme, string $style): string
    {
        return <<<PROMPT
Create a background image for a bio page with these specifications:

Theme: {$theme}
Style: {$style}

Requirements:
- Wide landscape format suitable for web page background
- Subtle and not overpowering
- Good contrast for overlaying text
- Modern and professional aesthetic
- No text or watermarks
- Seamless or tileable pattern preferred
PROMPT;
    }

    /**
     * Generate a placeholder image URL for when AI generation is unavailable.
     *
     * @param string $type Type of placeholder (profile or background)
     * @param int $width Width in pixels
     * @param int $height Height in pixels
     * @return string Placeholder image URL
     */
    public function getPlaceholder(string $type = 'general', int $width = 1024, int $height = 1024): string
    {
        $text = match ($type) {
            'profile' => 'Profile',
            'background' => 'Background',
            default => 'Image',
        };

        // Using placeholder.com service
        return "https://via.placeholder.com/{$width}x{$height}/cccccc/666666?text={$text}";
    }

    /**
     * Estimate the cost of generating an image.
     *
     * @param string $size Image size (1024x1024, 1792x1024, etc.)
     * @return float Estimated cost in USD
     */
    public function estimateCost(string $size = '1024x1024'): float
    {
        // DALL-E 3 pricing (as of 2024)
        $pricing = [
            '1024x1024' => 0.040,   // $0.040 per image
            '1792x1024' => 0.080,   // $0.080 per image
            '1024x1792' => 0.080,   // $0.080 per image
        ];

        return $pricing[$size] ?? 0.040;
    }
}
