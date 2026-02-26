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
            'judul' => $this->judul_kuesioner,
            'deskripsi' => $this->deskripsi_kuesioner,
            'tanggal_publikasi' => $this->tanggal_publikasi?->format('Y-m-d'),
            'status' => $this->whenLoaded('status', function () {
                return [
                    'id' => $this->status->id_status,
                    'nama' => $this->status->nama_status,
                ];
            }),
            'jumlah_pertanyaan' => $this->whenCounted('pertanyaan'),
            'section_ques' => SectionQuesResource::collection($this->whenLoaded('sectionQues')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
