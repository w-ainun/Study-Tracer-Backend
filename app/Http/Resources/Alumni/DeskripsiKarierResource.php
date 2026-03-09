<?php

namespace App\Http\Resources\Alumni;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeskripsiKarierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $riwayat = $this->whenLoaded('riwayatStatus');

        return [
            'id' => $this->id_deskripsi,
            'status_karier_id' => $this->id_riwayat,
            'deskripsi' => $this->deskripsi,
            'riwayat_status' => $this->when($riwayat, function () use ($riwayat) {
                $status = $riwayat->status?->nama_status ?? 'Unknown';
                $detail = null;

                if ($riwayat->pekerjaan) {
                    $detail = $riwayat->pekerjaan->posisi . ' di ' . ($riwayat->pekerjaan->perusahaan?->nama_perusahaan ?? 'Perusahaan');
                } elseif ($riwayat->kuliah) {
                    $detail = 'Mahasiswa di ' . ($riwayat->kuliah->universitas?->nama_universitas ?? 'Universitas');
                } elseif ($riwayat->wirausaha) {
                    $detail = 'Wirausaha: ' . $riwayat->wirausaha->nama_usaha;
                }

                return [
                    'id' => $riwayat->id_riwayat,
                    'status' => $status,
                    'detail' => $detail,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
