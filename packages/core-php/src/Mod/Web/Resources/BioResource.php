<?php

declare(strict_types=1);

namespace Core\Mod\Web\Resources;

use Core\Mod\Web\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * BioLink API resource.
 *
 * Transforms BioLink models into API responses with consistent structure.
 *
 * @mixin BioLink
 */
class BioResource extends JsonResource
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
            'type' => $this->type,
            'url' => $this->url,
            'full_url' => $this->full_url,
            'location_url' => $this->location_url,
            'is_enabled' => $this->is_enabled,
            'is_verified' => $this->is_verified,

            // Settings (exclude sensitive data)
            'settings' => $this->getPublicSettings(),

            // Stats
            'clicks' => $this->clicks,
            'unique_clicks' => $this->unique_clicks,
            'last_click_at' => $this->last_click_at?->toIso8601String(),

            // Scheduling
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),

            // Relations (conditionally loaded)
            'blocks' => BlockResource::collection($this->whenLoaded('blocks')),
            'blocks_count' => $this->whenCounted('blocks'),
            'project' => $this->when($this->relationLoaded('project'), fn () => [
                'id' => $this->project?->id,
                'name' => $this->project?->name,
            ]),
            'domain' => $this->when($this->relationLoaded('domain'), fn () => [
                'id' => $this->domain?->id,
                'host' => $this->domain?->host,
            ]),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Get public settings (filter out any sensitive data).
     */
    protected function getPublicSettings(): array
    {
        $settings = $this->settings?->toArray() ?? [];

        // Remove any potentially sensitive keys
        unset($settings['password']);

        return $settings;
    }
}
