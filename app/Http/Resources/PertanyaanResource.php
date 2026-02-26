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
            'status_pertanyaan' => $this->status_pertanyaan ?? 'draft',
            'section' => $this->whenLoaded('sectionQues', function () {
                return [
                    'id' => $this->sectionQues->id_sectionques,
                    'judul' => $this->sectionQues->judul_pertanyaan,
                    'id_kuesioner' => $this->sectionQues->id_kuesioner,
                ];
            }),
            'kuesioner' => $this->whenLoaded('sectionQues', function () {
                if ($this->sectionQues && $this->sectionQues->relationLoaded('kuesioner')) {
                    $kuesioner = $this->sectionQues->kuesioner;
                    return [
                        'id' => $kuesioner->id_kuesioner,
                        'judul' => $kuesioner->judul_kuesioner,
                        'status' => $kuesioner->relationLoaded('status') && $kuesioner->status ? [
                            'id_status' => $kuesioner->status->id_status,
                            'nama_status' => $kuesioner->status->nama_status,
                        ] : null,
                    ];
                }
                return null;
            }),
            'opsi' => OpsiJawabanResource::collection($this->whenLoaded('opsiJawaban')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
