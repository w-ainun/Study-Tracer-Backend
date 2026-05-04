<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CalonLulusanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id_calon,
            'nisn'    => $this->nisn,
            'nama'    => $this->nama,
            'jurusan' => $this->jurusan?->nama_jurusan ?? '-',
            'id_jurusan' => $this->id_jurusan,
            'batch_id'   => $this->batch_id,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
