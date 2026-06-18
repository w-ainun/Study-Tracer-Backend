<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LamaranService;
use App\Services\ExportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class LamaranAdminController extends Controller
{
    use ApiResponse;

    private LamaranService $lamaranService;

    public function __construct(LamaranService $lamaranService)
    {
        $this->lamaranService = $lamaranService;
    }

    /**
     * GET /admin/lamaran
     * All applications with filters (paginated).
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['status', 'search', 'id_lowongan']);
            $perPage = $request->input('per_page', 15);

            $data = $this->lamaranService->getAll($filters, $perPage);

            $result = $data->through(function ($lamaran) {
                return [
                    'id_lamaran' => $lamaran->id_lamaran,
                    'status' => $lamaran->status,
                    'tanggal_apply' => $lamaran->tanggal_apply?->toISOString(),
                    'tanggal_respon' => $lamaran->tanggal_respon?->toISOString(),
                    'catatan' => $lamaran->catatan,
                    'catatan_admin' => $lamaran->catatan_admin,
                    'alumni' => $lamaran->alumni ? [
                        'id_alumni' => $lamaran->alumni->id_alumni,
                        'nama_alumni' => $lamaran->alumni->nama_alumni,
                        'nis' => $lamaran->alumni->nis,
                        'foto' => $lamaran->alumni->foto ? asset('storage/' . $lamaran->alumni->foto) : null,
                        'jurusan' => $lamaran->alumni->jurusan?->nama_jurusan,
                        'email' => $lamaran->alumni->user?->email_users,
                    ] : null,
                    'lowongan' => $lamaran->lowongan ? [
                        'id_lowongan' => $lamaran->lowongan->id_lowongan,
                        'judul_lowongan' => $lamaran->lowongan->judul_lowongan,
                        'perusahaan' => $lamaran->lowongan->perusahaan?->nama_perusahaan,
                    ] : null,
                ];
            });

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data lamaran: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/lamaran/lowongan/{id}
     * Applications for a specific lowongan.
     */
    public function byLowongan(Request $request, int $id)
    {
        try {
            $filters = $request->only(['status']);
            $perPage = $request->input('per_page', 15);

            $data = $this->lamaranService->getByLowongan($id, $filters, $perPage);

            $result = $data->through(function ($lamaran) {
                return [
                    'id_lamaran' => $lamaran->id_lamaran,
                    'status' => $lamaran->status,
                    'tanggal_apply' => $lamaran->tanggal_apply?->toISOString(),
                    'tanggal_respon' => $lamaran->tanggal_respon?->toISOString(),
                    'catatan' => $lamaran->catatan,
                    'catatan_admin' => $lamaran->catatan_admin,
                    'alumni' => $lamaran->alumni ? [
                        'id_alumni' => $lamaran->alumni->id_alumni,
                        'nama_alumni' => $lamaran->alumni->nama_alumni,
                        'nis' => $lamaran->alumni->nis,
                        'foto' => $lamaran->alumni->foto ? asset('storage/' . $lamaran->alumni->foto) : null,
                        'jurusan' => $lamaran->alumni->jurusan?->nama_jurusan,
                        'email' => $lamaran->alumni->user?->email_users,
                        'skills' => $lamaran->alumni->skills?->map(fn($s) => [
                            'id_skills' => $s->id_skills,
                            'nama_skills' => $s->nama_skills,
                        ]),
                    ] : null,
                ];
            });

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data pelamar: ' . $e->getMessage());
        }
    }

    /**
     * POST /admin/lamaran/{id}/terima
     * Accept an application.
     */
    public function terima(Request $request, int $id)
    {
        try {
            $catatanAdmin = $request->input('catatan_admin');
            $lamaran = $this->lamaranService->terima($id, $catatanAdmin);

            return $this->successResponse([
                'id_lamaran' => $lamaran->id_lamaran,
                'status' => $lamaran->status,
                'tanggal_respon' => $lamaran->tanggal_respon?->toISOString(),
            ], 'Lamaran berhasil diterima');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * POST /admin/lamaran/{id}/tolak
     * Reject an application (triggers new job recommendations).
     */
    public function tolak(Request $request, int $id)
    {
        try {
            $catatanAdmin = $request->input('catatan_admin');
            $lamaran = $this->lamaranService->tolak($id, $catatanAdmin);

            return $this->successResponse([
                'id_lamaran' => $lamaran->id_lamaran,
                'status' => $lamaran->status,
                'tanggal_respon' => $lamaran->tanggal_respon?->toISOString(),
            ], 'Lamaran berhasil ditolak. Notifikasi lowongan baru telah dikirim ke alumni.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * GET /admin/lamaran/stats
     * Global application statistics.
     */
    public function stats()
    {
        try {
            $stats = $this->lamaranService->getGlobalStats();
            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik lamaran: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/lamaran/export
     * Export lamaran data to CSV.
     */
    public function export(Request $request, ExportService $exportService)
    {
        try {
            $filters = $request->only(['status', 'search']);
            return $exportService->exportLamaran($filters);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor data lamaran: ' . $e->getMessage());
        }
    }
}
