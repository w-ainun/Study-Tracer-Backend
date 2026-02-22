<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KuesionerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_kuesioner,
            'judul' => $this->judul_kuesioner,
            'deskripsi' => $this->deskripsi_kuesioner,
            'status' => $this->status_kuesioner,
            'tanggal_publikasi' => $this->tanggal_publikasi?->format('Y-m-d'),
            'jumlah_pertanyaan' => $this->whenCounted('pertanyaan'),
            'pertanyaan' => PertanyaanResource::collection($this->whenLoaded('pertanyaan')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
