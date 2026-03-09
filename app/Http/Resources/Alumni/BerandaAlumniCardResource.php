<?php

namespace App\Http\Resources\Alumni;

use App\Traits\GeneratesThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BerandaAlumniCardResource extends JsonResource
{
    /**
     * Transform alumni data into a beranda card format.
     */
    public function toArray(Request $request): array
    {
        $latestRiwayat = $this->relationLoaded('riwayatStatus')
            ? $this->riwayatStatus->first()
            : null;

        // Determine tag from status name (Bekerja, Kuliah, Wirausaha, or Mencari)
        $tags = $latestRiwayat?->status?->nama_status ?? 'Mencari';

        // Determine role/position and company from the latest riwayat
        $role = null;
        $company = null;

        if ($latestRiwayat) {
            if ($latestRiwayat->pekerjaan) {
                $role = $latestRiwayat->pekerjaan->posisi;
                $company = $latestRiwayat->pekerjaan->perusahaan?->nama_perusahaan;
            } elseif ($latestRiwayat->kuliah) {
                $role = $latestRiwayat->kuliah->jenjang
                    ? $latestRiwayat->kuliah->jenjang . ' - ' . ($latestRiwayat->kuliah->jurusanKuliah?->nama ?? '')
                    : ($latestRiwayat->kuliah->jurusanKuliah?->nama ?? 'Mahasiswa');
                $company = $latestRiwayat->kuliah->universitas?->nama_universitas;
            } elseif ($latestRiwayat->wirausaha) {
                $role = 'Wirausaha';
                $company = $latestRiwayat->wirausaha->nama_usaha;
            }
        }

        return [
            'id' => $this->id_alumni,
            'name' => $this->nama_alumni,
            'angkatan' => $this->tahun_masuk,
            'foto' => $this->foto ?: null,
            'foto_thumbnail' => GeneratesThumbnail::thumbnailPath($this->foto),
            'jurusan' => $this->whenLoaded('jurusan', fn() => $this->jurusan?->nama_jurusan),
            'role' => $role ?? 'Mencari Pekerjaan',
            'company' => $company ?? '-',
            'tags' => $tags,
        ];
    }
}
