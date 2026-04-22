<?php

namespace App\Http\Resources\Alumni;

use App\Http\Resources\JurusanResource;
use App\Traits\GeneratesThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConnectionResource extends JsonResource
{
    /**
     * Resource untuk alumni dalam konteks koneksi.
     * Menampilkan informasi publik dasar tanpa data sensitif.
     */
    public function toArray(Request $request): array
    {
        // Determine the latest career status
        $latestRiwayat = $this->whenLoaded('riwayatStatus', function () {
            return $this->riwayatStatus->first();
        });

        $careerInfo = null;
        if ($latestRiwayat) {
            $status = $latestRiwayat->status;
            $careerInfo = [
                'status' => $status ? $status->nama_status : null,
            ];

            // Detail berdasarkan tipe status
            if ($latestRiwayat->pekerjaan) {
                $careerInfo['detail'] = $latestRiwayat->pekerjaan->posisi;
                $careerInfo['tempat'] = $latestRiwayat->pekerjaan->perusahaan?->nama_perusahaan;
            } elseif ($latestRiwayat->kuliah) {
                $careerInfo['detail'] = $latestRiwayat->kuliah->universitas?->nama_universitas;
                $careerInfo['tempat'] = null;
            } elseif ($latestRiwayat->wirausaha) {
                $careerInfo['detail'] = $latestRiwayat->wirausaha->nama_usaha;
                $careerInfo['tempat'] = null;
            }
        }

        return [
            'id_alumni'    => $this->id_alumni,
            'nama_alumni'  => $this->nama_alumni,
            'foto'         => $this->foto ?: null,
            'foto_thumbnail' => GeneratesThumbnail::thumbnailPath($this->foto),
            'tahun_lulus'  => $this->tahun_lulus?->format('Y'),
            'jurusan'      => new JurusanResource($this->whenLoaded('jurusan')),
            'karir'        => $careerInfo,
        ];
    }
}
