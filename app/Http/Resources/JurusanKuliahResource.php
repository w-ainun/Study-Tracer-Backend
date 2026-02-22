<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JurusanKuliahResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_jurusanKuliah,
            'nama' => $this->nama_jurusan,
        ];
    }
}
