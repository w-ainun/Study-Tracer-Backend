<?php

namespace App\Http\Resources\Alumni;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusPengajuanResource extends JsonResource
{
    /**
     * Transform the status pengajuan data into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->resource['status'],
            'estimasi' => $this->resource['estimasi'],
            'steps' => collect($this->resource['steps'])->map(fn($step) => [
                'title' => $step['title'],
                'status' => $step['status'],
                'date' => $step['date'],
                'description' => $step['description'],
            ])->toArray(),
        ];
    }
}
