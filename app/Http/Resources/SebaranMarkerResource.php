<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SebaranMarkerResource extends JsonResource
{
    /**
     * Transform a marker array into JSON.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'type' => $this->resource['type'],
            'entity_id' => $this->resource['entity_id'],
            'entity_name' => $this->resource['entity_name'],
            'latitude' => $this->resource['latitude'],
            'longitude' => $this->resource['longitude'],
            'kota' => $this->resource['kota'] ?? null,
            'provinsi' => $this->resource['provinsi'] ?? null,
            'alumni_count' => $this->resource['alumni_count'],
            'alumni_preview' => $this->resource['alumni_preview'],
        ];
    }
}
