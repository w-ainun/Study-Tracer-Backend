<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SebaranAlumniDetailResource extends JsonResource
{
    /**
     * Transform alumni detail at location into JSON.
     */
    public function toArray(Request $request): array
    {
        return [
            'id_alumni' => $this->resource['id_alumni'],
            'nama' => $this->resource['nama'],
            'foto' => $this->resource['foto'],
            'foto_thumbnail' => $this->resource['foto_thumbnail'],
            'jurusan' => $this->resource['jurusan'],
            'tahun_masuk' => $this->resource['tahun_masuk'],
            'tahun_lulus' => $this->resource['tahun_lulus'],
            'status_karir' => $this->resource['status_karir'],
            'detail' => $this->resource['detail'],
        ];
    }
}
