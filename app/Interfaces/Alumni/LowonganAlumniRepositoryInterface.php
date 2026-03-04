<?php

namespace App\Interfaces\Alumni;

interface LowonganAlumniRepositoryInterface
{
    /**
     * Get published lowongan sorted by skill match for alumni.
     * Alumni with matching skills see relevant lowongan first.
     */
    public function getPublishedSortedBySkillMatch(array $alumniSkillIds, array $filters = [], int $perPage = 15);

    /**
     * Get lowongan detail by id (only published/approved).
     */
    public function getPublishedById(int $id);

    /**
     * Get saved lowongan for a specific user.
     */
    public function getSavedByUser(int $userId, int $perPage = 15);

    /**
     * Toggle save/unsave a lowongan for the user.
     * Returns true if saved, false if unsaved.
     */
    public function toggleSave(int $userId, int $lowonganId): bool;

    /**
     * Check whether a specific lowongan is saved by the user.
     */
    public function isSavedByUser(int $userId, int $lowonganId): bool;

    /**
     * Get list of lowongan IDs saved by the user.
     */
    public function getSavedLowonganIds(int $userId): array;
}
