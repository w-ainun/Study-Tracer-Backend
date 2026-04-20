<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWirausahaRequest;
use App\Http\Resources\BidangUsahaResource;
use App\Http\Resources\JurusanKuliahResource;
use App\Http\Resources\UniversitasResource;
use App\Http\Resources\WirausahaResource;
use App\Services\StatusKarierService;
use App\Traits\ApiResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StatusKarierController extends Controller
{
    use ApiResponse;

    private StatusKarierService $service;

    public function __construct(StatusKarierService $service)
    {
        $this->service = $service;
    }

    // ═══════════════════════════════════════════════
    //  UNIVERSITAS
    // ═══════════════════════════════════════════════

    public function universitas()
    {
        try {
            $data = $this->service->getAllUniversitas();
            return $this->successResponse(UniversitasResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data universitas');
        }
    }

    public function storeUniversitas(Request $request)
    {
        $request->validate([
            'nama' => 'required_without:nama_universitas|string|max:255',
            'nama_universitas' => 'required_without:nama|string|max:255',
            'alamat' => 'nullable|string|max:500',
            'id_kota' => 'nullable|exists:kota,id_kota',
        ]);

        try {
            $data = $this->service->createUniversitas($request->only('nama', 'nama_universitas', 'alamat', 'id_kota'));
            return $this->createdResponse(
                new UniversitasResource($data),
                'Universitas berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan universitas: ' . $e->getMessage());
        }
    }

    public function updateUniversitas(Request $request, int $id)
    {
        $request->validate([
            'nama' => 'sometimes|string|max:255',
            'nama_universitas' => 'sometimes|string|max:255',
            'alamat' => 'nullable|string|max:500',
            'id_kota' => 'nullable|exists:kota,id_kota',
        ]);

        try {
            $data = $this->service->updateUniversitas($id, $request->only('nama', 'nama_universitas', 'alamat', 'id_kota'));
            return $this->successResponse(
                new UniversitasResource($data),
                'Universitas berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui universitas: ' . $e->getMessage());
        }
    }

    public function destroyUniversitas(int $id)
    {
        try {
            $this->service->deleteUniversitas($id);
            return $this->successResponse(null, 'Universitas berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus universitas: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  PROGRAM STUDI (JURUSAN KULIAH)
    // ═══════════════════════════════════════════════

    public function prodi()
    {
        try {
            $data = $this->service->getAllProdi();
            return $this->successResponse(JurusanKuliahResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data program studi');
        }
    }

    public function storeProdi(Request $request)
    {
        $request->validate([
            'nama_prodi' => 'required|string|max:255',
            'id_universitas' => 'nullable|exists:universitas,id_universitas',
        ]);

        try {
            $data = $this->service->createProdi($request->only('nama_prodi', 'id_universitas'));
            return $this->createdResponse(
                new JurusanKuliahResource($data),
                'Program studi berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan program studi: ' . $e->getMessage());
        }
    }

    public function updateProdi(Request $request, int $id)
    {
        $request->validate([
            'nama_prodi' => 'sometimes|string|max:255',
            'id_universitas' => 'nullable|exists:universitas,id_universitas',
        ]);

        try {
            $data = $this->service->updateProdi($id, $request->only('nama_prodi', 'id_universitas'));
            return $this->successResponse(
                new JurusanKuliahResource($data),
                'Program studi berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui program studi: ' . $e->getMessage());
        }
    }

    public function destroyProdi(int $id)
    {
        try {
            $this->service->deleteProdi($id);
            return $this->successResponse(null, 'Program studi berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus program studi: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════
    //  BIDANG WIRAUSAHA
    // ═══════════════════════════════════════════════

    public function bidangUsaha()
    {
        try {
            $data = $this->service->getAllBidangUsaha();
            return $this->successResponse(BidangUsahaResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data bidang usaha');
        }
    }

    public function storeBidangUsaha(Request $request)
    {
        $request->validate([
            'nama_bidang' => 'required|string|max:255',
        ]);

        try {
            $data = $this->service->createBidangUsaha($request->only('nama_bidang'));
            return $this->createdResponse(
                new BidangUsahaResource($data),
                'Bidang usaha berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan bidang usaha: ' . $e->getMessage());
        }
    }

    public function updateBidangUsaha(Request $request, int $id)
    {
        $request->validate([
            'nama_bidang' => 'sometimes|string|max:255',
        ]);

        try {
            $data = $this->service->updateBidangUsaha($id, $request->only('nama_bidang'));
            return $this->successResponse(
                new BidangUsahaResource($data),
                'Bidang usaha berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui bidang usaha: ' . $e->getMessage());
        }
    }

public function destroyBidangUsaha(int $id)
{
    try {
        $this->service->deleteBidangUsaha($id);
        return $this->successResponse(null, 'Bidang usaha berhasil dihapus');
    } catch (\Exception $e) {
        return $this->errorResponse('Gagal menghapus bidang usaha: ' . $e->getMessage());
    }
}

    // ═══════════════════════════════════════════════
    //  DATA WIRAUSAHA
    // ═══════════════════════════════════════════════

    public function wirausaha(Request $request)
    {
        try {
            $search = $request->query('search');
            $data = $this->service->getAllWirausaha($search);
            return $this->successResponse(WirausahaResource::collection($data));
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil data wirausaha: ' . $e->getMessage());
        }
    }

    public function storeWirausaha(StoreWirausahaRequest $request)
    {
        try {
            $data = $this->service->createWirausaha($request->validated());
            return $this->createdResponse(
                new WirausahaResource($data),
                'Data wirausaha berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menambahkan data wirausaha: ' . $e->getMessage());
        }
    }

    public function updateWirausaha(StoreWirausahaRequest $request, int $id)
    {
        try {
            $data = $this->service->updateWirausaha($id, $request->validated());
            return $this->successResponse(
                new WirausahaResource($data),
                'Data wirausaha berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal memperbarui data wirausaha: ' . $e->getMessage());
        }
    }

    public function destroyWirausaha(int $id)
    {
        try {
            $this->service->deleteWirausaha($id);
            return $this->successResponse(null, 'Data wirausaha berhasil dihapus');
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menghapus data wirausaha: ' . $e->getMessage());
        }
    }

// ═══════════════════════════════════════════════
    //  REPORT / EXPORT
    // ═══════════════════════════════════════════════

    public function statusDistribution()
    {
        try {
            $data = $this->service->getStatusDistribution();
            return $this->successResponse($data);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil distribusi status');
        }
    }

    public function exportReport(Request $request)
    {
        $request->validate([
            'type' => 'required|in:universitas,prodi,wirausaha',
            'format' => 'sometimes|in:csv,pdf',
        ]);

        $type = $request->input('type');
        $format = $request->input('format', 'csv');
        $data = $this->service->exportStatusReport($type);
        $timestamp = now()->format('Ymd_His');

        // ─── PDF Export ───────────────────────────────────
        if ($format === 'pdf') {
            $typeLabels = [
                'universitas' => 'Universitas',
                'prodi' => 'Program Studi',
                'wirausaha' => 'Data Wirausaha',
            ];

            $columnsMap = [
                'universitas' => ['ID', 'Nama Universitas', 'Jurusan'],
                'prodi'       => ['ID', 'Nama'],
                'wirausaha'   => ['No', 'Nama Alumni', 'NIS', 'Nama Usaha', 'Bidang Usaha', 'Alamat', 'Kota', 'Provinsi'],
            ];
            $columns = $columnsMap[$type] ?? ['ID', 'Nama'];

            $pdf = Pdf::loadView('exports.status-karier', [
                'title' => 'Laporan Status Karier — ' . ($typeLabels[$type] ?? $type),
                'columns' => $columns,
                'data' => $data,
                'generatedAt' => now()->format('d M Y H:i'),
            ]);

            return $pdf->download("status_karier_{$type}_{$timestamp}.pdf");
        }

        // ─── CSV Export ───────────────────────────────────
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="status_karier_' . $type . '_' . $timestamp . '.csv"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ];

        $callback = function () use ($data, $type) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM

            $csvColumnsMap = [
                'universitas' => ['ID', 'Nama Universitas', 'Jurusan'],
                'prodi'       => ['ID', 'Nama'],
                'wirausaha'   => ['No', 'Nama Alumni', 'NIS', 'Nama Usaha', 'Bidang Usaha', 'Alamat', 'Kota', 'Provinsi'],
            ];
            fputcsv($handle, $csvColumnsMap[$type] ?? ['ID', 'Nama']);

            foreach ($data as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
