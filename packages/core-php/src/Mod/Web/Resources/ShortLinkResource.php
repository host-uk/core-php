<?php

declare(strict_types=1);

namespace Core\Mod\Web\Resources;

use Core\Mod\Web\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Short Link API resource.
 *
 * Transforms BioLink models of type 'link' into API responses.
 * Short links are essentially biolinks that redirect to a URL.
 *
 * @mixin BioLink
 */
class ShortLinkResource extends JsonResource
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
            'url' => $this->url,
            'short_url' => $this->full_url,
            'destination_url' => $this->location_url,
            'is_enabled' => $this->is_enabled,

            // Stats
            'clicks' => $this->clicks,
            'unique_clicks' => $this->unique_clicks,
            'last_click_at' => $this->last_click_at?->toIso8601String(),

            // Scheduling
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),

            // Settings relevant to short links
            'settings' => [
                'redirect_type' => $this->getSetting('redirect_type', '302'),
                'password_protected' => (bool) $this->getSetting('password'),
                'cloaking' => (bool) $this->getSetting('cloaking'),
            ],

            // Relations
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
}
