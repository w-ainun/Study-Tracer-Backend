<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class KemitraanUniversitasResource extends JsonResource
{
    /**
     * Transform universitas into the shape the frontend Kemitraan.jsx expects:
     * { id, nama, jalan, image }
     */
    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id_universitas,
            'nama'  => $this->nama_universitas,
            'jalan' => $this->alamat,
            'image' => $this->logo
                ? url('storage/' . $this->logo)
                : null,
        ];
    }
}
