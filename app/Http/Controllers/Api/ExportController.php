<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    use ApiResponse;

    private ExportService $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * GET /admin/export/alumni
     * Export all alumni data with career status and kesesuaian bidang.
     */
    public function alumni(Request $request)
    {
        try {
            $filters = $request->only(['status_create', 'id_jurusan', 'search', 'tahun_lulus']);
            return $this->exportService->exportAlumniComplete($filters);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor data alumni: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/export/lamaran
     * Export lamaran/application data.
     */
    public function lamaran(Request $request)
    {
        try {
            $filters = $request->only(['status', 'search']);
            return $this->exportService->exportLamaran($filters);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor data lamaran: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/export/kesesuaian-bidang
     * Export kesesuaian bidang data.
     */
    public function kesesuaianBidang(Request $request)
    {
        try {
            $filters = $request->only(['kesesuaian', 'id_jurusan', 'tahun_lulus', 'status_karier']);
            return $this->exportService->exportKesesuaianBidang($filters);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor data kesesuaian bidang: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/export/lowongan
     * Export lowongan data with application statistics.
     */
    public function lowongan(Request $request)
    {
        try {
            $filters = $request->only(['status']);
            return $this->exportService->exportLowongan($filters);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor data lowongan: ' . $e->getMessage());
        }
    }
}
