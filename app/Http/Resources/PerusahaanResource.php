<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerusahaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_perusahaan,
            'nama' => $this->nama_perusahaan,
            'jalan' => $this->jalan,
            'kota' => new KotaResource($this->whenLoaded('kota')),
        ];
    }
}
