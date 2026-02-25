<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JawabanKuesionerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_jawabanKuis,
            'id_pertanyaan' => $this->id_pertanyaan,
            'pertanyaan' => $this->whenLoaded('pertanyaan', function () {
                return [
                    'id' => $this->pertanyaan->id_pertanyaanKuis,
                    'pertanyaan' => $this->pertanyaan->pertanyaan,
                    'tipe' => $this->pertanyaan->tipe_pertanyaan,
                    'opsi' => $this->pertanyaan->opsiJawaban->pluck('opsi')->toArray(),
                ];
            }),
            'jawaban' => $this->jawaban,
            'opsi_jawaban' => $this->whenLoaded('opsiJawaban', function () {
                return $this->opsiJawaban ? $this->opsiJawaban->opsi : null;
            }),
            'created_at' => $this->created_at,
        ];
    }
}
