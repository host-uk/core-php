<?php

declare(strict_types=1);

namespace Core\Mod\Web\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bio Analytics API resource.
 *
 * Transforms analytics data into API responses.
 */
class AnalyticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // The resource wraps an array of analytics data
        return [
            'biolink_id' => $this->resource['biolink_id'] ?? null,
            'period' => $this->resource['period'] ?? null,
            'date_range' => [
                'start' => $this->resource['start'] ?? null,
                'end' => $this->resource['end'] ?? null,
                'limited' => $this->resource['limited'] ?? false,
                'max_days' => $this->resource['max_days'] ?? null,
            ],
            'summary' => $this->resource['summary'] ?? [],
            'clicks_over_time' => $this->resource['clicks_over_time'] ?? null,
            'countries' => $this->resource['countries'] ?? null,
            'devices' => $this->resource['devices'] ?? null,
            'browsers' => $this->resource['browsers'] ?? null,
            'operating_systems' => $this->resource['operating_systems'] ?? null,
            'referrers' => $this->resource['referrers'] ?? null,
            'utm_sources' => $this->resource['utm_sources'] ?? null,
            'utm_campaigns' => $this->resource['utm_campaigns'] ?? null,
            'blocks' => $this->resource['blocks'] ?? null,
        ];
    }
}
