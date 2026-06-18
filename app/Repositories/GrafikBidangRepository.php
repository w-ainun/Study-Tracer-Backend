<?php

namespace App\Repositories;

use App\Interfaces\GrafikBidangRepositoryInterface;
use App\Models\Alumni;
use App\Models\RiwayatStatus;
use Illuminate\Support\Facades\DB;

class GrafikBidangRepository implements GrafikBidangRepositoryInterface
{
    /**
     * Get overall kesesuaian bidang statistics.
     * Returns counts of sesuai/tidak_sesuai/belum_ditentukan grouped by status type.
     */
    public function getKesesuaianStats(array $filters = []): array
    {
        $query = RiwayatStatus::query()
            ->join('alumni', 'riwayat_status.id_alumni', '=', 'alumni.id_alumni')
            ->join('status', 'riwayat_status.id_status', '=', 'status.id_status')
            ->where('riwayat_status.approval_status', 'approved')
            ->whereIn('status.nama_status', ['Bekerja', 'Kuliah', 'Wirausaha']);

        $this->applyFilters($query, $filters);

        // Overall counts
        $overall = (clone $query)->select([
            DB::raw("SUM(CASE WHEN riwayat_status.is_sesuai_bidang = 1 THEN 1 ELSE 0 END) as sesuai"),
            DB::raw("SUM(CASE WHEN riwayat_status.is_sesuai_bidang = 0 THEN 1 ELSE 0 END) as tidak_sesuai"),
            DB::raw("SUM(CASE WHEN riwayat_status.is_sesuai_bidang IS NULL THEN 1 ELSE 0 END) as belum_ditentukan"),
            DB::raw("COUNT(*) as total"),
        ])->first();

        // Per status type breakdown
        $perStatus = (clone $query)->select([
            'status.nama_status',
            DB::raw("SUM(CASE WHEN riwayat_status.is_sesuai_bidang = 1 THEN 1 ELSE 0 END) as sesuai"),
            DB::raw("SUM(CASE WHEN riwayat_status.is_sesuai_bidang = 0 THEN 1 ELSE 0 END) as tidak_sesuai"),
            DB::raw("SUM(CASE WHEN riwayat_status.is_sesuai_bidang IS NULL THEN 1 ELSE 0 END) as belum_ditentukan"),
            DB::raw("COUNT(*) as total"),
        ])->groupBy('status.nama_status')->get();

        return [
            'overall' => [
                'sesuai' => (int) ($overall->sesuai ?? 0),
                'tidak_sesuai' => (int) ($overall->tidak_sesuai ?? 0),
                'belum_ditentukan' => (int) ($overall->belum_ditentukan ?? 0),
                'total' => (int) ($overall->total ?? 0),
                'persentase_sesuai' => $overall->total > 0
                    ? round(($overall->sesuai / $overall->total) * 100, 1)
                    : 0,
            ],
            'per_status' => $perStatus->map(function ($item) {
                return [
                    'status' => $item->nama_status,
                    'sesuai' => (int) $item->sesuai,
                    'tidak_sesuai' => (int) $item->tidak_sesuai,
                    'belum_ditentukan' => (int) $item->belum_ditentukan,
                    'total' => (int) $item->total,
                    'persentase_sesuai' => $item->total > 0
                        ? round(($item->sesuai / $item->total) * 100, 1)
                        : 0,
                ];
            }),
        ];
    }

    /**
     * Get kesesuaian breakdown per jurusan (for bar chart).
     */
    public function getKesesuaianByJurusan(array $filters = []): array
    {
        $query = RiwayatStatus::query()
            ->join('alumni', 'riwayat_status.id_alumni', '=', 'alumni.id_alumni')
            ->join('jurusan', 'alumni.id_jurusan', '=', 'jurusan.id_jurusan')
            ->join('status', 'riwayat_status.id_status', '=', 'status.id_status')
            ->where('riwayat_status.approval_status', 'approved')
            ->whereIn('status.nama_status', ['Bekerja', 'Kuliah', 'Wirausaha']);

        $this->applyFilters($query, $filters);

        $result = $query->select([
            'jurusan.id_jurusan',
            'jurusan.nama_jurusan',
            DB::raw("SUM(CASE WHEN riwayat_status.is_sesuai_bidang = 1 THEN 1 ELSE 0 END) as sesuai"),
            DB::raw("SUM(CASE WHEN riwayat_status.is_sesuai_bidang = 0 THEN 1 ELSE 0 END) as tidak_sesuai"),
            DB::raw("COUNT(*) as total"),
        ])
        ->groupBy('jurusan.id_jurusan', 'jurusan.nama_jurusan')
        ->orderBy('jurusan.nama_jurusan')
        ->get();

        return $result->map(function ($item) {
            return [
                'id_jurusan' => $item->id_jurusan,
                'nama_jurusan' => $item->nama_jurusan,
                'sesuai' => (int) $item->sesuai,
                'tidak_sesuai' => (int) $item->tidak_sesuai,
                'total' => (int) $item->total,
                'persentase_sesuai' => $item->total > 0
                    ? round(($item->sesuai / $item->total) * 100, 1)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Get kesesuaian breakdown per tahun lulus (for line chart).
     */
    public function getKesesuaianByTahunLulus(array $filters = []): array
    {
        $query = RiwayatStatus::query()
            ->join('alumni', 'riwayat_status.id_alumni', '=', 'alumni.id_alumni')
            ->join('status', 'riwayat_status.id_status', '=', 'status.id_status')
            ->where('riwayat_status.approval_status', 'approved')
            ->whereIn('status.nama_status', ['Bekerja', 'Kuliah', 'Wirausaha'])
            ->whereNotNull('alumni.tahun_lulus');

        $this->applyFilters($query, $filters);

        $result = $query->select([
            DB::raw("YEAR(alumni.tahun_lulus) as tahun"),
            DB::raw("SUM(CASE WHEN riwayat_status.is_sesuai_bidang = 1 THEN 1 ELSE 0 END) as sesuai"),
            DB::raw("SUM(CASE WHEN riwayat_status.is_sesuai_bidang = 0 THEN 1 ELSE 0 END) as tidak_sesuai"),
            DB::raw("COUNT(*) as total"),
        ])
        ->groupBy(DB::raw("YEAR(alumni.tahun_lulus)"))
        ->orderBy('tahun')
        ->get();

        return $result->map(function ($item) {
            return [
                'tahun' => $item->tahun,
                'sesuai' => (int) $item->sesuai,
                'tidak_sesuai' => (int) $item->tidak_sesuai,
                'total' => (int) $item->total,
                'persentase_sesuai' => $item->total > 0
                    ? round(($item->sesuai / $item->total) * 100, 1)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Get detailed alumni list with kesesuaian status (paginated).
     */
    public function getKesesuaianDetail(array $filters = [], int $perPage = 15)
    {
        $query = RiwayatStatus::query()
            ->with([
                'alumni.jurusan',
                'status',
                'pekerjaan.perusahaan',
                'kuliah.universitas',
                'kuliah.jurusanKuliah',
                'wirausaha.bidangUsaha',
            ])
            ->whereHas('status', function ($q) {
                $q->whereIn('nama_status', ['Bekerja', 'Kuliah', 'Wirausaha']);
            })
            ->where('approval_status', 'approved');

        // Filter by kesesuaian
        if (isset($filters['kesesuaian'])) {
            if ($filters['kesesuaian'] === 'sesuai') {
                $query->where('is_sesuai_bidang', true);
            } elseif ($filters['kesesuaian'] === 'tidak_sesuai') {
                $query->where('is_sesuai_bidang', false);
            } elseif ($filters['kesesuaian'] === 'belum') {
                $query->whereNull('is_sesuai_bidang');
            }
        }

        // Filter by jurusan
        if (!empty($filters['id_jurusan'])) {
            $query->whereHas('alumni', function ($q) use ($filters) {
                $q->where('id_jurusan', $filters['id_jurusan']);
            });
        }

        // Filter by tahun lulus
        if (!empty($filters['tahun_lulus'])) {
            $query->whereHas('alumni', function ($q) use ($filters) {
                $q->whereYear('tahun_lulus', $filters['tahun_lulus']);
            });
        }

        // Filter by status type (Bekerja/Kuliah/Wirausaha)
        if (!empty($filters['status_karier'])) {
            $query->whereHas('status', function ($q) use ($filters) {
                $q->where('nama_status', $filters['status_karier']);
            });
        }

        // Search by name
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('alumni', function ($q) use ($search) {
                $q->where('nama_alumni', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('updated_at')->paginate($perPage);
    }

    /**
     * Apply common filters to the query.
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['id_jurusan'])) {
            $query->where('alumni.id_jurusan', $filters['id_jurusan']);
        }

        if (!empty($filters['tahun_lulus'])) {
            $query->whereYear('alumni.tahun_lulus', $filters['tahun_lulus']);
        }

        if (!empty($filters['status_karier'])) {
            $query->where('status.nama_status', $filters['status_karier']);
        }
    }
}
