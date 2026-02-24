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

        // Worker percentage
        $statusBekerja = Status::where('nama_status', 'Bekerja')->first();
        $totalBekerja = $statusBekerja
            ? RiwayatStatus::where('id_status', $statusBekerja->id_status)->count()
            : 0;
        $workerPercentage = $totalUsers > 0 ? round(($totalBekerja / $totalUsers) * 100) : 0;

        // Users growth: new alumni registered this week
        $newThisWeek = User::where('role', 'alumni')
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
        $lastWeek = User::where('role', 'alumni')
            ->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])
            ->count();
        $usersGrowth = $lastWeek > 0 ? round((($newThisWeek - $lastWeek) / $lastWeek) * 100) : ($newThisWeek > 0 ? 100 : 0);

        // Lowongan stats
        $activeLowongan = Lowongan::where('status', 'published')
            ->where('approval_status', 'approved')
            ->count();
        $newLowonganThisWeek = Lowongan::where('created_at', '>=', now()->startOfWeek())->count();
        $totalLowongan = Lowongan::count();

        // Alumni per jurusan stats
        $alumniPerJurusan = Alumni::where('status_create', 'ok')
            ->selectRaw('id_jurusan, count(*) as total')
            ->groupBy('id_jurusan')
            ->with('jurusan')
            ->get()
            ->map(fn ($item) => [
                'jurusan' => $item->jurusan->nama_jurusan ?? 'Unknown',
                'total' => $item->total,
            ]);

        // Status karir distribution
        $statusDistribution = RiwayatStatus::selectRaw('id_status, count(*) as total')
            ->groupBy('id_status')
            ->with('status')
            ->get()
            ->map(fn ($item) => [
                'status' => $item->status->nama_status ?? 'Unknown',
                'total' => $item->total,
            ]);

        return [
            // Frontend-compatible field names
            'total_users' => $totalUsers,
            'users_growth' => $usersGrowth,
            'worker_percentage' => $workerPercentage,
            'active_kuesioner' => $activeKuesioner,
            'pending_count' => $pendingUsers + $pendingLowongan,

            // Detailed stats
            'pending_users' => $pendingUsers,
            'pending_lowongan' => $pendingLowongan,
            'percent_bekerja' => $workerPercentage,
            'active_lowongan' => $activeLowongan,
            'new_lowongan_this_week' => $newLowonganThisWeek,
            'total_lowongan' => $totalLowongan,
            'new_users_this_week' => $newThisWeek,

            // Chart data
            'alumni_per_jurusan' => $alumniPerJurusan,
            'status_distribution' => $statusDistribution,
        ];
    }

    public function getUserManagementStats(): array
    {
        $pending  = Alumni::where('status_create', 'pending')->count();
        $active   = Alumni::where('status_create', 'ok')->count();
        $rejected = Alumni::where('status_create', 'rejected')->count();
        $total    = Alumni::count();
        $profileUpdated = Alumni::where('updated_at', '>=', now()->subDays(30))
            ->where('status_create', 'ok')
            ->count();

        return [
            'pending'         => $pending,
            'active'          => $active,
            'rejected'        => $rejected,
            'total'           => $total,
            'profile_updated' => $profileUpdated,
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
                  ->orWhere('nisn', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%");
                  });
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

    public function getLowonganStats(): array
    {
        $activeLowongan = Lowongan::where('status', 'published')
            ->where('approval_status', 'approved')
            ->count();
        $pendingLowongan = Lowongan::where('approval_status', 'pending')->count();
        $newThisWeek = Lowongan::where('created_at', '>=', now()->startOfWeek())->count();
        $totalLowongan = Lowongan::count();
        $expiredLowongan = Lowongan::where('status', 'closed')->count();

        // Categories count
        $categories = Lowongan::whereNotNull('tipe_pekerjaan')
            ->selectRaw('tipe_pekerjaan, count(*) as total')
            ->groupBy('tipe_pekerjaan')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($item) => [
                'name' => $item->tipe_pekerjaan,
                'count' => $item->total,
            ])
            ->toArray();

        return [
            'active' => $activeLowongan,
            'pending' => $pendingLowongan,
            'new_this_week' => $newThisWeek,
            'total' => $totalLowongan,
            'expired' => $expiredLowongan,
            'categories' => $categories,
        ];
    }

    public function getTopCompanies(int $limit = 5): array
    {
        return \App\Models\Pekerjaan::selectRaw('id_perusahaan, count(*) as alumni_count')
            ->groupBy('id_perusahaan')
            ->orderByDesc('alumni_count')
            ->limit($limit)
            ->with('perusahaan.kota')
            ->get()
            ->map(function ($item) {
                $perusahaan = $item->perusahaan;
                return [
                    'nama' => $perusahaan?->nama_perusahaan ?? 'Unknown',
                    'lokasi' => $perusahaan?->kota?->nama_kota ?? '-',
                    'alumni_count' => $item->alumni_count,
                ];
            })
            ->toArray();
    }

    public function getGeographicDistribution(): array
    {
        $total = \App\Models\Pekerjaan::count();
        if ($total === 0) return [];

        return \App\Models\Pekerjaan::join('perusahaan', 'pekerjaan.id_perusahaan', '=', 'perusahaan.id_perusahaan')
            ->join('kota', 'perusahaan.id_kota', '=', 'kota.id_kota')
            ->join('provinsi', 'kota.id_provinsi', '=', 'provinsi.id_provinsi')
            ->selectRaw('provinsi.nama_provinsi as region, count(*) as total')
            ->groupBy('provinsi.nama_provinsi')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn ($item) => [
                'region' => $item->region,
                'percentage' => round(($item->total / $total) * 100),
            ])
            ->toArray();
    }
}
