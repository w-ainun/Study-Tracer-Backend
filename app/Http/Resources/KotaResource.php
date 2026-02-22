<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KotaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_kota,
            'nama' => $this->nama_kota,
            'provinsi' => new ProvinsiResource($this->whenLoaded('provinsi')),
        ];
    }
}
