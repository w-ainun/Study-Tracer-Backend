<?php

namespace App\Http\Resources\Alumni;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PostLikerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id_alumni'   => $this->alumni->id_alumni ?? null,
            'nama_alumni' => $this->alumni->nama_alumni ?? null,
            'foto'        => $this->alumni->foto
                ? (str_starts_with($this->alumni->foto, 'http')
                    ? $this->alumni->foto
                    : Storage::disk('public')->url($this->alumni->foto))
                : null,
            'jurusan'     => $this->alumni->jurusan->nama_jurusan ?? null,
            'liked_at'    => $this->created_at?->toISOString(),
        ];
    }
}
