<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KuesionerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentStatus = $this->status;
        if ($currentStatus === 'aktif') {
            if ($this->tanggal_mulai && \Carbon\Carbon::parse($this->tanggal_mulai)->isFuture()) {
                $currentStatus = 'pending';
            } elseif ($this->tanggal_selesai && \Carbon\Carbon::parse($this->tanggal_selesai)->isPast()) {
                $currentStatus = 'hidden';
            }
        }

        return [
            'id' => $this->id_kuesioner,
            'id_status' => $this->id_status,
            'title' => $this->title,
            'deskripsi' => $this->deskripsi,
            'status' => $currentStatus,
            'tanggal_mulai' => $this->tanggal_mulai?->format('Y-m-d\TH:i:s'),
            'tanggal_selesai' => $this->tanggal_selesai?->format('Y-m-d\TH:i:s'),
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
