<?php

namespace App\Services;

use App\Models\LandingStat;
use App\Models\Alumni;
use App\Models\Lowongan;
use App\Models\Perusahaan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LandingService
{
    /**
     * Get statistics for landing page
     */
    public function getStats(): array
    {
        return Cache::remember('landing.stats', 3600, function () {
            // Get custom stats from database
            $customStats = LandingStat::pluck('value', 'key')->toArray();

            // Calculate real-time stats
            $totalAlumni = Alumni::where('status_create', 'approved')->count();
            $totalJobs = Lowongan::where('status', 'published')
                ->where('approval_status', 'approved')
                ->count();

            // Merge with custom stats
            return [
                'total_alumni' => $customStats['total_alumni'] ?? $totalAlumni,
                'employment_rate' => $customStats['employment_rate'] ?? '92',
                'partner_companies' => $customStats['partner_companies'] ?? '100',
                'active_workers_percentage' => $customStats['active_workers_percentage'] ?? '85',
                'total_jobs' => $totalJobs,
            ];
        });
    }

    /**
     * Get featured job listings for landing page
     */
    public function getFeaturedJobs(int $limit = 6): array
    {
        return Lowongan::with(['perusahaan.kota.provinsi', 'pekerjaan', 'skills'])
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->where('lowongan_selesai', '>=', now())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get featured alumni for landing page
     */
    public function getFeaturedAlumni(int $limit = 8): array
    {
        return Alumni::with([
            'jurusan',
            'riwayatStatus' => function ($query) {
                $query->where('approval_status', 'approved')
                      ->orderBy('tahun_mulai', 'desc')
                      ->limit(1);
            },
            'riwayatStatus.status',
            'riwayatStatus.pekerjaan.perusahaan',
            'riwayatStatus.kuliah.universitas',
            'riwayatStatus.wirausaha',
        ])
        ->where('status_create', 'approved')
        ->inRandomOrder()
        ->limit($limit)
        ->get()
        ->toArray();
    }

    /**
     * Update landing stat (admin only)
     */
    public function updateStat(string $key, string $value): void
    {
        LandingStat::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget('landing.stats');
    }
}
