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
            'tipe_pekerjaan' => $this->tipe_pekerjaan,
            'lokasi' => $this->lokasi,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'lowongan_selesai' => $this->lowongan_selesai?->format('Y-m-d'),
            'foto' => $this->foto_lowongan,
            'perusahaan' => new PerusahaanResource($this->whenLoaded('perusahaan')),
            'pekerjaan' => $this->whenLoaded('pekerjaan', function () {
                return [
                    'id' => $this->pekerjaan->id_pekerjaan,
                    'posisi' => $this->pekerjaan->posisi,
                ];
            }),
            'posted_by' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id_users,
                    'email' => $this->user->email_users,
                    'role' => $this->user->role,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
