<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlumniResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $socialMedia = $this->whenLoaded('socialMedia');
        $instagram = null;
        $linkedin = null;
        $github = null;
        $facebook = null;
        $website = null;

        if ($socialMedia && is_iterable($socialMedia)) {
            foreach ($socialMedia as $sm) {
                $name = strtolower($sm->nama_sosmed);
                if (str_contains($name, 'instagram')) $instagram = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'linkedin')) $linkedin = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'github')) $github = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'facebook')) $facebook = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'website') || str_contains($name, 'web')) $website = $sm->pivot->url ?? null;
            }
        }

        return [
            'id' => $this->id_alumni,
            'nama' => $this->nama_alumni,
            'nis' => $this->nis,
            'nisn' => $this->nisn,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tanggal_lahir' => $this->tanggal_lahir?->format('Y-m-d'),
            'tempat_lahir' => $this->tempat_lahir,
            'tahun_masuk' => $this->tahun_masuk,
            'foto' => $this->foto ?: null,
            'alamat' => $this->alamat,
            'no_hp' => $this->no_hp,
            'tahun_lulus' => $this->tahun_lulus?->format('Y-m-d'),
            'status_create' => $this->status_create,
            'jurusan' => new JurusanResource($this->whenLoaded('jurusan')),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),
            'social_media' => SocialMediaResource::collection($this->whenLoaded('socialMedia')),
            'instagram' => $instagram,
            'linkedin' => $linkedin,
            'github' => $github,
            'facebook' => $facebook,
            'website' => $website,
            'riwayat_status' => RiwayatStatusResource::collection($this->whenLoaded('riwayatStatus')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
