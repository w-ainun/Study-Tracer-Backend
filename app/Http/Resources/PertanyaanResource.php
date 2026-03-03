<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PertanyaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_pertanyaan,
            'id_kuesioner' => $this->id_kuesioner,
            'isi_pertanyaan' => $this->isi_pertanyaan,
            'kuesioner' => $this->whenLoaded('kuesioner', function () {
                return [
                    'id' => $this->kuesioner->id_kuesioner,
                    'judul' => $this->kuesioner->judul_kuesioner ?? $this->kuesioner->title,
                    'status_karir' => $this->kuesioner->relationLoaded('statusKarir') && $this->kuesioner->statusKarir ? [
                        'id_status' => $this->kuesioner->statusKarir->id_status,
                        'nama_status' => $this->kuesioner->statusKarir->nama_status,
                    ] : null,
                ];
            }),
            'opsi' => OpsiJawabanResource::collection($this->whenLoaded('opsiJawaban')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
