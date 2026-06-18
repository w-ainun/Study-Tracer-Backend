<?php

namespace App\Repositories;

use App\Interfaces\LamaranRepositoryInterface;
use App\Models\Lamaran;
use Illuminate\Support\Facades\DB;

class LamaranRepository implements LamaranRepositoryInterface
{
    /**
     * Alumni applies to a lowongan.
     */
    public function apply(int $alumniId, int $lowonganId, ?string $catatan = null)
    {
        return Lamaran::create([
            'id_alumni' => $alumniId,
            'id_lowongan' => $lowonganId,
            'status' => 'pending',
            'tanggal_apply' => now(),
            'catatan' => $catatan,
        ]);
    }

    /**
     * Get lamaran history for a specific alumni (paginated).
     */
    public function getByAlumni(int $alumniId, array $filters = [], int $perPage = 15)
    {
        $query = Lamaran::with([
            'lowongan.perusahaan.kota.provinsi',
            'lowongan.pekerjaan',
            'lowongan.skills',
        ])
        ->forAlumni($alumniId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('lowongan', function ($q) use ($search) {
                $q->where('judul_lowongan', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('tanggal_apply')->paginate($perPage);
    }

    /**
     * Update status of a lamaran.
     */
    public function updateStatus(int $lamaranId, string $status, ?string $catatanAdmin = null)
    {
        $lamaran = Lamaran::findOrFail($lamaranId);
        $lamaran->update([
            'status' => $status,
            'tanggal_respon' => now(),
            'catatan_admin' => $catatanAdmin,
        ]);

        return $lamaran->fresh(['alumni', 'lowongan.perusahaan']);
    }

    /**
     * Get all lamaran for a specific lowongan (paginated).
     */
    public function getByLowongan(int $lowonganId, array $filters = [], int $perPage = 15)
    {
        $query = Lamaran::with([
            'alumni.jurusan',
            'alumni.skills',
            'alumni.user',
        ])
        ->forLowongan($lowonganId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('tanggal_apply')->paginate($perPage);
    }

    /**
     * Get lamaran statistics for an alumni.
     */
    public function getAlumniStats(int $alumniId): array
    {
        $stats = Lamaran::forAlumni($alumniId)
            ->select([
                DB::raw("COUNT(*) as total"),
                DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN status = 'diterima' THEN 1 ELSE 0 END) as diterima"),
                DB::raw("SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak"),
            ])
            ->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'pending' => (int) ($stats->pending ?? 0),
            'diterima' => (int) ($stats->diterima ?? 0),
            'ditolak' => (int) ($stats->ditolak ?? 0),
        ];
    }

    /**
     * Get global lamaran statistics (admin).
     */
    public function getGlobalStats(): array
    {
        $stats = Lamaran::select([
            DB::raw("COUNT(*) as total"),
            DB::raw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending"),
            DB::raw("SUM(CASE WHEN status = 'diterima' THEN 1 ELSE 0 END) as diterima"),
            DB::raw("SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as ditolak"),
            DB::raw("COUNT(DISTINCT id_alumni) as total_pelamar"),
            DB::raw("COUNT(DISTINCT id_lowongan) as total_lowongan_dilamar"),
        ])->first();

        return [
            'total' => (int) ($stats->total ?? 0),
            'pending' => (int) ($stats->pending ?? 0),
            'diterima' => (int) ($stats->diterima ?? 0),
            'ditolak' => (int) ($stats->ditolak ?? 0),
            'total_pelamar' => (int) ($stats->total_pelamar ?? 0),
            'total_lowongan_dilamar' => (int) ($stats->total_lowongan_dilamar ?? 0),
        ];
    }

    /**
     * Get all lamaran with filters (admin, paginated).
     */
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Lamaran::with([
            'alumni.jurusan',
            'alumni.user',
            'lowongan.perusahaan',
            'lowongan.pekerjaan',
        ]);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->whereHas('alumni', function ($sq) use ($search) {
                    $sq->where('nama_alumni', 'like', "%{$search}%");
                })->orWhereHas('lowongan', function ($sq) use ($search) {
                    $sq->where('judul_lowongan', 'like', "%{$search}%");
                });
            });
        }

        if (!empty($filters['id_lowongan'])) {
            $query->where('id_lowongan', $filters['id_lowongan']);
        }

        return $query->orderByDesc('tanggal_apply')->paginate($perPage);
    }

    /**
     * Find a lamaran by ID with relations.
     */
    public function findById(int $lamaranId)
    {
        return Lamaran::with([
            'alumni.jurusan',
            'alumni.skills',
            'alumni.user',
            'lowongan.perusahaan',
            'lowongan.pekerjaan',
            'lowongan.skills',
        ])->findOrFail($lamaranId);
    }

    /**
     * Delete (cancel) a pending lamaran.
     */
    public function delete(int $lamaranId): bool
    {
        $lamaran = Lamaran::findOrFail($lamaranId);
        return $lamaran->delete();
    }
}
