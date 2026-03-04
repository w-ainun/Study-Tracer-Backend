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

        // Sort by skill match when alumni has skills
        if (!empty($alumniSkillIds)) {
            $placeholders = implode(',', array_map('intval', $alumniSkillIds));

            $query->leftJoin('lowongan_skills', 'lowongan.id_lowongan', '=', 'lowongan_skills.id_lowongan')
                ->select('lowongan.*')
                ->selectRaw(
                    "COALESCE(SUM(CASE WHEN lowongan_skills.id_skills IN ({$placeholders}) THEN 1 ELSE 0 END), 0) as skill_match_count"
                )
                ->groupBy('lowongan.id_lowongan')
                ->orderByDesc('skill_match_count')
                ->orderByDesc('lowongan.created_at');
        } else {
            $query->orderByDesc('created_at');
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
}
