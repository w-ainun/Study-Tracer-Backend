<?php

namespace App\Http\Resources\Alumni;

use App\Http\Resources\JurusanResource;
use App\Http\Resources\SkillResource;
use App\Http\Resources\SocialMediaResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    /**
     * Full alumni profile for the profile page.
     * Serves all 3 tabs: Detail Pribadi, Status Karier, Keahlian.
     */
    public function toArray(Request $request): array
    {
        // Extract social media URLs by platform name
        $socialMedia = $this->whenLoaded('socialMedia');
        $instagram = null;
        $linkedin = null;
        $github = null;
        $facebook = null;
        $website = null;

        if ($socialMedia && is_iterable($socialMedia)) {
            foreach ($socialMedia as $sm) {
                $name = strtolower($sm->nama_sosmed ?? '');
                if (str_contains($name, 'instagram')) $instagram = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'linkedin')) $linkedin = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'github')) $github = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'facebook')) $facebook = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'website') || str_contains($name, 'web')) $website = $sm->pivot->url ?? null;
            }
        }

        // Determine current career info from latest riwayat
        $latestRiwayat = $this->relationLoaded('riwayatStatus')
            ? $this->riwayatStatus->first()
            : null;

        $currentCareer = null;
        if ($latestRiwayat) {
            $currentCareer = [
                'status' => $latestRiwayat->status?->nama_status ?? null,
                'tahun_mulai' => $latestRiwayat->tahun_mulai,
                'tahun_selesai' => $latestRiwayat->tahun_selesai,
            ];

            if ($latestRiwayat->pekerjaan) {
                $perusahaan = $latestRiwayat->pekerjaan->perusahaan;
                $currentCareer['pekerjaan'] = [
                    'posisi' => $latestRiwayat->pekerjaan->posisi,
                    'perusahaan' => $perusahaan?->nama_perusahaan,
                    'kota' => $perusahaan?->kota?->nama_kota ?? null,
                    'provinsi' => $perusahaan?->kota?->provinsi?->nama_provinsi ?? null,
                ];
            }

            if ($latestRiwayat->kuliah) {
                $currentCareer['kuliah'] = [
                    'universitas' => $latestRiwayat->kuliah->universitas?->nama_universitas,
                    'jurusan_kuliah' => $latestRiwayat->kuliah->jurusanKuliah?->nama ?? null,
                    'jenjang' => $latestRiwayat->kuliah->jenjang,
                    'jalur_masuk' => $latestRiwayat->kuliah->jalur_masuk,
                ];
            }

            if ($latestRiwayat->wirausaha) {
                $currentCareer['wirausaha'] = [
                    'nama_usaha' => $latestRiwayat->wirausaha->nama_usaha,
                    'bidang_usaha' => $latestRiwayat->wirausaha->bidangUsaha?->nama_bidang ?? null,
                ];
            }
        }

        return [
            'id' => $this->id_alumni,
            'nama' => $this->nama_alumni,
            'nis' => $this->nis,
            'nisn' => $this->nisn,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tanggal_lahir' => $this->tanggal_lahir?->format('Y-m-d'),
            'tempat_lahir' => $this->tempat_lahir,
            'tahun_masuk' => $this->tahun_masuk,
            'foto' => $this->foto ?: null,
            'alamat' => $this->alamat,
            'no_hp' => $this->no_hp,
            'tahun_lulus' => $this->tahun_lulus?->format('Y-m-d'),
            'status_create' => $this->status_create,

            // Relations
            'jurusan' => new JurusanResource($this->whenLoaded('jurusan')),
            'email' => $this->whenLoaded('user', fn() => $this->user?->email),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),

            // Social media (flat)
            'social_media' => SocialMediaResource::collection($this->whenLoaded('socialMedia')),
            'instagram' => $instagram,
            'linkedin' => $linkedin,
            'github' => $github,
            'facebook' => $facebook,
            'website' => $website,

            // Career data
            'current_career' => $currentCareer,
            'riwayat_status' => ProfileRiwayatResource::collection($this->whenLoaded('riwayatStatus')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
