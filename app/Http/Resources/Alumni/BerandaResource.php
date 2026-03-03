<?php

namespace App\Http\Resources\Alumni;

use App\Http\Resources\BerandaAlumniCardResource;
use App\Http\Resources\KuesionerResource;
use App\Http\Resources\LowonganResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BerandaResource extends JsonResource
{
    /**
     * Transform the beranda data into an array.
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
                'jurusan' => $profile->jurusan?->nama_jurusan,
                'tahun_masuk' => $profile->tahun_masuk,
                'tahun_lulus' => $profile->tahun_lulus?->format('Y-m-d'),
                'status_create' => $profile->status_create,
                'email' => $profile->user?->email_users,
                'current_status' => $this->buildCurrentStatus($latestRiwayat),
            ],
            'is_verified' => $this->resource['is_verified'],
            'status_pengajuan' => new StatusPengajuanResource($this->resource['status_pengajuan']),
            'kuesioner_pending' => BerandaKuesionerResource::collection($this->resource['kuesioner_pending']),
            'alumni_terbaru' => BerandaAlumniCardResource::collection(
                collect($this->resource['alumni_terbaru'])
            ),
            'lowongan_terbaru' => LowonganResource::collection(
                collect($this->resource['lowongan_terbaru'])
            ),
            'top_perusahaan' => BerandaPerusahaanResource::collection(
                collect($this->resource['top_perusahaan'])
            ),
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
            $data['jurusan_kuliah'] = $riwayat->kuliah->jurusanKuliah?->nama ?? null;
        } elseif ($riwayat->wirausaha) {
            $data['nama_usaha'] = $riwayat->wirausaha->nama_usaha;
            $data['bidang_usaha'] = $riwayat->wirausaha->bidangUsaha?->nama ?? null;
        }

        return $data;
    }
}
