<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class KemitraanPerusahaanResource extends JsonResource
{
    /**
     * Transform perusahaan into the shape the frontend Kemitraan.jsx expects:
     * { id, nama, jalan, image }
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id_perusahaan,
            'nama'  => $this->nama_perusahaan,
            'jalan' => $this->jalan,
            'image' => $this->logo
                ? url('storage/' . $this->logo)
                : null,
        ];
    }
}
