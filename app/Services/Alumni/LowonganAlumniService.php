<?php

namespace App\Services\Alumni;

use App\Interfaces\Alumni\LowonganAlumniRepositoryInterface;
use App\Models\Alumni;

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
}
