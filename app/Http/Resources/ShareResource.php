<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ShareResource formats Share model data for API responses.
 *
 * @property-read \App\Models\Share $resource
 */
final class ShareResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'share_type' => $this->resource->share_type,
            'share_url' => $this->resource->getShareUrl(),
            'is_active' => $this->resource->is_active,
            'view_count' => $this->resource->view_count,
            'expires_at' => $this->resource->expires_at?->toISOString(),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),

            // Conditional fields
            'settings' => $this->when($this->resource->settings !== null, $this->resource->settings),
            'last_viewed_at' => $this->when($this->resource->last_viewed_at !== null, $this->resource->last_viewed_at?->toISOString()),

            // Related models when loaded
            'shareable' => $this->whenLoaded('shareable', fn () => [
                'type' => class_basename($this->resource->shareable_type),
                'id' => $this->resource->shareable_id,
                'business_name' => $this->resource->shareable->business_name ?? null,
                'business_description' => $this->resource->shareable->business_description ?? null,
            ]),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->resource->user->id,
                'name' => $this->resource->user->name,
            ]),

            // Analytics when requested
            'analytics' => $this->when($request->routeIs('shares.analytics'), fn () => [
                'total_views' => $this->resource->view_count,
                'unique_visitors' => $this->resource->accesses()->distinct('ip_address')->count(),
                'recent_views' => $this->resource->accesses()->where('accessed_at', '>=', now()->subDays(7))->count(),
                'today_views' => $this->resource->accesses()->whereDate('accessed_at', today())->count(),
            ]),

            // Security-sensitive fields only for owners
            'is_expired' => $this->resource->isExpired(),
            'is_accessible' => $this->resource->isAccessible(),
        ];
    }
}
