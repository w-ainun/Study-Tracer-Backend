<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PertanyaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_pertanyaanKuis,
            'pertanyaan' => $this->pertanyaan,
            'tipe_pertanyaan' => $this->tipe_pertanyaan,
            'status_pertanyaan' => $this->status_pertanyaan,
            'kategori' => $this->kategori,
            'judul_bagian' => $this->judul_bagian,
            'urutan' => $this->urutan,
            'opsi' => OpsiJawabanResource::collection($this->whenLoaded('opsiJawaban')),
        ];
    }
}
