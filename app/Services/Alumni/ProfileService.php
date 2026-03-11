<?php

namespace App\Services\Alumni;

use App\Interfaces\Alumni\ProfileRepositoryInterface;
use App\Models\Kuliah;
use App\Models\Pekerjaan;
use App\Models\PendingProfileUpdate;
use App\Models\Perusahaan;
use App\Models\RiwayatStatus;
use App\Models\Universitas;
use App\Models\Wirausaha;
use App\Traits\GeneratesThumbnail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
    use GeneratesThumbnail;
    private ProfileRepositoryInterface $profileRepository;

    public function __construct(ProfileRepositoryInterface $profileRepository)
    {
        $this->profileRepository = $profileRepository;
    }

    /**
     * Get full alumni profile for the profile page.
     */
    public function getProfile(int $userId)
    {
        return $this->profileRepository->getProfileByUserId($userId);
    }

    /**
     * Update personal profile data — saves as pending for admin approval.
     */
    public function updateProfile(int $userId, array $data, $foto = null)
    {
        $alumni = $this->profileRepository->getProfileByUserId($userId);

        if (!$alumni) {
            throw new \Exception('Profil alumni tidak ditemukan.');
        }

        return DB::transaction(function () use ($alumni, $data, $foto) {
            // Check for existing pending personal_info update
            $existingPending = PendingProfileUpdate::where('id_alumni', $alumni->id_alumni)
                ->where('section', 'personal_info')
                ->where('status', 'pending')
                ->first();

            if ($existingPending) {
                throw new \Exception('Anda sudah memiliki pembaruan profil yang sedang menunggu persetujuan admin.');
            }

            // Handle foto upload to temp location
            $fotoPath = null;
            if ($foto) {
                try {
                    $result = $this->storeWithThumbnail($foto, 'alumni/foto/pending');
                    $fotoPath = $result['path'];
                } catch (\Error $e) {
                    $fotoPath = $foto->store('alumni/foto/pending', 'public');
                }
            }

            // Extract skills and social media
            $skills = $data['skills'] ?? null;
            $socialMedia = $data['social_media'] ?? null;
            unset($data['skills'], $data['social_media']);

            // Build old data snapshot
            $oldData = [
                'nama_alumni' => $alumni->nama_alumni,
                'nis' => $alumni->nis,
                'nisn' => $alumni->nisn,
                'jenis_kelamin' => $alumni->jenis_kelamin,
                'tanggal_lahir' => $alumni->tanggal_lahir?->format('Y-m-d'),
                'tempat_lahir' => $alumni->tempat_lahir,
                'tahun_masuk' => $alumni->tahun_masuk,
                'alamat' => $alumni->alamat,
                'no_hp' => $alumni->no_hp,
                'id_jurusan' => $alumni->id_jurusan,
                'tahun_lulus' => $alumni->tahun_lulus?->format('Y-m-d'),
                'foto' => $alumni->foto,
            ];

            // Build new data
            $newData = $data;
            if ($fotoPath) {
                $newData['foto'] = $fotoPath;
            }

            // Create pending personal info update
            PendingProfileUpdate::create([
                'id_alumni' => $alumni->id_alumni,
                'section' => 'personal_info',
                'action' => 'update',
                'old_data' => $oldData,
                'new_data' => $newData,
                'foto_path' => $fotoPath,
            ]);

            // If skills are provided, create separate pending update
            if ($skills !== null) {
                $existingSkillsPending = PendingProfileUpdate::where('id_alumni', $alumni->id_alumni)
                    ->where('section', 'skills')
                    ->where('status', 'pending')
                    ->first();

                if (!$existingSkillsPending) {
                    $oldSkills = $alumni->skills->pluck('id_skills')->toArray();
                    PendingProfileUpdate::create([
                        'id_alumni' => $alumni->id_alumni,
                        'section' => 'skills',
                        'action' => 'update',
                        'old_data' => ['skill_ids' => $oldSkills],
                        'new_data' => ['skill_ids' => $skills],
                    ]);
                }
            }

            // If social media are provided, create separate pending update
            if ($socialMedia !== null) {
                $existingSocialPending = PendingProfileUpdate::where('id_alumni', $alumni->id_alumni)
                    ->where('section', 'social_media')
                    ->where('status', 'pending')
                    ->first();

                if (!$existingSocialPending) {
                    $oldSocial = $alumni->socialMedia->map(fn($sm) => [
                        'id_sosmed' => $sm->pivot->id_sosmed ?? $sm->id_sosmed,
                        'url' => $sm->pivot->url ?? '',
                    ])->toArray();
                    PendingProfileUpdate::create([
                        'id_alumni' => $alumni->id_alumni,
                        'section' => 'social_media',
                        'action' => 'update',
                        'old_data' => ['social_media' => $oldSocial],
                        'new_data' => ['social_media' => $socialMedia],
                    ]);
                }
            }

            return $this->profileRepository->getAlumniWithRelations($alumni->id_alumni);
        });
    }

    /**
     * Update career status (add new riwayat_status).
     * Only career status changes go through approval_status on riwayat_status.
     * Does NOT change alumni.status_create — that is only for initial registration.
     */
    public function updateCareerStatus(int $userId, array $data)
    {
        $alumni = $this->profileRepository->getProfileByUserId($userId);

        if (!$alumni) {
            throw new \Exception('Profil alumni tidak ditemukan.');
        }

        return DB::transaction(function () use ($alumni, $data) {
            // --- Duplicate prevention ---
            // Check if an identical pending riwayat already exists for this alumni + status
            $existingPending = RiwayatStatus::where('id_alumni', $alumni->id_alumni)
                ->where('id_status', $data['id_status'])
                ->where('approval_status', 'pending')
                ->first();

            if ($existingPending) {
                throw new \Exception('Anda sudah memiliki perubahan status karir yang sedang menunggu persetujuan admin.');
            }

            // Create riwayat status with pending approval
            $riwayat = $this->profileRepository->createRiwayatStatus($alumni->id_alumni, [
                'id_status' => $data['id_status'],
                'tahun_mulai' => $data['tahun_mulai'] ?? null,
                'tahun_selesai' => $data['tahun_selesai'] ?? null,
                'approval_status' => 'pending',
            ]);

            // Create career detail based on status type
            if (!empty($data['pekerjaan'])) {
                $perusahaan = Perusahaan::firstOrCreate(
                    ['nama_perusahaan' => $data['pekerjaan']['nama_perusahaan']],
                    [
                        'id_kota' => $data['pekerjaan']['id_kota'] ?? null,
                        'jalan' => $data['pekerjaan']['jalan'] ?? '',
                    ]
                );

                Pekerjaan::create([
                    'posisi' => $data['pekerjaan']['posisi'],
                    'id_perusahaan' => $perusahaan->id_perusahaan,
                    'id_riwayat' => $riwayat->id_riwayat,
                ]);
            }

            // Handle kuliah data - support both 'kuliah' and 'universitas' keys from frontend
            $kuliahData = $data['kuliah'] ?? $data['universitas'] ?? null;
            if (!empty($kuliahData)) {
                // Resolve nama_universitas to id_universitas if needed
                $idUniversitas = $kuliahData['id_universitas'] ?? null;
                if (!$idUniversitas && !empty($kuliahData['nama_universitas'])) {
                    $univ = Universitas::firstOrCreate(
                        ['nama_universitas' => $kuliahData['nama_universitas']]
                    );
                    $idUniversitas = $univ->id_universitas;
                }

                if ($idUniversitas) {
                    Kuliah::create([
                        'id_universitas' => $idUniversitas,
                        'id_jurusanKuliah' => $kuliahData['id_jurusanKuliah'] ?? null,
                        'jalur_masuk' => $kuliahData['jalur_masuk'] ?? null,
                        'jenjang' => $kuliahData['jenjang'] ?? null,
                        'id_riwayat' => $riwayat->id_riwayat,
                    ]);
                }
            }

            if (!empty($data['wirausaha'])) {
                Wirausaha::create([
                    'id_bidang' => $data['wirausaha']['id_bidang'],
                    'nama_usaha' => $data['wirausaha']['nama_usaha'],
                    'id_riwayat' => $riwayat->id_riwayat,
                ]);
            }

            // Career status changes use their own approval_status on riwayat_status.
            // Do NOT change alumni.status_create — that is only for initial user registration approval.

            return $riwayat->load([
                'status',
                'pekerjaan.perusahaan.kota.provinsi',
                'kuliah.universitas',
                'kuliah.jurusanKuliah',
                'wirausaha.bidangUsaha',
            ]);
        });
    }

    /**
     * Update existing career status (riwayat_status) directly.
     * This method updates the existing entry without creating a new pending one.
     */
    public function updateExistingCareerStatus(int $userId, int $riwayatId, array $data)
    {
        $alumni = $this->profileRepository->getProfileByUserId($userId);

        if (!$alumni) {
            throw new \Exception('Profil alumni tidak ditemukan.');
        }

        return DB::transaction(function () use ($alumni, $riwayatId, $data) {
            // Find the riwayat status and verify ownership
            $riwayat = RiwayatStatus::where('id_riwayat', $riwayatId)
                ->where('id_alumni', $alumni->id_alumni)
                ->first();

            if (!$riwayat) {
                throw new \Exception('Riwayat status tidak ditemukan atau tidak memiliki akses.');
            }

            // Update basic fields
            $riwayat->update([
                'id_status' => $data['id_status'] ?? $riwayat->id_status,
                'tahun_mulai' => $data['tahun_mulai'] ?? $riwayat->tahun_mulai,
                'tahun_selesai' => $data['tahun_selesai'] ?? $riwayat->tahun_selesai,
            ]);

            // Update career detail based on status type
            if (!empty($data['pekerjaan'])) {
                // Update or create pekerjaan
                $perusahaan = Perusahaan::firstOrCreate(
                    ['nama_perusahaan' => $data['pekerjaan']['nama_perusahaan']],
                    [
                        'id_kota' => $data['pekerjaan']['id_kota'] ?? null,
                        'jalan' => $data['pekerjaan']['jalan'] ?? '',
                    ]
                );

                Pekerjaan::updateOrCreate(
                    ['id_riwayat' => $riwayat->id_riwayat],
                    [
                        'posisi' => $data['pekerjaan']['posisi'],
                        'id_perusahaan' => $perusahaan->id_perusahaan,
                    ]
                );
            }

            // Handle kuliah data
            $kuliahData = $data['kuliah'] ?? $data['universitas'] ?? null;
            if (!empty($kuliahData)) {
                $idUniversitas = $kuliahData['id_universitas'] ?? null;
                if (!$idUniversitas && !empty($kuliahData['nama_universitas'])) {
                    $univ = Universitas::firstOrCreate(
                        ['nama_universitas' => $kuliahData['nama_universitas']]
                    );
                    $idUniversitas = $univ->id_universitas;
                }

                if ($idUniversitas) {
                    Kuliah::updateOrCreate(
                        ['id_riwayat' => $riwayat->id_riwayat],
                        [
                            'id_universitas' => $idUniversitas,
                            'id_jurusanKuliah' => $kuliahData['id_jurusanKuliah'] ?? null,
                            'jalur_masuk' => $kuliahData['jalur_masuk'] ?? null,
                            'jenjang' => $kuliahData['jenjang'] ?? null,
                        ]
                    );
                }
            }

            if (!empty($data['wirausaha'])) {
                Wirausaha::updateOrCreate(
                    ['id_riwayat' => $riwayat->id_riwayat],
                    [
                        'id_bidang' => $data['wirausaha']['id_bidang'],
                        'nama_usaha' => $data['wirausaha']['nama_usaha'],
                    ]
                );
            }

            return $riwayat->load([
                'status',
                'pekerjaan.perusahaan.kota.provinsi',
                'kuliah.universitas',
                'kuliah.jurusanKuliah',
                'wirausaha.bidangUsaha',
            ]);
        });
    }
}
