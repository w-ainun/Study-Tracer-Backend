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
            // Identitas & Media
            'nama_sekolah'          => $this->nama_sekolah,
            'logo'                  => $this->logo ? Storage::disk('public')->url($this->logo) : null,
            'logo_thumbnail'        => $this->logo ? Storage::disk('public')->url(GeneratesThumbnail::thumbnailPath($this->logo)) : null,
            'login_bg'              => $this->login_bg ? Storage::disk('public')->url($this->login_bg) : null,
            'login_bg_thumbnail'    => $this->login_bg ? Storage::disk('public')->url(GeneratesThumbnail::thumbnailPath($this->login_bg)) : null,
            'landing_bg'            => $this->landing_bg ? Storage::disk('public')->url($this->landing_bg) : null,
            'landing_bg_thumbnail'  => $this->landing_bg ? Storage::disk('public')->url(GeneratesThumbnail::thumbnailPath($this->landing_bg)) : null,

            // Konten Landing Page
            'landing_title'         => $this->landing_title,
            'landing_description'   => $this->landing_description,

            // Palet Warna
            'primary_color'         => $this->primary_color,
            'secondary_color'       => $this->secondary_color,
            'third_color'           => $this->third_color,

            // Konten Footer & Kontak
            'deskripsi_footer'      => $this->deskripsi_footer,
            'email_kontak'          => $this->email_kontak,
            'web_kontak'            => $this->web_kontak,
            'telp_kontak'           => $this->telp_kontak,

            // Teks Modal (Privasi, Layanan, Dukungan)
            'teks_privasi'          => $this->teks_privasi,
            'teks_layanan'          => $this->teks_layanan,
            'teks_dukungan'         => $this->teks_dukungan,

            'updated_at'            => $this->updated_at?->toIso8601String(),
        ];
    }
}
