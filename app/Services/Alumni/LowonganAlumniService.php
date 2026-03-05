<?php

namespace App\Services\Alumni;

use App\Interfaces\Alumni\LowonganAlumniRepositoryInterface;
use App\Models\Alumni;
use App\Models\Kota;
use App\Models\Perusahaan;

class LowonganAlumniService
{
    private LowonganAlumniRepositoryInterface $lowonganRepository;

    public function __construct(LowonganAlumniRepositoryInterface $lowonganRepository)
    {
        $this->lowonganRepository = $lowonganRepository;
    }

    /**
     * Get published lowongan for alumni, sorted by skill match.
     * Also attaches saved-lowongan IDs so frontend can show bookmark state.
     */
    public function getPublishedForAlumni(int $userId, array $filters = [], int $perPage = 15): array
    {
        $alumni = Alumni::where('id_users', $userId)->first();

        $alumniSkillIds = [];
        if ($alumni) {
            $alumniSkillIds = $alumni->skills()->pluck('skills.id_skills')->toArray();
        }

        $lowongan = $this->lowonganRepository->getPublishedSortedBySkillMatch($alumniSkillIds, $filters, $perPage);
        $savedIds = $this->lowonganRepository->getSavedLowonganIds($userId);

        return [
            'lowongan' => $lowongan,
            'saved_ids' => $savedIds,
        ];
    }

    /**
     * Get detail of a published lowongan with is_saved flag.
     */
    public function getDetail(int $userId, int $lowonganId): array
    {
        $lowongan = $this->lowonganRepository->getPublishedById($lowonganId);
        $isSaved = $this->lowonganRepository->isSavedByUser($userId, $lowonganId);

        return [
            'lowongan' => $lowongan,
            'is_saved' => $isSaved,
        ];
    }

    /**
     * Get lowongan saved by alumni.
     */
    public function getSavedLowongan(int $userId, int $perPage = 15)
    {
        return $this->lowonganRepository->getSavedByUser($userId, $perPage);
    }

    /**
     * Toggle save/unsave a lowongan.
     */
    public function toggleSave(int $userId, int $lowonganId): bool
    {
        // Verify the lowongan exists and is published
        $this->lowonganRepository->getPublishedById($lowonganId);

        return $this->lowonganRepository->toggleSave($userId, $lowonganId);
    }

    // ── Alumni Lowongan Submission ───────────────────

    /**
     * Create a new lowongan submitted by an alumni.
     * - Always starts as draft + pending
     * - Auto-creates Perusahaan from nama_perusahaan if needed
     * - Syncs skills
     */
    public function createLowongan(array $data): \App\Models\Lowongan
    {
        // Force alumni-submitted lowongan to draft + pending
        $data['status'] = 'draft';
        $data['approval_status'] = 'pending';

        // Auto-create Perusahaan from nama_perusahaan if no id_perusahaan
        if (!empty($data['nama_perusahaan']) && empty($data['id_perusahaan'])) {
            $defaultCityId = Kota::value('id_kota') ?? 1;

            if (!empty($data['id_kota'])) {
                $defaultCityId = $data['id_kota'];
            } elseif (!empty($data['lokasi'])) {
                $city = Kota::where('nama_kota', 'like', '%' . $data['lokasi'] . '%')->first();
                if ($city) $defaultCityId = $city->id_kota;
            }

            $perusahaan = Perusahaan::firstOrCreate(
                ['nama_perusahaan' => $data['nama_perusahaan']],
                ['jalan' => $data['lokasi'] ?? '-', 'id_kota' => $defaultCityId]
            );
            $data['id_perusahaan'] = $perusahaan->id_perusahaan;
        }
        
        // Remove non-lowongan fields before mass assignment
        unset($data['nama_perusahaan'], $data['id_kota']);

        // Extract & remove skills before creating lowongan
        $skillIds = $data['skills'] ?? [];
        unset($data['skills']);

        $lowongan = $this->lowonganRepository->create($data);

        if (!empty($skillIds)) {
            $this->lowonganRepository->syncSkills($lowongan->id_lowongan, $skillIds);
            $lowongan->load('skills');
        }

        return $lowongan;
    }

    /**
     * Get alumni's own lowongan (all statuses) for "Lowongan Saya" page.
     */
    public function getMyLowongan(int $userId, array $filters = [], int $perPage = 15)
    {
        return $this->lowonganRepository->getByUserId($userId, $filters, $perPage);
    }
}
