<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiwayatKelulusanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id_kelulusan,
            'nisn'             => $this->nisn,
            'nama'             => $this->nama,
            'jurusan'          => $this->jurusan?->nama_jurusan ?? '-',
            'id_jurusan'       => $this->id_jurusan,
            'status_kelulusan' => $this->status_kelulusan ?? 'lulus',
            'tahunLulus'       => (string) $this->tahun_lulus,
            'tahun_lulus'      => (string) $this->tahun_lulus,
            'batch_id'         => $this->batch_id,
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
