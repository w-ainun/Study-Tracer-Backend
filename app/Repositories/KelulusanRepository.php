<?php

namespace App\Repositories;

use App\Interfaces\KelulusanRepositoryInterface;
use App\Models\CalonLulusan;
use App\Models\RiwayatKelulusan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class KelulusanRepository implements KelulusanRepositoryInterface
{
    // ═══════════════════════════════════════════════
    //  CALON LULUSAN (STAGING TABLE)
    // ═══════════════════════════════════════════════

    /**
     * Get all calon lulusan with optional search/jurusan filter.
     */
    public function getCalonLulusan(array $filters = [], int $perPage = 50)
    {
        $query = CalonLulusan::with('jurusan:id_jurusan,nama_jurusan');

        // Search by nama or NISN
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        // Filter by jurusan
        if (!empty($filters['id_jurusan'])) {
            $query->where('id_jurusan', $filters['id_jurusan']);
        }

        // Filter by jurusan name (from frontend dropdown)
        if (!empty($filters['jurusan']) && $filters['jurusan'] !== 'Semua Jurusan') {
            $query->whereHas('jurusan', function ($q) use ($filters) {
                $q->where('nama_jurusan', $filters['jurusan']);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a single calon lulusan entry.
     */
    public function createCalonLulusan(array $data)
    {
        $calon = CalonLulusan::create($data);
        $this->clearKelulusanCache();
        return $calon->load('jurusan:id_jurusan,nama_jurusan');
    }

    /**
     * Bulk insert from Excel import.
     * Uses insertOrIgnore to skip duplicates gracefully.
     */
    public function bulkCreateCalonLulusan(array $rows): int
    {
        $now = now();
        $prepared = array_map(function ($row) use ($now) {
            return array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $rows);

        // Insert in chunks of 500 to avoid packet size limits
        $inserted = 0;
        foreach (array_chunk($prepared, 500) as $chunk) {
            $inserted += DB::table('calon_lulusan')->insert($chunk) ? count($chunk) : 0;
        }

        $this->clearKelulusanCache();
        return $inserted;
    }

    /**
     * Delete a single calon lulusan by ID.
     */
    public function deleteCalonLulusan(int $id): bool
    {
        $calon = CalonLulusan::findOrFail($id);
        $calon->delete();
        $this->clearKelulusanCache();
        return true;
    }

    /**
     * Clear all calon lulusan (after batch graduation).
     */
    public function clearCalonLulusan(): int
    {
        $count = CalonLulusan::count();
        CalonLulusan::truncate();
        $this->clearKelulusanCache();
        return $count;
    }

    /**
     * Count calon lulusan with optional filters.
     */
    public function countCalonLulusan(array $filters = []): int
    {
        $query = CalonLulusan::query();

        if (!empty($filters['id_jurusan'])) {
            $query->where('id_jurusan', $filters['id_jurusan']);
        }

        return $query->count();
    }

    // ═══════════════════════════════════════════════
    //  RIWAYAT KELULUSAN (CONFIRMED GRADUATES)
    // ═══════════════════════════════════════════════

    /**
     * Get confirmed graduates with search, jurusan, and tahun filters.
     */
    public function getRiwayatKelulusan(array $filters = [], int $perPage = 15)
    {
        $query = RiwayatKelulusan::with('jurusan:id_jurusan,nama_jurusan');

        // Search by nama or NISN
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        // Filter by jurusan ID
        if (!empty($filters['id_jurusan'])) {
            $query->where('id_jurusan', $filters['id_jurusan']);
        }

        // Filter by jurusan name (from frontend dropdown)
        if (!empty($filters['jurusan']) && $filters['jurusan'] !== 'Semua Jurusan') {
            $query->whereHas('jurusan', function ($q) use ($filters) {
                $q->where('nama_jurusan', $filters['jurusan']);
            });
        }

        // Filter by tahun_lulus
        if (!empty($filters['tahun_lulus'])) {
            $query->where('tahun_lulus', $filters['tahun_lulus']);
        }

        // Filter by tahun (from frontend dropdown, may be string like "2023")
        if (!empty($filters['tahun']) && $filters['tahun'] !== 'Semua Tahun') {
            $query->where('tahun_lulus', (int) $filters['tahun']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Bulk insert confirmed graduates (from staging).
     */
    public function bulkCreateRiwayatKelulusan(array $rows): int
    {
        $now = now();
        $prepared = array_map(function ($row) use ($now) {
            return array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $rows);

        $inserted = 0;
        foreach (array_chunk($prepared, 500) as $chunk) {
            $inserted += DB::table('riwayat_kelulusan')->insert($chunk) ? count($chunk) : 0;
        }

        $this->clearKelulusanCache();
        return $inserted;
    }

    /**
     * Get distinct graduation years for filter dropdown.
     */
    public function getDistinctTahunLulus(): array
    {
        return Cache::remember('kelulusan.tahun_lulus', 600, function () {
            return RiwayatKelulusan::select('tahun_lulus')
                ->distinct()
                ->orderBy('tahun_lulus', 'desc')
                ->pluck('tahun_lulus')
                ->toArray();
        });
    }

    /**
     * Get kelulusan statistics for dashboard.
     */
    public function getStats(): array
    {
        return Cache::remember('kelulusan.stats', 300, function () {
            return [
                'total_calon'   => CalonLulusan::count(),
                'total_lulusan' => RiwayatKelulusan::count(),
                'tahun_ini'     => RiwayatKelulusan::where('tahun_lulus', date('Y'))->count(),
                'per_jurusan'   => RiwayatKelulusan::select('id_jurusan', DB::raw('COUNT(*) as total'))
                    ->groupBy('id_jurusan')
                    ->with('jurusan:id_jurusan,nama_jurusan')
                    ->get()
                    ->map(fn ($item) => [
                        'jurusan' => $item->jurusan?->nama_jurusan ?? '-',
                        'total'   => $item->total,
                    ])
                    ->toArray(),
            ];
        });
    }

    /**
     * Stream riwayat kelulusan for export (CSV/Excel).
     * Uses chunk() to avoid memory issues on large datasets.
     */
    public function streamRiwayatKelulusan(array $filters = [], callable $callback)
    {
        $query = RiwayatKelulusan::with('jurusan:id_jurusan,nama_jurusan');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['jurusan']) && $filters['jurusan'] !== 'Semua Jurusan') {
            $query->whereHas('jurusan', function ($q) use ($filters) {
                $q->where('nama_jurusan', $filters['jurusan']);
            });
        }

        if (!empty($filters['tahun_lulus'])) {
            $query->where('tahun_lulus', $filters['tahun_lulus']);
        }

        if (!empty($filters['tahun']) && $filters['tahun'] !== 'Semua Tahun') {
            $query->where('tahun_lulus', (int) $filters['tahun']);
        }

        $query->orderBy('created_at', 'desc')->chunk(500, $callback);
    }

    // ── Cache Helpers ────────────────────────────

    private function clearKelulusanCache(): void
    {
        Cache::forget('kelulusan.stats');
        Cache::forget('kelulusan.tahun_lulus');
    }
}
