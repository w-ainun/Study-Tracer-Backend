<?php

namespace App\Http\Resources\Alumni;

use App\Http\Resources\JurusanResource;
use App\Http\Resources\SkillResource;
use App\Http\Resources\SocialMediaResource;
use App\Traits\GeneratesThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProfileResource extends JsonResource
{
    /**
     * Public alumni profile — excludes sensitive data (email, no_hp, alamat, nis, nisn).
     * Used for "Lihat Profile" from the alumni directory.
     */
    public function toArray(Request $request): array
    {
        // ── Social media URLs ──
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

        // ── Current career from latest approved riwayat ──
        $approvedRiwayat = $this->relationLoaded('riwayatStatus')
            ? $this->riwayatStatus->where('approval_status', 'approved')->values()
            : collect();

        $latestRiwayat = $approvedRiwayat->first();

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
                    'jurusan_kuliah' => $latestRiwayat->kuliah->jurusanKuliah?->nama_jurusan ?? null,
                    'jenjang' => $latestRiwayat->kuliah->jenjang,
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
            'jenis_kelamin' => $this->jenis_kelamin,
            'tahun_masuk' => $this->tahun_masuk,
            'tahun_lulus' => $this->tahun_lulus?->format('Y-m-d'),
            'foto' => $this->foto ?: null,
            'foto_thumbnail' => GeneratesThumbnail::thumbnailPath($this->foto),
            'tempat_lahir' => $this->tempat_lahir,

            // Relations
            'jurusan' => new JurusanResource($this->whenLoaded('jurusan')),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),

            // Social media
            'social_media' => SocialMediaResource::collection($this->whenLoaded('socialMedia')),
            'instagram' => $instagram,
            'linkedin' => $linkedin,
            'github' => $github,
            'facebook' => $facebook,
            'website' => $website,

            // Career data
            'current_career' => $currentCareer,
            'riwayat_status' => ProfileRiwayatResource::collection($approvedRiwayat),

            // Deskripsi Karier & Portofolio
            'deskripsi_karier' => $this->when($this->relationLoaded('riwayatStatus'), function () {
                $deskripsiList = [];
                foreach ($this->riwayatStatus->where('approval_status', 'approved') as $riwayat) {
                    if ($riwayat->relationLoaded('deskripsiKarier') && $riwayat->deskripsiKarier) {
                        $deskripsiList[] = new DeskripsiKarierResource(
                            $riwayat->deskripsiKarier->load('riwayatStatus.status', 'riwayatStatus.pekerjaan.perusahaan', 'riwayatStatus.kuliah.universitas', 'riwayatStatus.wirausaha')
                        );
                    }
                }
                return $deskripsiList;
            }, []),
            'portofolio' => PortofolioResource::collection($this->whenLoaded('portofolio') ?? collect()),
        ];
    }
}
