<?php

namespace App\Interfaces\Alumni;

interface ProfileRepositoryInterface
{
    /**
     * Get full alumni profile by user ID (with all relations).
     */
    public function getProfileByUserId(int $userId);

    /**
     * Update alumni basic profile fields.
     */
    public function updateProfile(int $alumniId, array $data);

    /**
     * Sync alumni skills (replace all).
     */
    public function syncSkills(int $alumniId, array $skillIds);

    /**
     * Sync alumni social media links (replace all).
     */
    public function syncSocialMedia(int $alumniId, array $socialMediaData);

    /**
     * Create a new riwayat status record.
     */
    public function createRiwayatStatus(int $alumniId, array $data);

    /**
     * Get alumni with all relations freshly loaded.
     */
    public function getAlumniWithRelations(int $alumniId);

    /**
     * Set alumni status_create to pending (requires admin re-approval).
     */
    public function setStatusPending(int $alumniId): void;

    /**
     * Get the latest riwayat status for an alumni.
     */
    public function getLatestRiwayat(int $alumniId);
}
