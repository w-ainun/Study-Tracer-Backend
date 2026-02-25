<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UniversitasResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_universitas,
            'nama' => $this->nama_universitas,
            'jurusan' => $this->jurusanKuliah?->nama_jurusan,
            'jalur_masuk' => $this->jalur_masuk,
            'jenjang' => $this->jenjang,
        ];
    }
}
