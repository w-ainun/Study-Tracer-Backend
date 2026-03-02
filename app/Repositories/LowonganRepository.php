<?php

namespace App\Repositories;

use App\Interfaces\LowonganRepositoryInterface;
use App\Models\Lowongan;
use App\Models\SimpanLowongan;
use Illuminate\Support\Facades\DB;

class LowonganRepository implements LowonganRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Lowongan::with(['perusahaan.kota.provinsi', 'pekerjaan', 'user', 'skills']);

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

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getById(int $id)
    {
        return Lowongan::with(['perusahaan.kota.provinsi', 'pekerjaan', 'user', 'skills'])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        $lowongan = Lowongan::create($data);
        return $lowongan->load(['perusahaan.kota.provinsi', 'pekerjaan', 'user', 'skills']);
    }

    public function update(int $id, array $data)
    {
        $lowongan = Lowongan::findOrFail($id);
        $lowongan->update($data);
        return $lowongan->fresh(['perusahaan.kota.provinsi', 'pekerjaan', 'user', 'skills']);
    }

    public function delete(int $id)
    {
        $lowongan = Lowongan::findOrFail($id);
        $lowongan->delete();
        return true;
    }

    public function getByApprovalStatus(string $status, int $perPage = 15)
    {
        return Lowongan::with(['perusahaan.kota.provinsi', 'user', 'skills'])
            ->where('approval_status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function updateApprovalStatus(int $id, string $status)
    {
        $lowongan = Lowongan::findOrFail($id);
        $lowongan->update(['approval_status' => $status]);
        return $lowongan->fresh(['perusahaan.kota.provinsi', 'pekerjaan', 'user', 'skills']);
    }

    public function getSavedByUser(int $userId, int $perPage = 15)
    {
        return SimpanLowongan::with(['lowongan.perusahaan.kota.provinsi'])
            ->where('id_user', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

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

    public function syncSkills(int $lowonganId, array $skillIds): void
    {
        $lowongan = Lowongan::findOrFail($lowonganId);
        $lowongan->skills()->sync($skillIds);
    }

    public function getPublishedSortedBySkillMatch(array $alumniSkillIds, array $filters = [], int $perPage = 15)
    {
        $query = Lowongan::with(['perusahaan.kota.provinsi', 'pekerjaan', 'user', 'skills'])
            ->where('approval_status', 'approved')
            ->where('status', 'published');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('judul_lowongan', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Jika alumni punya skill, urutkan berdasarkan jumlah skill yg cocok (desc), lalu random
        if (!empty($alumniSkillIds)) {
            $query->leftJoin('lowongan_skills', 'lowongan.id_lowongan', '=', 'lowongan_skills.id_lowongan')
                ->select('lowongan.*')
                ->selectRaw(
                    'COALESCE(SUM(CASE WHEN lowongan_skills.id_skills IN (' . implode(',', array_map('intval', $alumniSkillIds)) . ') THEN 1 ELSE 0 END), 0) as skill_match_count'
                )
                ->groupBy('lowongan.id_lowongan')
                ->orderByDesc('skill_match_count')
                ->orderByRaw('RAND()');
        } else {
            // Jika alumni tidak punya skill, tampilkan random
            $query->orderByRaw('RAND()');
        }

        return $query->paginate($perPage);
    }
}
