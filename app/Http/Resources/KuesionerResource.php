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
            'id_status' => $this->id_status,
            'title' => $this->title,
            'deskripsi' => $this->deskripsi,
            'status' => $this->status,
            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),
            'tanggal_publikasi' => $this->tanggal_publikasi?->format('Y-m-d'),
            'status_karir' => $this->whenLoaded('statusKarir', function () {
                return [
                    'id' => $this->statusKarir->id_status,
                    'nama' => $this->statusKarir->nama_status,
                ];
            }),
            'jumlah_pertanyaan' => $this->whenCounted('pertanyaan'),
            'pertanyaan' => PertanyaanResource::collection($this->whenLoaded('pertanyaan')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
