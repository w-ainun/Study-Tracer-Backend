<?php

namespace App\Repositories;

use App\Interfaces\AdminRepositoryInterface;
use App\Models\Alumni;
use App\Models\User;
use App\Models\Lowongan;
use App\Models\Kuesioner;
use App\Models\RiwayatStatus;
use App\Models\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminRepository implements AdminRepositoryInterface
{
    public function getDashboardStats(): array
    {
        return Cache::remember('admin.dashboard_stats', 60, function () {
            // Batch alumni counts in a single query
            $alumniCounts = Alumni::selectRaw("
                SUM(CASE WHEN status_create = 'ok' THEN 1 ELSE 0 END) as total_ok,
                SUM(CASE WHEN status_create = 'pending' THEN 1 ELSE 0 END) as total_pending,
                SUM(CASE WHEN status_create = 'ok' AND created_at >= ? THEN 1 ELSE 0 END) as new_this_week,
                SUM(CASE WHEN status_create = 'ok' AND created_at >= ? AND created_at <= ? THEN 1 ELSE 0 END) as last_week
            ", [
                now()->startOfWeek(),
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ])->first();

            $totalUsers = (int) ($alumniCounts->total_ok ?? 0);
            $pendingUsers = (int) ($alumniCounts->total_pending ?? 0);
            $newThisWeek = (int) ($alumniCounts->new_this_week ?? 0);
            $lastWeek = (int) ($alumniCounts->last_week ?? 0);

            // Batch lowongan counts in a single query
            $lowonganCounts = Lowongan::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'published' AND approval_status = 'approved' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_this_week,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as expired
            ", [now()->startOfWeek()])->first();

            $activeKuesioner = Kuesioner::count();
            $pendingLowongan = (int) ($lowonganCounts->pending ?? 0);

            // Worker percentage
            $statusBekerja = Status::where('nama_status', 'Bekerja')->first();
            $totalBekerja = $statusBekerja
                ? RiwayatStatus::where('id_status', $statusBekerja->id_status)
                    ->whereNull('tahun_selesai')
                    ->whereHas('alumni', fn ($q) => $q->where('status_create', 'ok'))
                    ->count()
                : 0;
            $workerPercentage = $totalUsers > 0 ? round(($totalBekerja / $totalUsers) * 100) : 0;

            $usersGrowth = $lastWeek > 0 ? round((($newThisWeek - $lastWeek) / $lastWeek) * 100) : ($newThisWeek > 0 ? 100 : 0);

            // Alumni per jurusan (accepted only)
            $alumniPerJurusan = Alumni::where('status_create', 'ok')
                ->selectRaw('id_jurusan, count(*) as total')
                ->groupBy('id_jurusan')
                ->with('jurusan')
                ->get()
                ->map(fn ($item) => [
                    'jurusan' => $item->jurusan->nama_jurusan ?? 'Unknown',
                    'total' => $item->total,
                ]);

            // Status distribution (only active riwayat of accepted alumni)
            $statusDistribution = RiwayatStatus::selectRaw('riwayat_status.id_status, count(*) as total')
                ->join('alumni', 'riwayat_status.id_alumni', '=', 'alumni.id_alumni')
                ->where('alumni.status_create', 'ok')
                ->whereNull('riwayat_status.tahun_selesai')
                ->groupBy('riwayat_status.id_status')
                ->with('status')
                ->get()
                ->map(fn ($item) => [
                    'status' => $item->status->nama_status ?? 'Unknown',
                    'total' => $item->total,
                ]);

            return [
                'total_users' => $totalUsers,
                'users_growth' => $usersGrowth,
                'worker_percentage' => $workerPercentage,
                'active_kuesioner' => $activeKuesioner,
                'pending_count' => $pendingUsers + $pendingLowongan,
                'pending_users' => $pendingUsers,
                'pending_lowongan' => $pendingLowongan,
                'percent_bekerja' => $workerPercentage,
                'active_lowongan' => (int) ($lowonganCounts->active ?? 0),
                'new_lowongan_this_week' => (int) ($lowonganCounts->new_this_week ?? 0),
                'total_lowongan' => (int) ($lowonganCounts->total ?? 0),
                'new_users_this_week' => $newThisWeek,
                'alumni_per_jurusan' => $alumniPerJurusan,
                'status_distribution' => $statusDistribution,
            ];
        });
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

    public function banAlumni(int $alumniId)
    {
        $alumni = Alumni::findOrFail($alumniId);
        $alumni->update(['status_create' => 'banned']);
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

        if (!empty($filters['tahun_lulus'])) {
            $query->whereYear('tahun_lulus', $filters['tahun_lulus']);
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
            'riwayatStatus.kuliah.universitas',
            'riwayatStatus.kuliah.jurusanKuliah',
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
        return Cache::remember('admin.lowongan_stats', 120, function () {
            $counts = Lowongan::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'published' AND approval_status = 'approved' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_this_week,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as expired
            ", [now()->startOfWeek()])->first();

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
                'active' => (int) ($counts->active ?? 0),
                'pending' => (int) ($counts->pending ?? 0),
                'new_this_week' => (int) ($counts->new_this_week ?? 0),
                'total' => (int) ($counts->total ?? 0),
                'expired' => (int) ($counts->expired ?? 0),
                'categories' => $categories,
            ];
        });
    }

    public function getTopCompanies(int $limit = 5): array
    {
        return Cache::remember("admin.top_companies.{$limit}", 300, function () use ($limit) {
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
        });
    }

    public function getGeographicDistribution(): array
    {
        return Cache::remember('admin.geographic_distribution', 300, function () {
            $total = \App\Models\Pekerjaan::join('riwayat_status', 'pekerjaan.id_riwayat', '=', 'riwayat_status.id_riwayat')
                ->join('alumni', 'riwayat_status.id_alumni', '=', 'alumni.id_alumni')
                ->where('alumni.status_create', 'ok')
                ->count();
            if ($total === 0) return [];

            return \App\Models\Pekerjaan::join('perusahaan', 'pekerjaan.id_perusahaan', '=', 'perusahaan.id_perusahaan')
                ->join('kota', 'perusahaan.id_kota', '=', 'kota.id_kota')
                ->join('provinsi', 'kota.id_provinsi', '=', 'provinsi.id_provinsi')
                ->join('riwayat_status', 'pekerjaan.id_riwayat', '=', 'riwayat_status.id_riwayat')
                ->join('alumni', 'riwayat_status.id_alumni', '=', 'alumni.id_alumni')
                ->where('alumni.status_create', 'ok')
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
        });
    }
}
