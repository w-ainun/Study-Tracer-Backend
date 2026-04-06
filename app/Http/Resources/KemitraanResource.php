<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KemitraanResource extends JsonResource
{
    /**
     * Transform kemitraan into the shape the frontend expects:
     * { id, nama, jalan, image, tipe }
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id_kemitraan,
            'nama'  => $this->nama,
            'jalan' => $this->alamat,
            'image' => $this->logo
                ? url('storage/' . $this->logo)
                : null,
            'tipe'  => $this->tipe,

            // Related entities (when loaded)
            'universitas' => $this->when(
                $this->relationLoaded('universitas') && $this->universitas,
                fn() => [
                    'id'   => $this->universitas->id_universitas,
                    'nama' => $this->universitas->nama_universitas,
                ]
            ),
            'perusahaan' => $this->when(
                $this->relationLoaded('perusahaan') && $this->perusahaan,
                fn() => [
                    'id'   => $this->perusahaan->id_perusahaan,
                    'nama' => $this->perusahaan->nama_perusahaan,
                ]
            ),
        ];
    }
}
