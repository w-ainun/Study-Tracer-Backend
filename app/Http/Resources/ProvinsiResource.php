<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinsiResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_provinsi,
            'nama' => $this->nama_provinsi,
            'code' => $this->code,
            'kota' => KotaResource::collection($this->whenLoaded('kota')),
        ];
    }
}
