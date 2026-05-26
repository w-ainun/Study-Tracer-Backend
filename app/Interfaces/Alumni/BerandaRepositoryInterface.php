<?php

namespace App\Interfaces\Alumni;

interface BerandaRepositoryInterface
{
    /**
     * Get alumni profile with basic relations for beranda summary.
     */
    public function getAlumniProfile(int $userId);

    /**
     * Get recently registered & verified alumni (for jejaring alumni section).
     */
    public function getRecentVerifiedAlumni(int $currentUserId, int $limit = 8);

    /**
     * Get latest published & approved lowongan (for beranda job section).
     */
    public function getLatestPublishedLowongan(int $limit = 6);

    /**
     * Get top companies ranked by alumni count.
     */
    public function getTopPerusahaan(int $limit = 5);

    /**
     * Get pending kuesioner for alumni filtered by current career status.
     */
    public function getPendingKuesioner(int $userId, ?int $statusId = null);

    /**
     * Get status pengajuan timeline data for alumni.
     */
    public function getStatusPengajuan(int $userId);

    /**
     * Get the current career status id from the alumni's latest riwayat.
     */
    public function getCurrentStatusId(int $userId): ?int;

    /**
     * Check whether the alumni has completed all required kuesioner
     * for their current career status.
     */
    public function hasCompletedKuesioner(int $userId, ?int $statusId = null): bool;
}
