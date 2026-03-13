<?php

namespace App\Services;

use App\Models\LandingStat;
use App\Models\Alumni;
use App\Models\Lowongan;
use App\Models\Perusahaan;
use App\Models\RiwayatStatus;
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
            // Calculate real-time stats
            $totalAlumni = Alumni::where('status_create', 'ok')->count();
            $totalJobs = Lowongan::where('status', 'published')
                ->where('approval_status', 'approved')
                ->count();

            // Calculate career distribution from riwayat_status
            // Only count current (tahun_selesai IS NULL) and approved records
            $careerStats = RiwayatStatus::selectRaw('id_status, count(DISTINCT id_alumni) as total')
                ->where('approval_status', 'approved')
                ->whereNull('tahun_selesai')
                ->groupBy('id_status')
                ->with('status')
                ->get();

            $totalWithStatus = $careerStats->sum('total');

            // Status IDs: 1=Bekerja, 2=Kuliah, 3=Wirausaha, 4=Belum Bekerja
            $bekerja = $careerStats->where('id_status', 1)->first();
            $kuliah = $careerStats->where('id_status', 2)->first();
            $wirausaha = $careerStats->where('id_status', 3)->first();
            $belumBekerja = $careerStats->where('id_status', 4)->first();

            $pctBekerja = $totalWithStatus > 0 ? round(($bekerja->total ?? 0) / $totalWithStatus * 100) : 0;
            $pctKuliah = $totalWithStatus > 0 ? round(($kuliah->total ?? 0) / $totalWithStatus * 100) : 0;
            $pctWirausaha = $totalWithStatus > 0 ? round(($wirausaha->total ?? 0) / $totalWithStatus * 100) : 0;
            $pctBelumBekerja = $totalWithStatus > 0 ? round(($belumBekerja->total ?? 0) / $totalWithStatus * 100) : 0;

            // Count partner companies (distinct companies from lowongan)
            $partnerCompanies = Lowongan::where('approval_status', 'approved')
                ->distinct('id_perusahaan')
                ->count('id_perusahaan');

            return [
                'total_alumni' => $totalAlumni,
                'total_jobs' => $totalJobs,
                'partner_companies' => $partnerCompanies,
                'total_alumni_with_status' => $totalWithStatus,
                'career_distribution' => [
                    'bekerja' => [
                        'total' => $bekerja->total ?? 0,
                        'percentage' => $pctBekerja,
                    ],
                    'kuliah' => [
                        'total' => $kuliah->total ?? 0,
                        'percentage' => $pctKuliah,
                    ],
                    'wirausaha' => [
                        'total' => $wirausaha->total ?? 0,
                        'percentage' => $pctWirausaha,
                    ],
                    'belum_bekerja' => [
                        'total' => $belumBekerja->total ?? 0,
                        'percentage' => $pctBelumBekerja,
                    ],
                ],
            ];
        });
    }

    /**
     * Get featured job listings for landing page
     */
    public function getFeaturedJobs(int $limit = 4): array
    {
        return Lowongan::with(['perusahaan.kota.provinsi', 'pekerjaan', 'skills'])
            ->where('status', 'published')
            ->where('approval_status', 'approved')
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
        ->where('status_create', 'ok')
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
