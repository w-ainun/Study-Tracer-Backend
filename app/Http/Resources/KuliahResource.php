<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KuliahResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_kuliah,
            'universitas' => $this->whenLoaded('universitas', function () {
                return [
                    'id' => $this->universitas->id_universitas,
                    'nama' => $this->universitas->nama_universitas,
                ];
            }),
            'jurusan_kuliah' => $this->whenLoaded('jurusanKuliah', function () {
                return [
                    'id' => $this->jurusanKuliah->id_jurusanKuliah,
                    'nama' => $this->jurusanKuliah->nama_jurusan,
                ];
            }),
            'jalur_masuk' => $this->jalur_masuk,
            'jenjang' => $this->jenjang,
        ];
    }
}
