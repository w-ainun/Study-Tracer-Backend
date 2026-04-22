<?php

namespace App\Http\Resources\Alumni;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionStatsResource extends JsonResource
{
    /**
     * Resource untuk statistik koneksi alumni.
     */
    public function toArray(Request $request): array
    {
        return [
            'connections_count'       => $this->resource['connections_count'] ?? 0,
            'pending_requests_count'  => $this->resource['pending_requests_count'] ?? null,
        ];
    }
}
