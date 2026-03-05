<?php

namespace App\Services\Alumni;

use App\Interfaces\Alumni\ProfileRepositoryInterface;
use App\Models\Kuliah;
use App\Models\Pekerjaan;
use App\Models\Perusahaan;
use App\Models\RiwayatStatus;
use App\Models\Universitas;
use App\Models\Wirausaha;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileService
{
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
     * Update personal profile data (nama, alamat, foto, skills, social media).
     * Does NOT change status_create — personal info edits don't need re-approval.
     */
    public function updateProfile(int $userId, array $data, $foto = null)
    {
        $alumni = $this->profileRepository->getProfileByUserId($userId);

        if (!$alumni) {
            throw new \Exception('Profil alumni tidak ditemukan.');
        }

        return DB::transaction(function () use ($alumni, $data, $foto) {
            // Handle foto upload
            if ($foto) {
                if ($alumni->foto) {
                    Storage::disk('public')->delete($alumni->foto);
                }
                $data['foto'] = $foto->store('alumni/foto', 'public');
            }

            // Extract skills and social media before updating profile
            $skills = $data['skills'] ?? null;
            $socialMedia = $data['social_media'] ?? null;
            unset($data['skills'], $data['social_media']);

            // Update basic profile
            $this->profileRepository->updateProfile($alumni->id_alumni, $data);

            // Sync skills if provided
            if ($skills !== null) {
                $this->profileRepository->syncSkills($alumni->id_alumni, $skills);
            }

            // Sync social media if provided
            if ($socialMedia !== null) {
                $this->profileRepository->syncSocialMedia($alumni->id_alumni, $socialMedia);
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
