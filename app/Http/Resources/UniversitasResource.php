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
            'jurusan_kuliah' => $this->whenLoaded('jurusanKuliah', function () {
                return $this->jurusanKuliah->map(fn($j) => [
                    'id' => $j->id_jurusanKuliah,
                    'nama' => $j->nama_jurusan,
                ]);
            }),
        ];
    }
}
