<?php

namespace App\Http\Resources\Alumni;

use App\Traits\GeneratesThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortofolioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_portofolio,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
            'link_project' => $this->link_project,
            'gambar' => $this->gambar,
            'gambar_thumbnail' => GeneratesThumbnail::thumbnailPath($this->gambar),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
