<?php

namespace App\Repositories\Alumni;

use App\Interfaces\Alumni\AlumniDirectoryRepositoryInterface;
use App\Models\Alumni;
use App\Models\RiwayatStatus;
use App\Models\Status;
use App\Models\Universitas;

class AlumniDirectoryRepository implements AlumniDirectoryRepositoryInterface
{
    /**
     * Get paginated verified alumni with search + filters.
     */
    public function getVerifiedAlumni(array $filters = [], int $perPage = 12)
    {
        $query = Alumni::with([
            'jurusan',
            'riwayatStatus' => fn($q) => $q->latest('id_riwayat')->limit(1),
            'riwayatStatus.status',
            'riwayatStatus.pekerjaan.perusahaan',
            'riwayatStatus.kuliah.universitas',
            'riwayatStatus.kuliah.jurusanKuliah',
            'riwayatStatus.wirausaha',
        ])
            ->where('status_create', 'ok');

        // Search by nama, perusahaan, or role/posisi
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_alumni', 'like', "%{$search}%")
                    ->orWhereHas('riwayatStatus.pekerjaan.perusahaan', function ($pq) use ($search) {
                        $pq->where('nama_perusahaan', 'like', "%{$search}%");
                    })
                    ->orWhereHas('riwayatStatus.pekerjaan', function ($pq) use ($search) {
                        $pq->where('posisi', 'like', "%{$search}%");
                    })
                    ->orWhereHas('riwayatStatus.wirausaha', function ($wq) use ($search) {
                        $wq->where('nama_usaha', 'like', "%{$search}%");
                    })
                    ->orWhereHas('riwayatStatus.kuliah.universitas', function ($uq) use ($search) {
                        $uq->where('nama_universitas', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by tahun lulus (graduation year)
        if (!empty($filters['tahun'])) {
            $query->whereYear('tahun_lulus', $filters['tahun']);
        }

        // Filter by status karir (Bekerja, Kuliah, Wirausaha, Mencari Pekerjaan)
        if (!empty($filters['status'])) {
            $statusName = $filters['status'];
            $query->whereHas('riwayatStatus', function ($rq) use ($statusName) {
                $rq->whereHas('status', fn($sq) => $sq->where('nama_status', $statusName))
                    ->whereRaw('id_riwayat = (
                        SELECT MAX(rs2.id_riwayat)
                        FROM riwayat_status rs2
                        WHERE rs2.id_alumni = riwayat_status.id_alumni
                    )');
            });
        }

        // Filter by universitas (only for alumni who are studying)
        if (!empty($filters['universitas'])) {
            $univName = $filters['universitas'];
            $query->whereHas('riwayatStatus.kuliah.universitas', function ($uq) use ($univName) {
                $uq->where('nama_universitas', $univName);
            });
        }

        return $query->orderByDesc('updated_at')->paginate($perPage);
    }

    /**
     * Get a single verified alumni with full relations for public profile view.
     */
    public function getAlumniPublicProfile(int $alumniId)
    {
        return Alumni::with([
            'jurusan',
            'skills',
            'socialMedia',
            'riwayatStatus' => fn($q) => $q->where('approval_status', 'approved')->orderByDesc('id_riwayat'),
            'riwayatStatus.status',
            'riwayatStatus.pekerjaan.perusahaan.kota.provinsi',
            'riwayatStatus.kuliah.universitas',
            'riwayatStatus.kuliah.jurusanKuliah',
            'riwayatStatus.wirausaha.bidangUsaha',
        ])
            ->where('status_create', 'ok')
            ->findOrFail($alumniId);
    }

    /**
     * Get distinct graduation years from verified alumni.
     */
    public function getTahunLulusOptions(): array
    {
        return Alumni::where('status_create', 'ok')
            ->whereNotNull('tahun_lulus')
            ->selectRaw('YEAR(tahun_lulus) as tahun')
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun')
            ->map(fn($y) => (string) $y)
            ->toArray();
    }

    /**
     * Get distinct status names from active riwayat.
     */
    public function getStatusOptions(): array
    {
        return Status::whereHas('riwayatStatus.alumni', function ($q) {
            $q->where('status_create', 'ok');
        })
            ->distinct()
            ->pluck('nama_status')
            ->toArray();
    }

    /**
     * Get distinct universitas names from verified alumni who are studying.
     */
    public function getUniversitasOptions(): array
    {
        return Universitas::whereHas('kuliah.riwayatStatus.alumni', function ($q) {
            $q->where('status_create', 'ok');
        })
            ->distinct()
            ->pluck('nama_universitas')
            ->toArray();
    }
}
