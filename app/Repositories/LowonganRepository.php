<?php

namespace App\Repositories;

use App\Interfaces\LowonganRepositoryInterface;
use App\Models\Lowongan;
use App\Models\SimpanLowongan;

class LowonganRepository implements LowonganRepositoryInterface
{
    public function getAll(array $filters = [], int $perPage = 15)
    {
        $query = Lowongan::with(['perusahaan.kota.provinsi', 'pekerjaan']);

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
        return Lowongan::with(['perusahaan.kota.provinsi', 'pekerjaan'])
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return Lowongan::create($data);
    }

    public function update(int $id, array $data)
    {
        $lowongan = Lowongan::findOrFail($id);
        $lowongan->update($data);
        return $lowongan->fresh();
    }

    public function delete(int $id)
    {
        $lowongan = Lowongan::findOrFail($id);
        $lowongan->delete();
        return true;
    }

    public function getByApprovalStatus(string $status, int $perPage = 15)
    {
        return Lowongan::with(['perusahaan.kota.provinsi'])
            ->where('approval_status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function updateApprovalStatus(int $id, string $status)
    {
        $lowongan = Lowongan::findOrFail($id);
        $lowongan->update(['approval_status' => $status]);
        return $lowongan->fresh();
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
}
