<?php

namespace App\Http\Resources\Alumni;

use App\Http\Resources\LowonganResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BerandaResource extends JsonResource
{
    /**
     * Transform the beranda data into an array.
     *
     * Restricted sections (alumni_terbaru, lowongan_terbaru, top_perusahaan)
     * are always returned with their data so the frontend can show them as
     * visible-but-locked. A `locked` boolean tells the frontend to disable
     * interaction when true.
     */
    public function toArray(Request $request): array
    {
        $profile = $this->resource['profile'];
        $latestRiwayat = $profile->riwayatStatus->first();

        return [
            'profile' => [
                'id' => $profile->id_alumni,
                'nama' => $profile->nama_alumni,
                'foto' => $profile->foto ?: null,
                'foto_thumbnail' => \App\Traits\GeneratesThumbnail::thumbnailPath($profile->foto),
                'jurusan' => $profile->jurusan?->nama_jurusan,
                'tahun_masuk' => $profile->tahun_masuk,
                'tahun_lulus' => $profile->tahun_lulus?->format('Y-m-d'),
                'status_create' => $profile->status_create,
                'email' => $profile->user?->email_users,
                'current_status' => $this->buildCurrentStatus($latestRiwayat),
            ],

            // Access control flags
            'is_verified' => $this->resource['is_verified'],
            'has_completed_kuesioner' => $this->resource['has_completed_kuesioner'],
            'can_access_all' => $this->resource['can_access_all'],
            'current_status_id' => $this->resource['current_status_id'],

            // Always accessible sections
            'status_pengajuan' => new StatusPengajuanResource($this->resource['status_pengajuan']),
            'kuesioner_pending' => BerandaKuesionerResource::collection($this->resource['kuesioner_pending']),

            // Restricted sections — data always provided, `locked` flag controls frontend interaction
            'alumni_terbaru' => [
                'locked' => $this->resource['alumni_terbaru']['locked'],
                'data' => BerandaAlumniCardResource::collection(
                    collect($this->resource['alumni_terbaru']['data'])
                ),
            ],
            'lowongan_terbaru' => [
                'locked' => $this->resource['lowongan_terbaru']['locked'],
                'data' => LowonganResource::collection(
                    collect($this->resource['lowongan_terbaru']['data'])
                ),
            ],
            'top_perusahaan' => [
                'locked' => $this->resource['top_perusahaan']['locked'],
                'data' => BerandaPerusahaanResource::collection(
                    collect($this->resource['top_perusahaan']['data'])
                ),
            ],
        ];
    }

    /**
     * Build current career status info from latest riwayat.
     */
    private function buildCurrentStatus($riwayat): ?array
    {
        if (!$riwayat) return null;

        $data = [
            'status' => $riwayat->status?->nama_status ?? 'Mencari',
        ];

        if ($riwayat->pekerjaan) {
            $data['posisi'] = $riwayat->pekerjaan->posisi;
            $data['perusahaan'] = $riwayat->pekerjaan->perusahaan?->nama_perusahaan;
        } elseif ($riwayat->kuliah) {
            $data['jenjang'] = $riwayat->kuliah->jenjang ?? null;
            $data['universitas'] = $riwayat->kuliah->universitas?->nama_universitas;
            $data['jurusan_kuliah'] = $riwayat->kuliah->jurusanKuliah?->nama_jurusan ?? null;
        } elseif ($riwayat->wirausaha) {
            $data['nama_usaha'] = $riwayat->wirausaha->nama_usaha;
            $data['bidang_usaha'] = $riwayat->wirausaha->bidangUsaha?->nama ?? null;
        }

        return $data;
    }
}
