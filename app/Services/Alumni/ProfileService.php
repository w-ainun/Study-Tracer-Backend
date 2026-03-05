<?php

namespace App\Services\Alumni;

use App\Interfaces\Alumni\ProfileRepositoryInterface;
use App\Models\Kuliah;
use App\Models\Pekerjaan;
use App\Models\Perusahaan;
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
     * Sets status_create to 'pending' — admin must re-approve in User Management.
     */
    public function updateCareerStatus(int $userId, array $data)
    {
        $alumni = $this->profileRepository->getProfileByUserId($userId);

        if (!$alumni) {
            throw new \Exception('Profil alumni tidak ditemukan.');
        }

        return DB::transaction(function () use ($alumni, $data) {
            // Create riwayat status
            $riwayat = $this->profileRepository->createRiwayatStatus($alumni->id_alumni, [
                'id_status' => $data['id_status'],
                'tahun_mulai' => $data['tahun_mulai'] ?? null,
                'tahun_selesai' => $data['tahun_selesai'] ?? null,
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

            if (!empty($data['kuliah'])) {
                Kuliah::create([
                    'id_universitas' => $data['kuliah']['id_universitas'],
                    'id_jurusanKuliah' => $data['kuliah']['id_jurusanKuliah'],
                    'jalur_masuk' => $data['kuliah']['jalur_masuk'],
                    'jenjang' => $data['kuliah']['jenjang'],
                    'id_riwayat' => $riwayat->id_riwayat,
                ]);
            }

            if (!empty($data['wirausaha'])) {
                Wirausaha::create([
                    'id_bidang' => $data['wirausaha']['id_bidang'],
                    'nama_usaha' => $data['wirausaha']['nama_usaha'],
                    'id_riwayat' => $riwayat->id_riwayat,
                ]);
            }

            // Set status_create to 'pending' → admin must re-approve via User Management
            $this->profileRepository->setStatusPending($alumni->id_alumni);

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
