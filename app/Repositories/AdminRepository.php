<?php

namespace App\Repositories;

use App\Interfaces\AdminRepositoryInterface;
use App\Models\Alumni;
use App\Models\User;
use App\Models\Lowongan;
use App\Models\Kuesioner;
use App\Models\RiwayatStatus;
use App\Models\Status;

class AdminRepository implements AdminRepositoryInterface
{
    public function getDashboardStats(): array
    {
        $totalUsers = User::where('role', 'alumni')->count();
        $pendingUsers = Alumni::where('status_create', 'pending')->count();
        $activeKuesioner = Kuesioner::where('status_kuesioner', 'publish')->count();
        $pendingLowongan = Lowongan::where('approval_status', 'pending')->count();

        $statusBekerja = Status::where('nama_status', 'Bekerja')->first();
        $totalBekerja = $statusBekerja
            ? RiwayatStatus::where('id_status', $statusBekerja->id_status)->count()
            : 0;
        $percentBekerja = $totalUsers > 0 ? round(($totalBekerja / $totalUsers) * 100) : 0;

        return [
            'total_users' => $totalUsers,
            'pending_users' => $pendingUsers,
            'active_kuesioner' => $activeKuesioner,
            'pending_lowongan' => $pendingLowongan,
            'percent_bekerja' => $percentBekerja,
        ];
    }

    public function getPendingAlumni(int $perPage = 15)
    {
        return Alumni::with(['user', 'jurusan'])
            ->where('status_create', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function approveAlumni(int $alumniId)
    {
        $alumni = Alumni::findOrFail($alumniId);
        $alumni->update(['status_create' => 'ok']);
        return $alumni;
    }

    public function rejectAlumni(int $alumniId)
    {
        $alumni = Alumni::findOrFail($alumniId);
        $alumni->update(['status_create' => 'rejected']);
        return $alumni;
    }

    public function getAllAlumni(array $filters = [], int $perPage = 15)
    {
        $query = Alumni::with(['user', 'jurusan', 'riwayatStatus.status']);

        if (!empty($filters['status_create'])) {
            $query->where('status_create', $filters['status_create']);
        }

        if (!empty($filters['id_jurusan'])) {
            $query->where('id_jurusan', $filters['id_jurusan']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('nama_alumni', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function getAlumniDetail(int $alumniId)
    {
        return Alumni::with([
            'user',
            'jurusan',
            'skills',
            'socialMedia',
            'riwayatStatus.status',
            'riwayatStatus.pekerjaan.perusahaan.kota.provinsi',
            'riwayatStatus.universitas.jurusanKuliah',
            'riwayatStatus.wirausaha.bidangUsaha',
        ])->findOrFail($alumniId);
    }

    public function deleteUser(int $userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();
        return true;
    }
}
