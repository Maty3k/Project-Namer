<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * ExportResource formats Export model data for API responses.
 *
 * @property-read \App\Models\Export $resource
 */
final class ExportResource extends JsonResource
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
            'export_type' => $this->resource->export_type,
            'file_size' => $this->resource->file_size,
            'formatted_file_size' => $this->resource->getFormattedFileSize(),
            'download_count' => $this->resource->download_count,
            'download_url' => $this->resource->getDownloadUrl(),
            'is_expired' => $this->resource->isExpired(),
            'expires_at' => $this->resource->expires_at?->toISOString(),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),

            // Conditional fields based on download count
            'has_been_downloaded' => $this->resource->download_count > 0,

            // Related models when loaded
            'exportable' => $this->whenLoaded('exportable', fn () => [
                'type' => class_basename($this->resource->exportable_type),
                'id' => $this->resource->exportable_id,
                'business_name' => $this->resource->exportable->business_name ?? null,
                'business_description' => $this->resource->exportable->business_description ?? null,
            ]),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->resource->user->id,
                'name' => $this->resource->user->name,
            ]),
        ];
    }
}
