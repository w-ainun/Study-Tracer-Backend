<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlumniResource;
use App\Http\Resources\UserResource;
use App\Http\Resources\Alumni\ProfileRiwayatResource;
use App\Services\AdminService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminController extends Controller
{
    use ApiResponse;

    private AdminService $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function getStats()
    {
        try {
            $stats = $this->adminService->getDashboardStats();
            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik dashboard');
        }
    }

    public function getUserManagementStats()
    {
        try {
            $stats = $this->adminService->getUserManagementStats();
            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik pengguna');
        }
    }

    public function getPendingUsers(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 15);
            $pending = $this->adminService->getPendingAlumni($perPage);
            return $this->successResponse(AlumniResource::collection($pending)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data pending');
        }
    }

    public function approveUser(int $id)
    {
        try {
            $alumni = $this->adminService->approveAlumni($id);
            return $this->successResponse(new AlumniResource($alumni), 'Alumni berhasil disetujui');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyetujui alumni: ' . $e->getMessage());
        }
    }

    public function rejectUser(int $id)
    {
        try {
            $alumni = $this->adminService->rejectAlumni($id);
            return $this->successResponse(new AlumniResource($alumni), 'Alumni berhasil ditolak');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menolak alumni: ' . $e->getMessage());
        }
    }

    public function banUser(int $id)
    {
        try {
            $alumni = $this->adminService->banAlumni($id);
            return $this->successResponse(new AlumniResource($alumni), 'Alumni berhasil dibanned');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memban alumni: ' . $e->getMessage());
        }
    }

    public function getAllAlumni(Request $request)
    {
        try {
            $filters = $request->only(['status_create', 'id_jurusan', 'search', 'tahun_lulus']);
            $perPage = $request->input('per_page', 15);
            $alumni = $this->adminService->getAllAlumni($filters, $perPage);

            return $this->successResponse(AlumniResource::collection($alumni)->response()->getData(true));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data alumni');
        }
    }

    public function getAlumniDetail(int $id)
    {
        try {
            $alumni = $this->adminService->getAlumniDetail($id);
            return $this->successResponse(new AlumniResource($alumni));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil detail alumni: ' . $e->getMessage());
        }
    }

    public function deleteUser(int $id)
    {
        try {
            $this->adminService->deleteUser($id);
            return $this->successResponse(null, 'User berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus user: ' . $e->getMessage());
        }
    }

    public function getLowonganStats()
    {
        try {
            $stats = $this->adminService->getLowonganStats();
            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik lowongan');
        }
    }

    public function getTopCompanies(Request $request)
    {
        try {
            $limit = $request->input('limit', 5);
            $companies = $this->adminService->getTopCompanies($limit);
            return $this->successResponse($companies);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil perusahaan teratas');
        }
    }

    public function getGeographicDistribution()
    {
        try {
            $distribution = $this->adminService->getGeographicDistribution();
            return $this->successResponse($distribution);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil distribusi geografis');
        }
    }

    public function exportAlumniCsv(Request $request): StreamedResponse
    {
        $filters = $request->only(['status_create', 'id_jurusan', 'search', 'tahun_lulus']);
        $alumni  = $this->adminService->getAllAlumni($filters, 99999);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="alumni_export_' . now()->format('Ymd_His') . '.csv"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ];

        $callback = function () use ($alumni) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'ID', 'Nama', 'NIS', 'NISN', 'Jenis Kelamin',
                'Tanggal Lahir', 'Tempat Lahir', 'Tahun Masuk', 'Tahun Lulus',
                'Alamat', 'No HP', 'Jurusan', 'Status', 'Email', 'Dibuat',
            ]);

            foreach ($alumni as $item) {
                fputcsv($handle, [
                    $item->id_alumni,
                    $item->nama_alumni,
                    $item->nis,
                    $item->nisn,
                    $item->jenis_kelamin,
                    $item->tanggal_lahir?->format('Y-m-d'),
                    $item->tempat_lahir,
                    $item->tahun_masuk,
                    $item->tahun_lulus?->format('Y-m-d'),
                    $item->alamat,
                    $item->no_hp,
                    $item->jurusan?->nama_jurusan ?? '-',
                    $item->status_create,
                    $item->user?->email_users ?? '-',
                    $item->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ── Pending Career Status Requests ───────────────────

    /**
     * GET /admin/pending-career-updates
     * List all riwayat_status with approval_status = 'pending'.
     */
    public function getPendingCareerUpdates()
    {
        try {
            $pending = $this->adminService->getPendingCareerUpdates();

            $data = $pending->map(function ($riwayat) {
                $alumni = $riwayat->alumni;
                $changes = [];

                // ── Find the alumni's previous APPROVED riwayat to show "old" values ──
                $previousRiwayat = null;
                if ($alumni) {
                    $previousRiwayat = \App\Models\RiwayatStatus::with([
                        'status',
                        'pekerjaan.perusahaan',
                        'kuliah.universitas',
                        'kuliah.jurusanKuliah',
                        'wirausaha.bidangUsaha',
                    ])
                        ->where('id_alumni', $alumni->id_alumni)
                        ->where('approval_status', 'approved')
                        ->where('id_riwayat', '!=', $riwayat->id_riwayat)
                        ->orderByDesc('id_riwayat')
                        ->first();
                }

                $oldStatus = $previousRiwayat?->status?->nama_status ?? '-';
                $newStatus = $riwayat->status?->nama_status ?? '-';
                $changes[] = ['label' => 'Status Karier', 'old' => $oldStatus, 'new' => $newStatus];

                // Old pekerjaan details
                $oldPosisi = $previousRiwayat?->pekerjaan?->posisi ?? '-';
                $oldPerusahaan = $previousRiwayat?->pekerjaan?->perusahaan?->nama_perusahaan ?? '-';
                // Old kuliah details
                $oldUniversitas = $previousRiwayat?->kuliah?->universitas?->nama_universitas ?? '-';
                $oldJurusan = $previousRiwayat?->kuliah?->jurusanKuliah?->nama_jurusan ?? '-';
                // Old wirausaha details
                $oldNamaUsaha = $previousRiwayat?->wirausaha?->nama_usaha ?? '-';
                $oldBidang = $previousRiwayat?->wirausaha?->bidangUsaha?->nama_bidang ?? '-';

                if ($riwayat->pekerjaan) {
                    $changes[] = ['label' => 'Posisi', 'old' => $oldPosisi, 'new' => $riwayat->pekerjaan->posisi ?? '-'];
                    $changes[] = ['label' => 'Perusahaan', 'old' => $oldPerusahaan, 'new' => $riwayat->pekerjaan->perusahaan?->nama_perusahaan ?? '-'];
                }
                if ($riwayat->kuliah) {
                    $changes[] = ['label' => 'Universitas', 'old' => $oldUniversitas, 'new' => $riwayat->kuliah->universitas?->nama_universitas ?? '-'];
                    $changes[] = ['label' => 'Jurusan', 'old' => $oldJurusan, 'new' => $riwayat->kuliah->jurusanKuliah?->nama_jurusan ?? '-'];
                }
                if ($riwayat->wirausaha) {
                    $changes[] = ['label' => 'Nama Usaha', 'old' => $oldNamaUsaha, 'new' => $riwayat->wirausaha->nama_usaha ?? '-'];
                    $changes[] = ['label' => 'Bidang', 'old' => $oldBidang, 'new' => $riwayat->wirausaha->bidangUsaha?->nama_bidang ?? '-'];
                }

                return [
                    'id' => $riwayat->id_riwayat,
                    'name' => $alumni?->nama_alumni ?? '-',
                    'angkatan' => $alumni?->tahun_masuk ?? '-',
                    'userId' => $alumni?->nis ?? '-',
                    'image' => $alumni?->foto ? asset('storage/' . $alumni->foto) : null,
                    'initials' => strtoupper(substr($alumni?->nama_alumni ?? 'A', 0, 2)),
                    'time' => $riwayat->created_at?->diffForHumans() ?? '-',
                    'field' => 'Status Karier',
                    'changes' => $changes,
                ];
            });

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil permintaan update karier: ' . $e->getMessage());
        }
    }

    /**
     * POST /admin/career-updates/{id}/approve
     */
    public function approveCareerUpdate(int $id)
    {
        try {
            $riwayat = $this->adminService->approveCareerUpdate($id);
            return $this->successResponse(
                new ProfileRiwayatResource($riwayat),
                'Status karier berhasil disetujui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyetujui status karier: ' . $e->getMessage());
        }
    }

    /**
     * POST /admin/career-updates/{id}/reject
     */
    public function rejectCareerUpdate(int $id)
    {
        try {
            $this->adminService->rejectCareerUpdate($id);
            return $this->successResponse(null, 'Permintaan status karier berhasil ditolak');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menolak status karier: ' . $e->getMessage());
        }
    }
}
