<?php

namespace App\Http\Resources;

use App\Traits\GeneratesThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengumumanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id_pengumuman,
            'judul'           => $this->judul,
            'konten'          => $this->konten,
            'foto'            => $this->foto,
            'foto_thumbnail'  => GeneratesThumbnail::thumbnailPath($this->foto),
            'status'          => $this->status,
            'is_pinned'       => $this->is_pinned,
            'posted_by'       => $this->whenLoaded('user', function () {
                return [
                    'id'    => $this->user->id_users,
                    'email' => $this->user->email_users,
                    'role'  => $this->user->role,
                ];
            }),
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
