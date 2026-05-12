<?php

namespace App\Http\Resources\Alumni;

use App\Http\Resources\SkillResource;
use App\Http\Resources\PerusahaanResource;
use App\Traits\GeneratesThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LowonganAlumniResource extends JsonResource
{
    /**
     * Transform lowongan data for alumni view.
     * Excludes admin-only fields (approval_status, posted_by).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_lowongan,
            'judul' => $this->judul_lowongan,
            'deskripsi' => $this->deskripsi,
            'nomor_kontak' => $this->nomor_kontak,
            'kebutuhan_lainnya' => $this->kebutuhan_lainnya,
            'tipe_pekerjaan' => $this->tipe_pekerjaan,
            'lokasi' => $this->lokasi,
            'status' => $this->status,
            'lowongan_selesai' => $this->lowongan_selesai?->format('Y-m-d'),
            'jam_mulai' => $this->jam_mulai,
            'jam_berakhir' => $this->jam_berakhir,
            'foto' => $this->foto_lowongan,
            'foto_thumbnail' => GeneratesThumbnail::thumbnailPath($this->foto_lowongan),
            'perusahaan' => new PerusahaanResource($this->whenLoaded('perusahaan')),
            'pekerjaan' => $this->whenLoaded('pekerjaan', function () {
                return [
                    'id' => $this->pekerjaan->id_pekerjaan,
                    'posisi' => $this->pekerjaan->posisi,
                ];
            }),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'skill_match_count' => $this->when(isset($this->skill_match_count), $this->skill_match_count),
            'created_at' => $this->created_at,
        ];
    }
}
