<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id_sosmed,
            'nama' => $this->nama_sosmed,
            'icon' => $this->icon_sosmed,
            'url' => $this->pivot ? $this->pivot->url : null,
        ];
    }
}
