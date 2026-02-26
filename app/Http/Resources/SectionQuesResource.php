<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SectionQuesResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_sectionques,
            'id_kuesioner' => $this->id_kuesioner,
            'judul_pertanyaan' => $this->judul_pertanyaan,
            'pertanyaan' => PertanyaanResource::collection($this->whenLoaded('pertanyaan')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
