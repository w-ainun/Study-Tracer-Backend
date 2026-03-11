<?php

namespace App\Http\Resources\Alumni;

use App\Traits\GeneratesThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlumniDirectoryResource extends JsonResource
{
    /**
     * Transform alumni into directory card format.
     * Matches the frontend alumni.jsx card structure:
     * { id, name, angkatan, role, company, status, image }
     */
    public function toArray(Request $request): array
    {
        $latestRiwayat = $this->relationLoaded('riwayatStatus')
            ? $this->riwayatStatus->first()
            : null;

        // Status karir: Bekerja, Kuliah, Wirausaha, or Mencari Pekerjaan
        $status = $latestRiwayat?->status?->nama_status ?? 'Mencari Pekerjaan';

        // Role (posisi/jenjang/wirausaha) and company
        $role = null;
        $company = null;

        if ($latestRiwayat) {
            if ($latestRiwayat->pekerjaan) {
                $role = $latestRiwayat->pekerjaan->posisi;
                $company = $latestRiwayat->pekerjaan->perusahaan?->nama_perusahaan;
            } elseif ($latestRiwayat->kuliah) {
                // For studying alumni: show "jenjang - jurusan kuliah"
                $jenjang = $latestRiwayat->kuliah->jenjang ?? '';
                $jurusan = $latestRiwayat->kuliah->jurusanKuliah?->nama_jurusan ?? '';
                
                if ($jenjang && $jurusan) {
                    $role = $jenjang . ' - ' . $jurusan;
                } elseif ($jenjang) {
                    $role = $jenjang;
                } elseif ($jurusan) {
                    $role = $jurusan;
                } else {
                    $role = 'Mahasiswa';
                }
                
                $company = $latestRiwayat->kuliah->universitas?->nama_universitas;
            } elseif ($latestRiwayat->wirausaha) {
                $role = 'Wirausaha';
                $company = $latestRiwayat->wirausaha->nama_usaha;
            }
        }

        return [
            'id'       => $this->id_alumni,
            'name'     => $this->nama_alumni,
            'angkatan' => $this->tahun_masuk,
            'foto'     => $this->foto ?: null,
            'foto_thumbnail' => GeneratesThumbnail::thumbnailPath($this->foto),
            'jurusan'  => $this->whenLoaded('jurusan', fn() => $this->jurusan?->nama_jurusan),
            'role'     => $role ?? 'Mencari Pekerjaan',
            'company'  => $company ?? '-',
            'status'   => $status,
        ];
    }
}
