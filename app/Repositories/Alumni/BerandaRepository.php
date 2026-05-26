<?php

namespace App\Repositories\Alumni;

use App\Interfaces\Alumni\BerandaRepositoryInterface;
use App\Models\Alumni;

use App\Models\Kuesioner;
use App\Models\Lowongan;
use App\Models\Perusahaan;
use App\Models\Pertanyaan;
use App\Models\RiwayatStatus;

class BerandaRepository implements BerandaRepositoryInterface
{
    /**
     * Get alumni profile with basic relations for beranda summary.
     */
    public function getAlumniProfile(int $userId)
    {
        return Alumni::with([
            'jurusan',
            'user',
            'skills',
            'riwayatStatus' => fn($q) => $q->latest('id_riwayat')->limit(1),
            'riwayatStatus.status',
            'riwayatStatus.pekerjaan.perusahaan',
            'riwayatStatus.kuliah.universitas',
            'riwayatStatus.kuliah.jurusanKuliah',
            'riwayatStatus.wirausaha.bidangUsaha',
        ])
            ->where('id_users', $userId)
            ->first();
    }

    /**
     * Get recently registered & verified alumni (for jejaring alumni section).
     */
    public function getRecentVerifiedAlumni(int $currentUserId, int $limit = 8)
    {
        return Alumni::with([
            'jurusan',
            'riwayatStatus' => fn($q) => $q->latest('id_riwayat')->limit(1),
            'riwayatStatus.status',
            'riwayatStatus.pekerjaan.perusahaan',
            'riwayatStatus.kuliah.universitas',
            'riwayatStatus.kuliah.jurusanKuliah',
            'riwayatStatus.wirausaha',
        ])
            ->where('status_create', 'ok')
            ->where('id_users', '!=', $currentUserId)
            ->whereDoesntHave('riwayatStatus', function ($rq) {
                $rq->whereHas('status', fn($sq) => $sq->where('nama_status', 'Siswa Aktif'))
                    ->whereRaw('id_riwayat = (
                        SELECT MAX(rs2.id_riwayat)
                        FROM riwayat_status rs2
                        WHERE rs2.id_alumni = riwayat_status.id_alumni
                    )');
            })
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get latest published & approved lowongan (for beranda job section).
     */
    public function getLatestPublishedLowongan(int $limit = 6)
    {
        return Lowongan::with(['perusahaan.kota.provinsi', 'skills'])
            ->where('approval_status', 'approved')
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top companies ranked by alumni count.
     */
    public function getTopPerusahaan(int $limit = 5)
    {
        return Perusahaan::withCount(['pekerjaan as alumni_count' => function ($query) {
            $query->whereHas('riwayatStatus', function ($q) {
                $q->whereHas('alumni', fn($a) => $a->where('status_create', 'ok'));
            });
        }])
            ->with('kota.provinsi')
            ->having('alumni_count', '>', 0)
            ->orderByDesc('alumni_count')
            ->limit($limit)
            ->get();
    }



    /**
     * Get pending kuesioner for alumni filtered by current career status.
     * Only returns active kuesioner matching the alumni's status that have NOT been fully answered.
     */
    public function getPendingKuesioner(int $userId, ?int $statusId = null)
    {
        $query = Kuesioner::with('statusKarir')
            ->withCount('pertanyaan')
            ->where('status', 'aktif')
            ->whereNotNull('tanggal_publikasi')
            ->where(function ($q) {
                $q->whereNull('tanggal_mulai')
                    ->orWhere('tanggal_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now());
            });

        // Filter by current career status when provided
        // Include BOTH status-specific AND global kuesioner (id_status IS NULL)
        $query->where(function ($q) use ($statusId) {
            $q->whereNull('id_status');
            if ($statusId !== null) {
                $q->orWhere('id_status', $statusId);
            }
        });

        $kuesioners = $query->orderByDesc('tanggal_publikasi')->get();
        
        if ($kuesioners->isEmpty()) {
            return collect();
        }

        // Batch: get answered counts for all kuesioners at once (single query)
        $kuesionerIds = $kuesioners->pluck('id_kuesioner');
        $answeredCounts = Pertanyaan::whereIn('id_kuesioner', $kuesionerIds)
            ->whereHas('jawaban', fn($q) => $q->where('id_user', $userId))
            ->selectRaw('id_kuesioner, COUNT(*) as answered')
            ->groupBy('id_kuesioner')
            ->pluck('answered', 'id_kuesioner');

        return $kuesioners->filter(function ($kuesioner) use ($answeredCounts) {
            if ($kuesioner->pertanyaan_count === 0) return false;
            $answered = $answeredCounts->get($kuesioner->id_kuesioner, 0);
            return $answered < $kuesioner->pertanyaan_count;
        })->values();
    }

    /**
     * Get status pengajuan timeline data for alumni.
     */
    public function getStatusPengajuan(int $userId)
    {
        return Alumni::with('user')
            ->where('id_users', $userId)
            ->first();
    }

    /**
     * Get the current career status id from the alumni's latest riwayat.
     */
    public function getCurrentStatusId(int $userId): ?int
    {
        $alumni = Alumni::where('id_users', $userId)->first();

        if (!$alumni) return null;

        $latestRiwayat = RiwayatStatus::where('id_alumni', $alumni->id_alumni)
            ->latest('id_riwayat')
            ->first();

        return $latestRiwayat?->id_status;
    }

    /**
     * Check whether the alumni has completed all required kuesioner
     * for their current career status.
     */
    public function hasCompletedKuesioner(int $userId, ?int $statusId = null): bool
    {
        $query = Kuesioner::withCount('pertanyaan')
            ->where('status', 'aktif')
            ->whereNotNull('tanggal_publikasi')
            ->where(function ($q) {
                $q->whereNull('tanggal_mulai')
                    ->orWhere('tanggal_mulai', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('tanggal_selesai')
                    ->orWhere('tanggal_selesai', '>=', now());
            });

        // Include BOTH status-specific AND global kuesioner (id_status IS NULL)
        $query->where(function ($q) use ($statusId) {
            $q->whereNull('id_status');
            if ($statusId !== null) {
                $q->orWhere('id_status', $statusId);
            }
        });

        $activeKuesioner = $query->get();

        if ($activeKuesioner->isEmpty()) {
            return true;
        }

        // Batch: get answered counts for all kuesioners in single query
        $kuesionerIds = $activeKuesioner->pluck('id_kuesioner');
        $answeredCounts = Pertanyaan::whereIn('id_kuesioner', $kuesionerIds)
            ->whereHas('jawaban', fn($q) => $q->where('id_user', $userId))
            ->selectRaw('id_kuesioner, COUNT(*) as answered')
            ->groupBy('id_kuesioner')
            ->pluck('answered', 'id_kuesioner');

        foreach ($activeKuesioner as $kuesioner) {
            if ($kuesioner->pertanyaan_count === 0) continue;
            $answered = $answeredCounts->get($kuesioner->id_kuesioner, 0);
            if ($answered < $kuesioner->pertanyaan_count) {
                return false;
            }
        }

        return true;
    }
}
