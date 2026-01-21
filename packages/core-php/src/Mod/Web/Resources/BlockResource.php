<?php

declare(strict_types=1);

namespace Core\Mod\Web\Resources;

use Core\Mod\Web\Models\Block;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bio Block API resource.
 *
 * Transforms Block models into API responses.
 *
 * @mixin Block
 */
class BlockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'biolink_id' => $this->biolink_id,
            'type' => $this->type,
            'type_config' => $this->getTypeConfig(),
            'location_url' => $this->location_url,
            'settings' => $this->settings?->toArray() ?? [],
            'order' => $this->order,
            'is_enabled' => $this->is_enabled,
            'is_active' => $this->isActive(),

            // Stats
            'clicks' => $this->clicks,

            // Scheduling
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
