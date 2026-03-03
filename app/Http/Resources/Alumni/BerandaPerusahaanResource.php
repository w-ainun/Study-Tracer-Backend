<?php

namespace App\Http\Resources\Alumni;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BerandaPerusahaanResource extends JsonResource
{
    /**
     * Transform perusahaan data for beranda top companies card.
     */
    public function toArray(Request $request): array
    {
        $kota = $this->kota;
        $lokasi = $kota
            ? ($kota->nama_kota . ($kota->provinsi ? ', ' . $kota->provinsi->nama_provinsi : ''))
            : '-';

        return [
            'id' => $this->id_perusahaan,
            'name' => $this->nama_perusahaan,
            'location' => $lokasi,
            'alumniCount' => $this->alumni_count ?? 0,
        ];
    }
}
