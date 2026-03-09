<?php

namespace App\Http\Resources\Alumni;

use App\Http\Resources\PerusahaanResource;
use App\Http\Resources\SkillResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedLowonganResource extends JsonResource
{
    /**
     * Transform saved lowongan (SimpanLowongan) for alumni view.
     */
    public function toArray(Request $request): array
    {
        $lowongan = $this->whenLoaded('lowongan', fn() => $this->lowongan);

        return [
            'id_simpan' => $this->id_simpan,
            'saved_at' => $this->created_at,
            'lowongan' => $this->whenLoaded('lowongan', function () {
                return [
                    'id' => $this->lowongan->id_lowongan,
                    'judul' => $this->lowongan->judul_lowongan,
                    'deskripsi' => $this->lowongan->deskripsi,
                    'tipe_pekerjaan' => $this->lowongan->tipe_pekerjaan,
                    'lokasi' => $this->lowongan->lokasi,
                    'status' => $this->lowongan->status,
                    'lowongan_selesai' => $this->lowongan->lowongan_selesai?->format('Y-m-d'),
                    'foto' => $this->lowongan->foto_lowongan,
                    'foto_thumbnail' => \App\Traits\GeneratesThumbnail::thumbnailPath($this->lowongan->foto_lowongan),
                    'perusahaan' => new PerusahaanResource($this->lowongan->perusahaan),
                    'pekerjaan' => $this->lowongan->pekerjaan ? [
                        'id' => $this->lowongan->pekerjaan->id_pekerjaan,
                        'posisi' => $this->lowongan->pekerjaan->posisi,
                    ] : null,
                    'skills' => $this->lowongan->skills ? SkillResource::collection($this->lowongan->skills) : [],
                    'created_at' => $this->lowongan->created_at,
                ];
            }),
        ];
    }
}
