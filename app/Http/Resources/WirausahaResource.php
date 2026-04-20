<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WirausahaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $alumni = $this->riwayatStatus?->alumni;

        return [
            'id'          => $this->id_wirausaha,
            'nama_usaha'  => $this->nama_usaha,
            'alamat'      => $this->alamat,
            'id_bidang'   => $this->id_bidang,
            'bidang'      => $this->bidangUsaha?->nama_bidang,
            'id_kota'     => $this->id_kota,
            'kota'        => $this->kota?->nama_kota,
            'provinsi'    => $this->kota?->provinsi?->nama_provinsi,
            'id_riwayat'  => $this->id_riwayat,
            'alumni'      => $alumni ? [
                'id'       => $alumni->id_alumni,
                'nama'     => $alumni->nama_alumni,
                'nis'      => $alumni->nis,
                'jurusan'  => $alumni->jurusan?->nama_jurusan,
            ] : null,
            'tahun_mulai'   => $this->riwayatStatus?->tahun_mulai,
            'tahun_selesai' => $this->riwayatStatus?->tahun_selesai,
            'latitude'    => $this->latitude,
            'longitude'   => $this->longitude,
            'created_at'  => $this->created_at?->toISOString(),
            'updated_at'  => $this->updated_at?->toISOString(),
        ];
    }
}
