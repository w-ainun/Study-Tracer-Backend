<?php

namespace App\Http\Resources\Alumni;

use App\Http\Resources\JurusanResource;
use App\Http\Resources\SkillResource;
use App\Http\Resources\SocialMediaResource;
use App\Http\Resources\UserResource;
use App\Traits\GeneratesThumbnail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

class ProfileResource extends JsonResource
{
    /**
     * Full alumni profile for the profile page.
     * Serves all 3 tabs: Detail Pribadi, Status Karier, Keahlian.
     */
    public function toArray(Request $request): array
    {
        // Extract social media URLs by platform name
        $socialMedia = $this->whenLoaded('socialMedia');
        $instagram = null;
        $linkedin = null;
        $github = null;
        $facebook = null;
        $website = null;

        if ($socialMedia && is_iterable($socialMedia)) {
            foreach ($socialMedia as $sm) {
                $name = strtolower($sm->nama_sosmed ?? '');
                if (str_contains($name, 'instagram')) $instagram = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'linkedin')) $linkedin = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'github')) $github = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'facebook')) $facebook = $sm->pivot->url ?? null;
                elseif (str_contains($name, 'website') || str_contains($name, 'web')) $website = $sm->pivot->url ?? null;
            }
        }

        // Determine current career info from latest APPROVED riwayat
        $latestRiwayat = $this->relationLoaded('riwayatStatus')
            ? $this->riwayatStatus->where('approval_status', 'approved')->first()
            : null;

        $pendingUpdatesCollection = $this->relationLoaded('pendingProfileUpdates')
            ? $this->pendingProfileUpdates
            : collect();
        $pendingPersonalInfo = $pendingUpdatesCollection
            ->where('section', 'personal_info')
            ->where('status', 'pending')
            ->sortByDesc('created_at')
            ->first();

        $latestPersonalInfo = [
            'id' => $this->id_alumni,
            'nama' => $this->nama_alumni,
            'nis' => $this->nis,
            'nisn' => $this->nisn,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tanggal_lahir' => $this->tanggal_lahir?->format('Y-m-d'),
            'tempat_lahir' => $this->tempat_lahir,
            'tahun_masuk' => $this->tahun_masuk,
            'foto' => $this->foto ?: null,
            'foto_thumbnail' => GeneratesThumbnail::thumbnailPath($this->foto),
            'alamat' => $this->alamat,
            'no_hp' => $this->no_hp,
            'tahun_lulus' => $this->tahun_lulus?->format('Y-m-d'),
            'status' => 'approved',
            'pending_update_id' => null,
            'changed_fields' => [],
            'updated_at' => $this->updated_at,
        ];

        if ($pendingPersonalInfo) {
            $newData = is_array($pendingPersonalInfo->new_data) ? $pendingPersonalInfo->new_data : [];
            $oldData = is_array($pendingPersonalInfo->old_data) ? $pendingPersonalInfo->old_data : [];

            foreach ($newData as $key => $value) {
                if (Arr::has($latestPersonalInfo, $key)) {
                    $latestPersonalInfo[$key] = $value;
                }
            }

            if (!empty($pendingPersonalInfo->foto_path)) {
                $latestPersonalInfo['foto'] = $pendingPersonalInfo->foto_path;
                $latestPersonalInfo['foto_thumbnail'] = GeneratesThumbnail::thumbnailPath($pendingPersonalInfo->foto_path);
            }

            $changedFields = [];
            foreach ($newData as $field => $newValue) {
                $oldValue = $oldData[$field] ?? null;
                if ((string) $oldValue !== (string) $newValue) {
                    $changedFields[] = $field;
                }
            }
            if (!empty($pendingPersonalInfo->foto_path)) {
                $changedFields[] = 'foto';
            }

            $latestPersonalInfo['status'] = 'pending';
            $latestPersonalInfo['pending_update_id'] = $pendingPersonalInfo->id;
            $latestPersonalInfo['changed_fields'] = array_values(array_unique($changedFields));
            $latestPersonalInfo['updated_at'] = $pendingPersonalInfo->updated_at;
        }

        $currentCareer = null;
        if ($latestRiwayat) {
            $currentCareer = [
                'id_riwayat' => $latestRiwayat->id_riwayat,
                'id_status' => $latestRiwayat->id_status,
                'status' => $latestRiwayat->status?->nama_status ?? null,
                'tahun_mulai' => $latestRiwayat->tahun_mulai,
                'tahun_selesai' => $latestRiwayat->tahun_selesai,
            ];

            if ($latestRiwayat->pekerjaan) {
                $perusahaan = $latestRiwayat->pekerjaan->perusahaan;
                $currentCareer['pekerjaan'] = [
                    'posisi' => $latestRiwayat->pekerjaan->posisi,
                    'perusahaan' => $perusahaan?->nama_perusahaan,
                    'kota' => $perusahaan?->kota?->nama_kota ?? null,
                    'provinsi' => $perusahaan?->kota?->provinsi?->nama_provinsi ?? null,
                    'id_kota' => $perusahaan?->id_kota,
                    'jalan' => $perusahaan?->jalan,
                ];
            }

            if ($latestRiwayat->kuliah) {
                $currentCareer['kuliah'] = [
                    'universitas' => $latestRiwayat->kuliah->universitas?->nama_universitas,
                    'jurusan_kuliah' => $latestRiwayat->kuliah->jurusanKuliah ? [
                        'id' => $latestRiwayat->kuliah->jurusanKuliah->id_jurusanKuliah,
                        'nama' => $latestRiwayat->kuliah->jurusanKuliah->nama_jurusan ?? $latestRiwayat->kuliah->jurusanKuliah->nama ?? null,
                    ] : null,
                    'jenjang' => $latestRiwayat->kuliah->jenjang,
                    'jalur_masuk' => $latestRiwayat->kuliah->jalur_masuk,
                ];
            }

            if ($latestRiwayat->wirausaha) {
                $currentCareer['wirausaha'] = [
                    'nama_usaha' => $latestRiwayat->wirausaha->nama_usaha,
                    'bidang_usaha' => $latestRiwayat->wirausaha->bidangUsaha?->nama_bidang ?? null,
                    'id_bidang' => $latestRiwayat->wirausaha->id_bidang,
                ];
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
            'foto_thumbnail' => GeneratesThumbnail::thumbnailPath($this->foto),
            'alamat' => $this->alamat,
            'no_hp' => $this->no_hp,
            'tahun_lulus' => $this->tahun_lulus?->format('Y-m-d'),
            'status_create' => $this->status_create,

            // Relations
            'jurusan' => new JurusanResource($this->whenLoaded('jurusan')),
            'email' => $this->whenLoaded('user', fn() => $this->user?->email),
            'skills' => SkillResource::collection($this->whenLoaded('skills')),

            // Social media (flat)
            'social_media' => SocialMediaResource::collection($this->whenLoaded('socialMedia')),
            'instagram' => $instagram,
            'linkedin' => $linkedin,
            'github' => $github,
            'facebook' => $facebook,
            'website' => $website,

            // Career data
            'current_career' => $currentCareer,
            'riwayat_status' => ProfileRiwayatResource::collection(
                $this->whenLoaded('riwayatStatus', function () {
                    // Only show approved riwayat in the list; pending ones are hidden until admin approves
                    return $this->riwayatStatus->where('approval_status', 'approved')->values();
                }, collect())
            ),

            // New: Deskripsi Karier & Portofolio  
            'deskripsi_karier' => $this->when($this->relationLoaded('riwayatStatus'), function () {
                // Extract all deskripsi karier from approved riwayat_status
                $deskripsiList = [];
                foreach ($this->riwayatStatus->where('approval_status', 'approved') as $riwayat) {
                    if ($riwayat->relationLoaded('deskripsiKarier') && $riwayat->deskripsiKarier) {
                        $deskripsiList[] = new DeskripsiKarierResource($riwayat->deskripsiKarier->load('riwayatStatus.status', 'riwayatStatus.pekerjaan.perusahaan', 'riwayatStatus.kuliah.universitas', 'riwayatStatus.wirausaha'));
                    }
                }
                return $deskripsiList;
            }, []),
            'portofolio' => PortofolioResource::collection($this->whenLoaded('portofolio') ?? collect()),

            // Pending profile updates (visible only to the alumni themselves)
            'pending_updates' => $this->whenLoaded('pendingProfileUpdates', function () {
                return $this->pendingProfileUpdates->map(function ($pending) {
                    return [
                        'id' => $pending->id,
                        'section' => $pending->section,
                        'action' => $pending->action,
                        'new_data' => $pending->new_data,
                        'old_data' => $pending->old_data,
                        'foto_path' => $pending->foto_path,
                        'foto_thumbnail' => GeneratesThumbnail::thumbnailPath($pending->foto_path),
                        'gambar_path' => $pending->gambar_path,
                        'related_id' => $pending->related_id,
                        'status' => 'pending',
                        'created_at' => $pending->created_at,
                    ];
                });
            }, []),

            // Profile data that should be displayed as the latest state in alumni UI.
            // If there is a pending personal_info update, this is a merged preview with status=pending.
            'latest_personal_info' => $latestPersonalInfo,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
