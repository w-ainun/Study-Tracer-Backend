<?php

namespace App\Http\Resources\Alumni;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BerandaKuesionerResource extends JsonResource
{
    /**
     * Transform kuesioner data for beranda notification card.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_kuesioner,
            'title' => $this->title,
            'deskripsi' => $this->deskripsi,
            'status_karir' => $this->whenLoaded('statusKarir', fn() => [
                'id' => $this->statusKarir->id_status,
                'nama' => $this->statusKarir->nama_status,
            ]),
            'jumlah_pertanyaan' => $this->pertanyaan_count ?? $this->pertanyaan()->count(),
            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d'),
        ];
    }
}
