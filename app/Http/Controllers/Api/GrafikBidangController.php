<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GrafikBidangService;
use App\Services\ExportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class GrafikBidangController extends Controller
{
    use ApiResponse;

    private GrafikBidangService $grafikService;

    public function __construct(GrafikBidangService $grafikService)
    {
        $this->grafikService = $grafikService;
    }

    /**
     * GET /admin/grafik-bidang/stats
     * Overall kesesuaian bidang statistics (pie chart data).
     */
    public function stats(Request $request)
    {
        try {
            $filters = $request->only(['id_jurusan', 'tahun_lulus', 'status_karier']);
            $stats = $this->grafikService->getStats($filters);

            return $this->successResponse($stats);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik kesesuaian bidang: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/grafik-bidang/by-jurusan
     * Kesesuaian breakdown per jurusan (bar chart data).
     */
    public function byJurusan(Request $request)
    {
        try {
            $filters = $request->only(['tahun_lulus', 'status_karier']);
            $data = $this->grafikService->getByJurusan($filters);

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data per jurusan: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/grafik-bidang/by-tahun
     * Kesesuaian breakdown per tahun lulus (line chart data).
     */
    public function byTahun(Request $request)
    {
        try {
            $filters = $request->only(['id_jurusan', 'status_karier']);
            $data = $this->grafikService->getByTahunLulus($filters);

            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data per tahun: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/grafik-bidang/detail
     * Detailed alumni list with kesesuaian filter (paginated).
     */
    public function detail(Request $request)
    {
        try {
            $filters = $request->only(['kesesuaian', 'id_jurusan', 'tahun_lulus', 'status_karier', 'search']);
            $perPage = $request->input('per_page', 15);

            $data = $this->grafikService->getDetail($filters, $perPage);

            // Transform paginated data for frontend
            $result = $data->through(function ($riwayat) {
                $alumni = $riwayat->alumni;
                return [
                    'id_riwayat' => $riwayat->id_riwayat,
                    'id_alumni' => $alumni?->id_alumni,
                    'nama_alumni' => $alumni?->nama_alumni ?? '-',
                    'jurusan' => $alumni?->jurusan?->nama_jurusan ?? '-',
                    'tahun_lulus' => $alumni?->tahun_lulus?->format('Y') ?? '-',
                    'foto' => $alumni?->foto ? asset('storage/' . $alumni->foto) : null,
                    'status_karier' => $riwayat->status?->nama_status ?? '-',
                    'detail_karier' => $this->getCareerDetail($riwayat),
                    'is_sesuai_bidang' => $riwayat->is_sesuai_bidang,
                    'kesesuaian_label' => $riwayat->is_sesuai_bidang === null
                        ? 'Belum Ditentukan'
                        : ($riwayat->is_sesuai_bidang ? 'Sesuai' : 'Tidak Sesuai'),
                ];
            });

            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil detail kesesuaian: ' . $e->getMessage());
        }
    }

    /**
     * GET /admin/grafik-bidang/export
     * Export kesesuaian bidang data to CSV.
     */
    public function export(Request $request, ExportService $exportService)
    {
        try {
            $filters = $request->only(['kesesuaian', 'id_jurusan', 'tahun_lulus', 'status_karier']);
            return $exportService->exportKesesuaianBidang($filters);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengekspor data kesesuaian bidang: ' . $e->getMessage());
        }
    }

    /**
     * POST /admin/grafik-bidang/recompute/{jurusanId}
     * Re-compute kesesuaian for all alumni of a specific jurusan.
     * Useful after admin updates bidang_relevan keywords.
     */
    public function recompute(int $jurusanId)
    {
        try {
            $count = $this->grafikService->recomputeForJurusan($jurusanId);
            return $this->successResponse(
                ['recomputed' => $count],
                "Berhasil menghitung ulang kesesuaian untuk {$count} alumni."
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghitung ulang kesesuaian: ' . $e->getMessage());
        }
    }

    /**
     * Helper to format career detail.
     */
    private function getCareerDetail($riwayat): string
    {
        $statusName = $riwayat->status?->nama_status ?? '';

        return match ($statusName) {
            'Bekerja' => ($riwayat->pekerjaan?->posisi ?? '-') . ' di ' . ($riwayat->pekerjaan?->perusahaan?->nama_perusahaan ?? '-'),
            'Kuliah' => ($riwayat->kuliah?->jurusanKuliah?->nama_jurusan ?? '-') . ' di ' . ($riwayat->kuliah?->universitas?->nama_universitas ?? '-'),
            'Wirausaha' => ($riwayat->wirausaha?->nama_usaha ?? '-') . ' (' . ($riwayat->wirausaha?->bidangUsaha?->nama_bidang ?? '-') . ')',
            default => '-',
        };
    }
}
