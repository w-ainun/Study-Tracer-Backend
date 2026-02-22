<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LowonganResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_lowongan,
            'judul' => $this->judul_lowongan,
            'deskripsi' => $this->deskripsi,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'lowongan_selesai' => $this->lowongan_selesai,
            'foto' => $this->foto_lowongan ? asset('storage/' . $this->foto_lowongan) : null,
            'perusahaan' => new PerusahaanResource($this->whenLoaded('perusahaan')),
            'pekerjaan' => $this->whenLoaded('pekerjaan', function () {
                return [
                    'id' => $this->pekerjaan->id_pekerjaan,
                    'posisi' => $this->pekerjaan->posisi,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
