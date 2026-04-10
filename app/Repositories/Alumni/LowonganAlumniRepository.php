<?php

namespace App\Repositories\Alumni;

use App\Interfaces\Alumni\LowonganAlumniRepositoryInterface;
use App\Models\Lowongan;
use App\Models\SimpanLowongan;

class LowonganAlumniRepository implements LowonganAlumniRepositoryInterface
{
    /**
     * Get published lowongan sorted by skill match for alumni.
     * Alumni with matching skills see relevant lowongan first, then random.
     */
    public function getPublishedSortedBySkillMatch(array $alumniSkillIds, array $filters = [], int $perPage = 15)
    {
        $query = Lowongan::with(['perusahaan.kota.provinsi', 'pekerjaan', 'skills'])
            ->where('approval_status', 'approved')
            ->where('status', 'published');

        $sort = strtolower((string) ($filters['sort'] ?? 'terbaru'));

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('judul_lowongan', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%")
                    ->orWhere('lokasi', 'like', "%{$search}%")
                    ->orWhereHas('perusahaan', fn($pq) => $pq->where('nama_perusahaan', 'like', "%{$search}%"));
            });
        }

        // Tipe pekerjaan filter
        if (!empty($filters['tipe_pekerjaan'])) {
            $query->where('tipe_pekerjaan', $filters['tipe_pekerjaan']);
        }

        // Lokasi filters from frontend dropdowns
        if (!empty($filters['provinsi'])) {
            $provinsi = $filters['provinsi'];
            $query->whereHas('perusahaan.kota.provinsi', function ($q) use ($provinsi) {
                $q->where('nama_provinsi', 'like', "%{$provinsi}%");
            });
        }

        if (!empty($filters['kota'])) {
            $kota = $filters['kota'];
            $query->whereHas('perusahaan.kota', function ($q) use ($kota) {
                $q->where('nama_kota', 'like', "%{$kota}%");
            });
        }

        // Sort by skill match when alumni has skills
        if (!empty($alumniSkillIds)) {
            $placeholders = implode(',', array_map('intval', $alumniSkillIds));

            $query->leftJoin('lowongan_skills', 'lowongan.id_lowongan', '=', 'lowongan_skills.id_lowongan')
                ->select('lowongan.*')
                ->selectRaw(
                    "COALESCE(SUM(CASE WHEN lowongan_skills.id_skills IN ({$placeholders}) THEN 1 ELSE 0 END), 0) as skill_match_count"
                )
                ->selectRaw(
                    "CASE WHEN COALESCE(SUM(CASE WHEN lowongan_skills.id_skills IN ({$placeholders}) THEN 1 ELSE 0 END), 0) > 0 THEN 1 ELSE 0 END as has_skill_match"
                )
                ->groupBy(
                    'lowongan.id_lowongan',
                    'lowongan.judul_lowongan',
                    'lowongan.deskripsi',
                    'lowongan.tipe_pekerjaan',
                    'lowongan.lokasi',
                    'lowongan.status',
                    'lowongan.approval_status',
                    'lowongan.approved_at',
                    'lowongan.rejected_at',
                    'lowongan.lowongan_selesai',
                    'lowongan.jam_mulai',
                    'lowongan.jam_berakhir',
                    'lowongan.id_pekerjaan',
                    'lowongan.foto_lowongan',
                    'lowongan.id_perusahaan',
                    'lowongan.id_users',
                    'lowongan.created_at',
                    'lowongan.updated_at'
                )
                ->orderByDesc('has_skill_match')
                ->orderByDesc('skill_match_count')
                ->when($sort === 'terlama', fn($q) => $q->orderBy('lowongan.created_at'))
                ->when($sort !== 'terlama', fn($q) => $q->orderByDesc('lowongan.created_at'));
        } else {
            $query->when($sort === 'terlama', fn($q) => $q->orderBy('created_at'))
                ->when($sort !== 'terlama', fn($q) => $q->orderByDesc('created_at'));
        }

        return $query->paginate($perPage);
    }

    /**
     * Get lowongan detail by id (only published/approved).
     */
    public function getPublishedById(int $id)
    {
        return Lowongan::with(['perusahaan.kota.provinsi', 'pekerjaan', 'skills'])
            ->where('approval_status', 'approved')
            ->where('id_lowongan', $id)
            ->firstOrFail();
    }

    /**
     * Get saved lowongan for a specific user.
     */
    public function getSavedByUser(int $userId, int $perPage = 15)
    {
        return SimpanLowongan::with(['lowongan.perusahaan.kota.provinsi', 'lowongan.pekerjaan', 'lowongan.skills'])
            ->where('id_user', $userId)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Toggle save/unsave a lowongan for the user.
     */
    public function toggleSave(int $userId, int $lowonganId): bool
    {
        $existing = SimpanLowongan::where('id_user', $userId)
            ->where('id_lowongan', $lowonganId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false; // unsaved
        }

        SimpanLowongan::create([
            'id_user' => $userId,
            'id_lowongan' => $lowonganId,
        ]);

        return true; // saved
    }

    /**
     * Check whether a specific lowongan is saved by the user.
     */
    public function isSavedByUser(int $userId, int $lowonganId): bool
    {
        return SimpanLowongan::where('id_user', $userId)
            ->where('id_lowongan', $lowonganId)
            ->exists();
    }

    /**
     * Get list of lowongan IDs saved by the user.
     */
    public function getSavedLowonganIds(int $userId): array
    {
        return SimpanLowongan::where('id_user', $userId)
            ->pluck('id_lowongan')
            ->toArray();
    }

    /**
     * Get all lowongan posted by a specific user (includes all statuses for "my lowongan").
     */
    public function getByUserId(int $userId, array $filters = [], int $perPage = 15)
    {
        $query = Lowongan::with(['perusahaan.kota.provinsi', 'pekerjaan', 'skills'])
            ->where('id_users', $userId);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['approval_status'])) {
            $query->where('approval_status', $filters['approval_status']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('judul_lowongan', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Create a new lowongan.
     */
    public function create(array $data)
    {
        $lowongan = Lowongan::create($data);
        return $lowongan->load(['perusahaan.kota.provinsi', 'pekerjaan', 'skills']);
    }

    /**
     * Sync skills for a lowongan.
     */
    public function syncSkills(int $lowonganId, array $skillIds): void
    {
        $lowongan = Lowongan::findOrFail($lowonganId);
        $lowongan->skills()->sync($skillIds);
    }
}
