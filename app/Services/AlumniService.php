<?php

namespace App\Services;

use App\Interfaces\AlumniRepositoryInterface;
use App\Models\Kuliah;
use App\Models\Pekerjaan;
use App\Models\Perusahaan;
use App\Models\Wirausaha;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AlumniService
{
    private AlumniRepositoryInterface $alumniRepository;

    public function __construct(AlumniRepositoryInterface $alumniRepository)
    {
        $this->alumniRepository = $alumniRepository;
    }

    public function getProfile(int $userId)
    {
        return $this->alumniRepository->getAlumniByUserId($userId);
    }

    public function updateProfile(int $userId, array $data, $foto = null)
    {
        $alumni = $this->alumniRepository->getAlumniByUserId($userId);

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
            $this->alumniRepository->updateProfile($alumni->id_alumni, $data);

            // Sync skills if provided
            if ($skills !== null) {
                $this->alumniRepository->syncSkills($alumni->id_alumni, $skills);
            }

            // Sync social media if provided
            if ($socialMedia !== null) {
                $this->alumniRepository->syncSocialMedia($alumni->id_alumni, $socialMedia);
            }

            return $this->alumniRepository->getAlumniWithRelations($alumni->id_alumni);
        });
    }

    public function updateCareerStatus(int $userId, array $data)
    {
        $alumni = $this->alumniRepository->getAlumniByUserId($userId);

        if (!$alumni) {
            throw new \Exception('Profil alumni tidak ditemukan.');
        }

        return DB::transaction(function () use ($alumni, $data) {
            // Create riwayat status
            $riwayat = $this->alumniRepository->createRiwayatStatus($alumni->id_alumni, [
                'id_status' => $data['id_status'],
                'tahun_mulai' => $data['tahun_mulai'] ?? null,
                'tahun_selesai' => $data['tahun_selesai'] ?? null,
            ]);

            // Create career detail based on status type
            if (!empty($data['pekerjaan'])) {
                // Bekerja: create/find perusahaan, then create pekerjaan
                $perusahaan = Perusahaan::firstOrCreate(
                    ['nama_perusahaan' => $data['pekerjaan']['nama_perusahaan']],
                    [
                        'id_kota' => $data['pekerjaan']['id_kota'],
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
                // Kuliah
                Kuliah::create([
                    'id_universitas' => $data['kuliah']['id_universitas'],
                    'id_jurusanKuliah' => $data['kuliah']['id_jurusanKuliah'],
                    'jalur_masuk' => $data['kuliah']['jalur_masuk'],
                    'jenjang' => $data['kuliah']['jenjang'],
                    'id_riwayat' => $riwayat->id_riwayat,
                ]);
            }

            if (!empty($data['wirausaha'])) {
                // Wirausaha
                Wirausaha::create([
                    'id_bidang' => $data['wirausaha']['id_bidang'],
                    'nama_usaha' => $data['wirausaha']['nama_usaha'],
                    'id_riwayat' => $riwayat->id_riwayat,
                ]);
            }

            return $riwayat->load(['status', 'pekerjaan.perusahaan', 'kuliah.universitas', 'kuliah.jurusanKuliah', 'wirausaha.bidangUsaha']);
        });
    }
}
