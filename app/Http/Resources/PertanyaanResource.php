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
            'id_sectionques' => $this->id_sectionques,
            'isi_pertanyaan' => $this->isi_pertanyaan,
            'section' => $this->whenLoaded('sectionQues', function () {
                return [
                    'id' => $this->sectionQues->id_sectionques,
                    'judul' => $this->sectionQues->judul_pertanyaan,
                ];
            }),
            'opsi' => OpsiJawabanResource::collection($this->whenLoaded('opsiJawaban')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
