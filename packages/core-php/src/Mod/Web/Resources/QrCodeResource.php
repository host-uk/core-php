<?php

declare(strict_types=1);

namespace Core\Mod\Web\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * QR Code API resource.
 *
 * Returns QR code data with the generated image.
 */
class QrCodeResource extends JsonResource
{
    /**
     * The data array to transform.
     *
     * @var array<string, mixed>
     */
    public $resource;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'url' => $this->resource['url'],
            'format' => $this->resource['format'],
            'size' => $this->resource['size'],
            'image' => $this->resource['image'],
            'settings' => $this->resource['settings'] ?? [],

            // Biolink reference (if generated for a biolink)
            'biolink_id' => $this->resource['biolink_id'] ?? null,
            'biolink_url' => $this->resource['biolink_url'] ?? null,
        ];
    }
}
