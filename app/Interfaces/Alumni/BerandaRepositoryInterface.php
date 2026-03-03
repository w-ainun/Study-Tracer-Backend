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
    public function getRecentVerifiedAlumni(int $limit = 8);

    /**
     * Get latest published & approved lowongan (for beranda job section).
     */
    public function getLatestPublishedLowongan(int $limit = 6);

    /**
     * Get top companies ranked by alumni count.
     */
    public function getTopPerusahaan(int $limit = 5);

    /**
     * Get pending kuesioner for alumni based on their status.
     */
    public function getPendingKuesioner(int $userId);

    /**
     * Get status pengajuan timeline data for alumni.
     */
    public function getStatusPengajuan(int $userId);
}
