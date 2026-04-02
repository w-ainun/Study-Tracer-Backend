<?php

namespace App\Http\Resources;

use App\Traits\GeneratesThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PengaturanTampilanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'nama_sekolah'       => $this->nama_sekolah,
            'logo'               => $this->logo ? Storage::disk('public')->url($this->logo) : null,
            'logo_thumbnail'     => $this->logo ? Storage::disk('public')->url(GeneratesThumbnail::thumbnailPath($this->logo)) : null,
            'login_bg'           => $this->login_bg ? Storage::disk('public')->url($this->login_bg) : null,
            'login_bg_thumbnail' => $this->login_bg ? Storage::disk('public')->url(GeneratesThumbnail::thumbnailPath($this->login_bg)) : null,
            'primary_color'      => $this->primary_color,
            'secondary_color'    => $this->secondary_color,
            'third_color'        => $this->third_color,
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
